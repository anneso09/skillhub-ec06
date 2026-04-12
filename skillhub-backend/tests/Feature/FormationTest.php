<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Formation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class FormationTest extends TestCase
{
    use RefreshDatabase;

    private function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    #[Test]
    public function un_formateur_peut_creer_une_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
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
                'formation' => [ // ← formation est dans un wrapper
                    'id',
                    'titre',
                    'description',
                    'categorie',
                    'niveau',
                    'formateur_id'
                ]
            ]);

        $this->assertDatabaseHas('formations', [
            'titre'        => 'Introduction à React',
            'formateur_id' => $formateur->id,
        ]);
    }

    #[Test]
    public function un_formateur_peut_modifier_sa_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $token     = $this->getToken($formateur);

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

        $this->assertDatabaseHas('formations', [
            'id'    => $formation->id,
            'titre' => 'Titre modifié',
        ]);
    }

    #[Test]
    public function un_formateur_peut_supprimer_sa_formation()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $token     = $this->getToken($formateur);

        $formation = Formation::factory()->create([
            'formateur_id' => $formateur->id,
        ]);

        $response = $this->deleteJson("/api/formations/{$formation->id}", [], [
            'Authorization' => "Bearer $token"
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('formations', [
            'id' => $formation->id,
        ]);
    }

    #[Test]
    public function tout_le_monde_peut_voir_les_formations()
    {
        Formation::factory()->count(3)->create();

        $response = $this->getJson('/api/formations');

        $response->assertStatus(200);
    }
}
