<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'login' => $this->faker->unique()->phoneNumber(),
            'telephone' => $this->faker->unique()->phoneNumber(),
            'password' => Hash::make('password123'),
            'status' => 'Actif',
            'cni' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{9}'),
            'code' => 'OMPAY' . $this->faker->numberBetween(1000, 9999),
            'sexe' => $this->faker->randomElement(['Homme', 'Femme']),
            'role' => 'client',
            'is_verified' => true,
            'date_naissance' => $this->faker->date('Y-m-d', '-18 years'),
            'permissions' => [],
        ];
    }

    /**
     * Indicate that the user is pending verification.
     */
    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_verification',
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Inactif',
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}