<?php

namespace Database\Factories;

use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;

// ─────────────────────────────────────────────────────────────────
// ModuleFactory.php
// Rôle : génère des modules de test avec des données aléatoires
//
// Utilisé dans les tests unitaires pour créer des modules
// sans avoir à remplir manuellement tous les champs
// ─────────────────────────────────────────────────────────────────
class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre'        => $this->faker->sentence(4),
            'contenu'      => $this->faker->paragraphs(3, true),
            'ordre'        => $this->faker->numberBetween(1, 10),
            'formation_id' => Formation::factory(),
        ];
    }
}