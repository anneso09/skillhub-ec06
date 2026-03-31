<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Formation;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnrollmentController extends Controller
{
    public function store($formationId)
    {
        $formation = Formation::find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation introuvable'], 404);
        }

        $user = JWTAuth::user();

        $dejInscrit = Enrollment::where('utilisateur_id', $user->id)
            ->where('formation_id', $formationId)
            ->exists();

        if ($dejInscrit) {
            return response()->json(['message' => 'Vous êtes déjà inscrit à cette formation'], 409);
        }

        $enrollment = Enrollment::create([
            'utilisateur_id' => $user->id,
            'formation_id'   => $formationId,
            'progression'    => 0,
        ]);

        return response()->json([
            'message'    => 'Inscription réussie',
            'enrollment' => $enrollment
        ], 201);
    }

    public function destroy($formationId)
    {
        $user = JWTAuth::user();

        $enrollment = Enrollment::where('utilisateur_id', $user->id)
            ->where('formation_id', $formationId)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Vous n\'êtes pas inscrit à cette formation'], 404);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Désinscription réussie']);
    }

    public function mesFormations()
    {
        $user = JWTAuth::user();

        $formations = Enrollment::where('utilisateur_id', $user->id)
            ->with('formation.formateur:id,nom,prenom')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'progression'   => $enrollment->progression,
                    'date_inscription' => $enrollment->date_inscription,
                    'formation'     => $enrollment->formation,
                ];
            });

        return response()->json($formations);
    }

    public function updateProgression(Request $request, $formationId)
    {
        $user = JWTAuth::user();

        $enrollment = Enrollment::where('utilisateur_id', $user->id)
            ->where('formation_id', $formationId)
            ->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Vous n\'êtes pas inscrit à cette formation'], 404);
        }

        $request->validate([
            'progression' => 'required|integer|min:0|max:100'
        ]);

        $enrollment->update(['progression' => $request->progression]);

        return response()->json([
            'message'    => 'Progression mise à jour',
            'enrollment' => $enrollment
        ]);
    }
}