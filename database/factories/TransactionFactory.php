<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $compte = Compte::factory()->create();
        $type = $this->faker->randomElement(['depot', 'retrait', 'transfert']);
        $destinataireId = null;

        if ($type === 'transfert') {
            $destinataireId = Compte::factory();
        }

        return [
            'user_id' => $compte->client->user_id,
            'compte_id' => $compte->id,
            'type' => $type,
            'montant' => $this->faker->randomFloat(2, 100, 100000),
            'destinataire_id' => $destinataireId,
            'statut' => $this->faker->randomElement(['reussi', 'echec', 'en_cours']),
            'date_operation' => $this->faker->dateTime,
            'reference' => 'TXN' . $this->faker->unique()->numberBetween(100000000000, 999999999999),
        ];
    }
}