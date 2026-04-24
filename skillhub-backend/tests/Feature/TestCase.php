<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase as BaseTestCase;

// ─────────────────────────────────────────────────────────────────
// TestCase.php (Feature)
// Classe de base pour tous les tests Feature qui ont besoin
// d'un token JWT valide.
//
// fakeSpringBoot() intercepte les appels vers Spring Boot
// et retourne une réponse simulée avec le bon rôle.
// Utilise Http::sequence() pour que les appels multiples
// dans le même test retournent toujours la même réponse.
// ─────────────────────────────────────────────────────────────────
class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function fakeSpringBoot(string $role, int $userId, string $email = 'test@test.com'): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'email'  => $email,
                    'role'   => $role,
                    'userId' => $userId,
                ], 200)
                ->whenEmpty(Http::response([
                    'email'  => $email,
                    'role'   => $role,
                    'userId' => $userId,
                ], 200)),
        ]);
    }
}