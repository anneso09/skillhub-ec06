<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_utilisateur_peut_sinscrire()
    {
        $response = $this->postJson('/api/register', [
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
            'email'    => 'jean@test.com',
            'password' => 'password123',
            'role'     => 'apprenant',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'nom', 'prenom', 'email', 'role'],
                     'token',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jean@test.com',
            'role'  => 'apprenant',
        ]);
    }

    #[Test]
    public function un_utilisateur_peut_se_connecter()
    {
        // Crée un user en base
        $user = User::factory()->create([
            'email'    => 'jean@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'apprenant',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'jean@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user',
                     'token',
                 ]);
    }

    #[Test]
    public function connexion_echoue_avec_mauvais_mot_de_passe()
    {
        User::factory()->create([
            'email'    => 'jean@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'jean@test.com',
            'password' => 'mauvaismdp',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function inscription_echoue_si_email_deja_utilise()
    {
        User::factory()->create(['email' => 'jean@test.com']);

        $response = $this->postJson('/api/register', [
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
            'email'    => 'jean@test.com',
            'password' => 'password123',
            'role'     => 'apprenant',
        ]);

        $response->assertStatus(422);
    }
}