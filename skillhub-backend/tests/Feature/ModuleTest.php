<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Formation;
use App\Models\Module;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;

// ─────────────────────────────────────────────────────────────────
// ModuleTest.php
// Rôle : tests d'intégration pour le CRUD des modules
//
// Teste les opérations principales du ModuleController :
//   - liste des modules d'une formation (public)
//   - création d'un module par le formateur propriétaire
//   - création refusée si formateur non propriétaire
//   - modification d'un module par le propriétaire
//   - modification refusée si formateur non propriétaire
//   - suppression d'un module par le propriétaire
//   - suppression refusée si formateur non propriétaire
//
// Pour lancer : php artisan test --filter ModuleTest
// ─────────────────────────────────────────────────────────────────
class ModuleTest extends TestCase
{
    private function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    // ─────────────────────────────────────────────────────────
    // Test 1 : liste des modules accessible sans token
    // Vérifie que la route publique retourne 200
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function tout_le_monde_peut_voir_les_modules_dune_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
        Module::factory()->count(3)->create(['formation_id' => $formation->id]);

        $response = $this->getJson("/api/formations/{$formation->id}/modules");

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────
    // Test 2 : création d'un module par le formateur propriétaire
    // Vérifie le code HTTP 201 et la persistance en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_creer_un_module_dans_sa_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);

        $response = $this->postJson("/api/formations/{$formation->id}/modules", [
            'titre'   => 'Introduction au HTML',
            'contenu' => 'Dans ce module nous allons apprendre les bases du HTML.',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'module' => ['id', 'titre', 'contenu', 'formation_id', 'ordre'],
            ]);

        $this->assertDatabaseHas('modules', [
            'titre'        => 'Introduction au HTML',
            'formation_id' => $formation->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 3 : création refusée si formateur non propriétaire
    // Vérifie que le code HTTP 403 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_ne_peut_pas_creer_un_module_dans_la_formation_dun_autre()
    {
        $formateur1 = User::factory()->create(['role' => 'formateur']);
        $formateur2 = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur2->id, $formateur2->email);
        $token2 = $this->getToken($formateur2);

        // Formation appartenant à formateur1
        $formation = Formation::factory()->create(['formateur_id' => $formateur1->id]);

        $response = $this->postJson("/api/formations/{$formation->id}/modules", [
            'titre'   => 'Module non autorisé',
            'contenu' => 'Contenu non autorisé',
        ], ['Authorization' => "Bearer $token2"]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────
    // Test 4 : modification d'un module par le propriétaire
    // Vérifie que le titre est bien mis à jour en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_modifier_son_module()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
        $module = Module::factory()->create([
            'formation_id' => $formation->id,
            'titre'        => 'Ancien titre',
        ]);

        $response = $this->putJson("/api/modules/{$module->id}", [
            'titre'   => 'Nouveau titre',
            'contenu' => $module->contenu,
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('modules', [
            'id'    => $module->id,
            'titre' => 'Nouveau titre',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 5 : modification refusée si formateur non propriétaire
    // Vérifie que le code HTTP 403 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_ne_peut_pas_modifier_le_module_dun_autre()
    {
        $formateur1 = User::factory()->create(['role' => 'formateur']);
        $formateur2 = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur2->id, $formateur2->email);
        $token2 = $this->getToken($formateur2);

        $formation = Formation::factory()->create(['formateur_id' => $formateur1->id]);
        $module = Module::factory()->create(['formation_id' => $formation->id]);

        $response = $this->putJson("/api/modules/{$module->id}", [
            'titre' => 'Tentative de modification',
        ], ['Authorization' => "Bearer $token2"]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────
    // Test 6 : suppression d'un module par le propriétaire
    // Vérifie que le module n'existe plus en BDD
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_supprimer_son_module()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
        $module = Module::factory()->create(['formation_id' => $formation->id]);

        $response = $this->deleteJson(
            "/api/modules/{$module->id}",
            [],
            ['Authorization' => "Bearer $token"]
        );

        $response->assertStatus(200);

        $this->assertDatabaseMissing('modules', [
            'id' => $module->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 7 : suppression refusée si formateur non propriétaire
    // Vérifie que le code HTTP 403 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_ne_peut_pas_supprimer_le_module_dun_autre()
    {
        $formateur1 = User::factory()->create(['role' => 'formateur']);
        $formateur2 = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur2->id, $formateur2->email);
        $token2 = $this->getToken($formateur2);

        $formation = Formation::factory()->create(['formateur_id' => $formateur1->id]);
        $module = Module::factory()->create(['formation_id' => $formation->id]);

        $response = $this->deleteJson(
            "/api/modules/{$module->id}",
            [],
            ['Authorization' => "Bearer $token2"]
        );

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────
    // Test 8 : formation introuvable → 404
    // Vérifie que le code HTTP 404 est retourné
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function liste_modules_retourne_404_si_formation_inexistante()
    {
        $response = $this->getJson("/api/formations/99999/modules");

        $response->assertStatus(404);
    }
    // ─────────────────────────────────────────────────────────
    // Test 9 : module introuvable → 404 sur modification
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function modifier_module_inexistant_retourne_404()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->putJson("/api/modules/99999", [
            'titre' => 'Test',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────
    // Test 10 : module introuvable → 404 sur suppression
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function supprimer_module_inexistant_retourne_404()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->deleteJson(
            "/api/modules/99999",
            [],
            ['Authorization' => "Bearer $token"]
        );

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────
    // Test 11 : création avec ordre personnalisé
    // Vérifie que l'ordre fourni est bien respecté
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function un_formateur_peut_creer_un_module_avec_ordre_personnalise()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);

        $response = $this->postJson("/api/formations/{$formation->id}/modules", [
            'titre'   => 'Module avec ordre',
            'contenu' => 'Contenu du module',
            'ordre'   => 3,
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('modules', [
            'titre'        => 'Module avec ordre',
            'formation_id' => $formation->id,
            'ordre'        => 3,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Test 12 : création avec formation inexistante → 404
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function creer_module_dans_formation_inexistante_retourne_404()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);

        $response = $this->postJson("/api/formations/99999/modules", [
            'titre'   => 'Module test',
            'contenu' => 'Contenu test',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────
    // Test 13 : validation échoue sur création module → 422
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function creer_module_sans_titre_retourne_422()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);

        $response = $this->postJson("/api/formations/{$formation->id}/modules", [
            'contenu' => 'Contenu sans titre',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────
    // Test 14 : validation échoue sur modification module → 422
    // ─────────────────────────────────────────────────────────
    #[Test]
    public function modifier_module_avec_titre_invalide_retourne_422()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $this->fakeSpringBoot('formateur', $formateur->id, $formateur->email);
        $token = $this->getToken($formateur);
        $formation = Formation::factory()->create(['formateur_id' => $formateur->id]);
        $module = Module::factory()->create(['formation_id' => $formation->id]);

        $response = $this->putJson("/api/modules/{$module->id}", [
            'titre' => str_repeat('a', 256), // dépasse max:255
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(422);
    }
}