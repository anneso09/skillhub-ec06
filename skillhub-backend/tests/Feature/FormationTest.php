<?php

namespace Tests\Feature;

use Tests\Feature\TestCase;
use App\Models\User;
use App\Models\Formation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

// ─────────────────────────────────────────────────────────────────
// FormationTest.php
// Rôle : tests d'intégration pour le CRUD des formations
//
// Teste les opérations principales du FormationController :
//   - création par un formateur
//   - modification par le propriétaire
//   - suppression par le propriétaire
//   - lecture publique sans token
//
// Pour lancer : php artisan test --filter FormationTest
// ─────────────────────────────────────────────────────────────────
class FormationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────
    // Génère un token JWT valide pour un utilisateur donné
    // Utilisé dans tous les tests qui nécessitent
    // une authentification
    // ─────────────────────────────────────────────────────────
    private function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }


    // ─────────────────────────────────────────────────────────
    // Test 1 : création de formation par un formateur
    // Vérifie le code HTTP, la structure JSON
    // et la persistance en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_creer_une_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token     = $this->getToken($formateur);

        $response = $this->postJson('/api/formations', [
            'titre'       => 'Introduction à React',
            'description' => 'Apprends React de zéro',
            'categorie'   => 'Développement web',
            'niveau'      => 'Débutant',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'formation' => [
                    'id',
                    'titre',
                    'description',
                    'categorie',
                    'niveau',
                    'formateur_id',
                ],
            ]);

        // Vérifie que la formation est bien liée au formateur
        $this->assertDatabaseHas('formations', [
            'titre'        => 'Introduction à React',
            'formateur_id' => $formateur->id,
        ]);
    }


    // ─────────────────────────────────────────────────────────
    // Test 2 : modification de sa propre formation
    // Vérifie que le titre est bien mis à jour en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_modifier_sa_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token     = $this->getToken($formateur);

        // Crée une formation appartenant à ce formateur
        $formation = Formation::factory()->create([
            'formateur_id' => $formateur->id,
        ]);

        $response = $this->putJson("/api/formations/{$formation->id}", [
            'titre'       => 'Titre modifié',
            'description' => $formation->description,
            'categorie'   => $formation->categorie,
            'niveau'      => $formation->niveau,
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200);

        // Vérifie que le nouveau titre est en BDD
        $this->assertDatabaseHas('formations', [
            'id'    => $formation->id,
            'titre' => 'Titre modifié',
        ]);
    }


    // ─────────────────────────────────────────────────────────
    // Test 3 : suppression de sa propre formation
    // Vérifie que la formation n'existe plus en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_supprimer_sa_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token     = $this->getToken($formateur);

        $formation = Formation::factory()->create([
            'formateur_id' => $formateur->id,
        ]);

        $response = $this->deleteJson(
            "/api/formations/{$formation->id}",
            [],
            ['Authorization' => "Bearer $token"]
        );

        $response->assertStatus(200);

        // assertDatabaseMissing = vérifie l'ABSENCE en BDD
        $this->assertDatabaseMissing('formations', [
            'id' => $formation->id,
        ]);
    }


    // ─────────────────────────────────────────────────────────
    // Test 4 : lecture publique sans token
    // Vérifie que le catalogue est accessible à tous
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function tout_le_monde_peut_voir_les_formations()
    {
        // Crée 3 formations en BDD via factory
        Formation::factory()->count(3)->create();

        // Requête sans token → doit quand même retourner 200
        $response = $this->getJson('/api/formations');

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────
    // Test 5 : formation inexistante → 404 sur modification
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function modifier_formation_inexistante_retourne_404()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->putJson("/api/formations/99999", [
            'titre' => 'Test',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────
    // Test 6 : formation inexistante → 404 sur suppression
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function supprimer_formation_inexistante_retourne_404()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->deleteJson(
            "/api/formations/99999",
            [],
            ['Authorization' => "Bearer $token"]
        );

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────
    // Test 7 : validation échoue sur création → 422
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function creer_formation_sans_titre_retourne_422()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->postJson('/api/formations', [
            'description' => 'Description sans titre',
            'categorie'   => 'Design',
            'niveau'      => 'Débutant',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────
    // Test 8 : mes formations (dashboard formateur)
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_voir_ses_propres_formations()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        Formation::factory()->count(3)->create(['formateur_id' => $formateur->id]);

        $response = $this->getJson('/api/formateur/formations', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200);
    }
}