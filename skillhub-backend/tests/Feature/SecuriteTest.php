<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Formation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class SecuriteTest extends TestCase
{
    use RefreshDatabase;

    private function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    #[Test]
    public function un_utilisateur_non_authentifie_ne_peut_pas_creer_une_formation()
    {
        $response = $this->postJson('/api/formations', [
            'titre'       => 'Formation test',
            'description' => 'Description test',
            'categorie'   => 'Design',
            'niveau'      => 'Débutant',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function un_apprenant_ne_peut_pas_creer_une_formation()
    {
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $token     = $this->getToken($apprenant);

        $response = $this->postJson('/api/formations', [
            'titre'       => 'Formation test',
            'description' => 'Description test',
            'categorie'   => 'Design',
            'niveau'      => 'Débutant',
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403);
    }

    #[Test]
    public function un_formateur_ne_peut_pas_modifier_la_formation_dun_autre()
    {
        $formateur1 = User::factory()->create(['role' => 'formateur']);
        $formateur2 = User::factory()->create(['role' => 'formateur']);
        $token2     = $this->getToken($formateur2);

        // Formation créée par formateur1
        $formation = Formation::factory()->create([
            'formateur_id' => $formateur1->id,
        ]);

        // Formateur2 essaie de modifier
        $response = $this->putJson("/api/formations/{$formation->id}", [
            'titre'       => 'Tentative de modification',
            'description' => $formation->description,
            'categorie'   => $formation->categorie,
            'niveau'      => $formation->niveau,
        ], ['Authorization' => "Bearer $token2"]);

        $response->assertStatus(403);
    }

    #[Test]
    public function un_utilisateur_non_authentifie_ne_peut_pas_voir_son_profil()
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    #[Test]
    public function un_apprenant_ne_peut_pas_acceder_au_dashboard_formateur()
    {
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $token     = $this->getToken($apprenant);

        $response = $this->getJson('/api/formateur/formations', [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(403);
    }
}