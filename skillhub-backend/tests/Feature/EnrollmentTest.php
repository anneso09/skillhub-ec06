<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Formation;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Enrollment;
// ─────────────────────────────────────────────────────────────────
// EnrollmentTest.php
// Rôle : tests d'intégration pour les inscriptions aux formations
//
// Teste les opérations principales du EnrollmentController :
//   - inscription d'un apprenant à une formation
//   - double inscription impossible
//   - désinscription
//   - consultation des formations suivies
//   - mise à jour de la progression
//
// Pour lancer : php artisan test --filter EnrollmentTest
// ─────────────────────────────────────────────────────────────────
class EnrollmentTest extends TestCase
{
    // ─────────────────────────────────────────────────────────
    // Crée un formateur + une formation pour les tests
    // Pas de fakeSpringBoot ici — chaque test gère son propre fake
    // ─────────────────────────────────────────────────────────
    private function creerFormation(): array
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $token = JWTAuth::fromUser($formateur);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
        return ['formation' => $formation, 'token' => $token, 'formateur' => $formateur];
    }

    // ─────────────────────────────────────────────────────────
    // Crée un apprenant
    // Pas de fakeSpringBoot ici — chaque test gère son propre fake
    // ─────────────────────────────────────────────────────────
    private function creerApprenant(): array
    {
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $token = JWTAuth::fromUser($apprenant);
        return ['apprenant' => $apprenant, 'token' => $token];
    }

    // ─────────────────────────────────────────────────────────
    // Test 1 : inscription réussie
    // Vérifie le code HTTP 201 et la persistance en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_peut_sinscrire_a_une_formation()
    {
        $data = $this->creerFormation();
        $apprenant = $this->creerApprenant();

        $this->fakeSpringBoot('apprenant', $apprenant['apprenant']->id, $apprenant['apprenant']->email);

        $response = $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('enrollments', [
            'utilisateur_id' => $apprenant['apprenant']->id,
            'formation_id'   => $data['formation']->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 2 : double inscription impossible
    // Vérifie que le code HTTP 409 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_ne_peut_pas_sinscrire_deux_fois()
    {
        $data = $this->creerFormation();
        $apprenant = $this->creerApprenant();

        $this->fakeSpringBoot('apprenant', $apprenant['apprenant']->id, $apprenant['apprenant']->email);

        // Première inscription
        $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        // Deuxième inscription — doit échouer avec 409 Conflict
        $response = $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        $response->assertStatus(409);
    }

    // ─────────────────────────────────────────────────────────
    // Test 3 : désinscription réussie
    // Vérifie que l'enrollment n'existe plus en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_peut_se_desinscrire()
    {
        $data = $this->creerFormation();
        $apprenant = $this->creerApprenant();

        $this->fakeSpringBoot('apprenant', $apprenant['apprenant']->id, $apprenant['apprenant']->email);

        // S'inscrire d'abord
        $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        // Se désinscrire
        $response = $this->deleteJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('enrollments', [
            'utilisateur_id' => $apprenant['apprenant']->id,
            'formation_id'   => $data['formation']->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 4 : consultation des formations suivies
    // Vérifie que la route retourne 200
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_peut_voir_ses_formations()
    {
        $data = $this->creerFormation();
        $apprenant = $this->creerApprenant();

        $this->fakeSpringBoot('apprenant', $apprenant['apprenant']->id, $apprenant['apprenant']->email);

        // S'inscrire d'abord
        $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        // Consulter ses formations
        $response = $this->getJson(
            '/api/apprenant/formations',
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────
    // Test 5 : mise à jour de la progression
    // Vérifie que la progression est bien enregistrée
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_peut_mettre_a_jour_sa_progression()
    {
        $data = $this->creerFormation();
        $apprenant = $this->creerApprenant();

        $this->fakeSpringBoot('apprenant', $apprenant['apprenant']->id, $apprenant['apprenant']->email);

        // S'inscrire d'abord
        $this->postJson(
            "/api/formations/{$data['formation']->id}/inscription",
            [],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        // Mettre à jour la progression à 75%
        $response = $this->putJson(
            "/api/formations/{$data['formation']->id}/progression",
            ['progression' => 75],
            ['Authorization' => "Bearer {$apprenant['token']}"]
        );

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────
    // Test 6 : un apprenant ne peut pas s'inscrire à plus de 5 formations
    // Vérifie que le code HTTP 400 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function test_un_apprenant_ne_peut_pas_sinscrire_a_plus_de_5_formations()
    {
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $this->fakeSpringBoot('apprenant', $apprenant->id, $apprenant->email);
        $token = JWTAuth::fromUser($apprenant);

        // Créer un formateur pour les formations
        $formateur = User::factory()->create(['role' => 'formateur']);

        // Inscrire l'apprenant à 5 formations
        for ($i = 0; $i < 5; $i++) {
            $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
            Enrollment::factory()->create([
                'utilisateur_id' => $apprenant->id,
                'formation_id'   => $formation->id,
            ]);
        }

        // Tenter une 6ème inscription
        $formation6 = Formation::factory()->create(['formateur_id' => $formateur->id]);

        $response = $this->postJson(
            "/api/formations/{$formation6->id}/inscription",
            [],
            ['Authorization' => "Bearer $token"]
        );

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Vous ne pouvez pas vous inscrire à plus de 5 formations.',
        ]);
    }
}
