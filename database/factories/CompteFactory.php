<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'numero_compte' => 'OM' . $this->faker->unique()->numberBetween(10000000, 99999999),
            'type' => $this->faker->randomElement(['marchand', 'simple']),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'bloqué', 'fermé']),
            'motif_blocage' => $this->faker->optional()->sentence,
            'date_fermeture' => $this->faker->optional()->dateTime,
        ];
    }
}