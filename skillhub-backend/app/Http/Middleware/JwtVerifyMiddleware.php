<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JwtVerifyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Récupération du header Authorization
        $authHeader = $request->header('Authorization');

        // Vérification présence du token
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Token manquant'
            ], 401);
        }

        // Extraction du token
        $token = substr($authHeader, 7);

        /**
         * MODE SIMULATION (utilisé pour le développement)
         * Permet de tester sans Spring Boot
         */
        if (false) { // 👉 tu peux remplacer par env('AUTH_SIMULATION', true)
            
            $payload = [
                'email' => 'test@test.com',
                'role'  => 'formateur', // changer en 'apprenant' pour tester
                'userId'=> 1
            ];

        } else {
            /**
             * MODE RÉEL (avec Spring Boot)
             */
            try {
                $response = Http::timeout(5)->post(
                    'http://127.0.0.1:8080/api/auth/validate',
                    ['token' => $token]
                );

                if (!$response->successful()) {
                    return response()->json([
                        'message' => 'Token invalide ou expiré'
                    ], 401);
                }

                $payload = $response->json();

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Service authentification indisponible'
                ], 503);
            }
        }

        // Injection des infos utilisateur dans la requête
        $request->merge([
            'auth_user_email' => $payload['email'],
            'auth_user_role'  => $payload['role'],
            'auth_user_id'    => $payload['userId'],
        ]);

        return $next($request);
    }
}