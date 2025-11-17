<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        // Create user with email
        $user = User::factory()->create([
            'nom' => 'Diop',
            'prenom' => 'Amadou',
            'telephone' => '771234567',
            'email' => 'amadou.diop@example.com',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        // Create client
        $client = Client::factory()->create(['user_id' => $user->id]);

        // Create compte
        $compte = Compte::factory()->create([
            'client_id' => $client->id,
            'numero_compte' => 'OM12345678',
            'type' => 'simple',
            'statut' => 'actif'
        ]);

        // Create some transactions
        Transaction::factory()->count(3)->create(['compte_id' => $compte->id]);

        // Authenticate user
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Dashboard utilisateur'
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user' => [
                             'id',
                             'nom',
                             'prenom',
                             'telephone',
                             'email'
                         ],
                         'compte' => [
                             'id',
                             'numero_compte',
                             'type',
                             'statut'
                         ],
                         'transactions'
                     ]
                 ]);

        // Check user data
        $response->assertJsonFragment([
            'user' => [
                'id' => $user->id,
                'nom' => 'Diop',
                'prenom' => 'Amadou',
                'telephone' => '771234567',
                'email' => 'amadou.diop@example.com'
            ]
        ]);

        // Check compte data
        $responseData = $response->json('data');
        $response->assertJsonFragment([
            'type' => 'simple',
            'statut' => 'actif'
        ]);
        $this->assertArrayHasKey('numero_compte', $responseData['compte']);

        // Check transactions count (should be 3, but limited to 10)
        $this->assertCount(3, $responseData['transactions']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Authentification requise'
                 ]);
    }

    /** @test */
    public function dashboard_returns_empty_compte_if_no_compte()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        // Create client but no compte
        Client::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'compte' => null,
                         'transactions' => []
                     ]
                 ]);
    }
}