<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Compte;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create sender
        $this->user = User::factory()->create([
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $client = Client::create(['user_id' => $this->user->id]);
        Compte::create([
            'client_id' => $client->id,
            'numero_compte' => 'OM12345678',
            'type' => \App\Models\Compte::TYPE_SIMPLE,
            'statut' => 'actif'
        ]);

        // Create recipient
        $this->recipient = User::factory()->create([
            'telephone' => '772345678',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $recipientClient = Client::create(['user_id' => $this->recipient->id]);
        Compte::create([
            'client_id' => $recipientClient->id,
            'numero_compte' => 'OM87654321',
            'type' => \App\Models\Compte::TYPE_SIMPLE,
            'statut' => 'actif'
        ]);
    }

    /** @test */
    public function user_can_deposit_money()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/ompay/deposit', [
            'amount' => 1000.00,
            'description' => 'Test deposit'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Dépôt effectué avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'transaction' => [
                            'id',
                            'type',
                            'montant',
                            'statut',
                            'reference'
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'depot',
            'montant' => 1000.00,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function user_can_transfer_money()
    {
        // First deposit some money
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/ompay/deposit', [
            'amount' => 2000.00,
            'description' => 'Initial deposit'
        ]);

        // Now transfer
        $response = $this->postJson('/api/ompay/transfer', [
            'recipient_telephone' => '772345678',
            'amount' => 500.00,
            'description' => 'Test transfer'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Transfert effectué avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'debit_transaction',
                        'credit_transaction',
                        'reference'
                    ]
                ]);

        // Check debit transaction
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfert',
            'montant' => 500.00,
            'statut' => 'reussi'
        ]);

        // Check credit transaction
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->recipient->id,
            'type' => 'transfert',
            'montant' => 500.00,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function transfer_fails_with_insufficient_funds()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/ompay/transfer', [
            'recipient_telephone' => '772345678',
            'amount' => 500.00,
            'description' => 'Test transfer'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function transfer_fails_to_inactive_account()
    {
        // Make recipient account inactive
        $recipientCompte = $this->recipient->client->comptes()->first();
        $recipientCompte->update(['statut' => 'inactif']);

        // Deposit money first
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/ompay/deposit', [
            'amount' => 1000.00,
            'description' => 'Initial deposit'
        ]);

        // Try transfer
        $response = $this->postJson('/api/ompay/transfer', [
            'recipient_telephone' => '772345678',
            'amount' => 500.00,
            'description' => 'Test transfer'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Le compte destinataire n\'est pas actif'
                ]);
    }

    /** @test */
    public function user_can_get_balance()
    {
        // Deposit money
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/ompay/deposit', [
            'amount' => 1500.00,
            'description' => 'Test deposit'
        ]);

        $response = $this->getJson('/api/ompay/balance');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Solde récupéré avec succès',
                    'data' => [
                        'solde' => 1500.00,
                        'devise' => 'FCFA'
                    ]
                ]);
    }

    /** @test */
    public function user_can_get_transaction_history()
    {
        // Create some transactions
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/ompay/deposit', ['amount' => 1000.00]);
        $this->postJson('/api/ompay/withdraw', ['amount' => 200.00]);

        $response = $this->getJson('/api/ompay/history');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Historique récupéré avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'transactions',
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'last_page'
                        ]
                    ]
                ]);
    }
}