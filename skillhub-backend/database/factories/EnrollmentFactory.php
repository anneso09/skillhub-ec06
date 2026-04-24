<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;

// ─────────────────────────────────────────────────────────────────
// EnrollmentFactory.php
// Rôle : génère des inscriptions de test avec des données aléatoires
//
// Utilisé dans les tests unitaires pour créer des enrollments
// sans avoir à remplir manuellement tous les champs
// ─────────────────────────────────────────────────────────────────
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'utilisateur_id' => User::factory(),
            'formation_id'   => Formation::factory(),
            'progression'    => $this->faker->numberBetween(0, 100),
        ];
    }
}