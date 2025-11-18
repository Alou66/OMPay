<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $compte;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with active account
        $this->user = User::factory()->create([
            'telephone' => '771234567',
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => 'test@example.com',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $client = Client::create(['user_id' => $this->user->id]);

        $this->compte = Compte::create([
            'client_id' => $client->id,
            'numero_compte' => 'OM12345678',
            'type' => Compte::TYPE_SIMPLE,
            'statut' => 'actif'
        ]);

        // Create some transactions
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 5000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'DASH001'
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'retrait',
            'montant' => 1000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'DASH002'
        ]);

        // Create access token
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Dashboard utilisateur'
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user' => [
                             'nom', 'prenom', 'telephone', 'email'
                         ],
                         'compte' => [
                             'numero_compte', 'type', 'statut', 'solde', 'devise'
                         ],
                         'transactions' => [
                             '*' => [
                                 'type', 'montant', 'statut', 'date_operation', 'reference'
                             ]
                         ]
                     ]
                 ]);

        // Verify user data
        $response->assertJsonFragment([
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '771234567',
            'email' => 'test@example.com'
        ]);

        // Verify compte data
        $response->assertJsonFragment([
            'numero_compte' => $this->compte->numero_compte,
            'type' => 'simple',
            'statut' => 'actif',
            'solde' => 4000.0,
            'devise' => 'FCFA'
        ]);

        // Verify transactions (should have last 10, but we have 2)
        $response->assertJsonCount(2, 'data.transactions');
    }

    /** @test */
    public function dashboard_requires_authentication()
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_shows_correct_balance()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/dashboard');

        $response->assertStatus(200);

        // Balance should be 5000 - 1000 = 4000
        $response->assertJsonFragment([
            'solde' => 4000.0
        ]);
    }

    /** @test */
    public function dashboard_returns_empty_transactions_if_none()
    {
        // Delete existing transactions
        Transaction::where('user_id', $this->user->id)->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/dashboard');

        $response->assertStatus(200)
                ->assertJsonCount(0, 'data.transactions');
    }
}