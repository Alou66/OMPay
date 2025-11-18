<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
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

        // Create access token
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    /** @test */
    public function user_can_deposit_money()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/deposit', [
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
                            'id', 'type', 'montant', 'statut', 'reference'
                        ],
                        'reference'
                    ]
                ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 1000.00,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function deposit_fails_with_invalid_amount()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/deposit', [
            'amount' => 50.00, // Below minimum
            'description' => 'Test deposit'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function user_can_withdraw_money()
    {
        // First deposit some money
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 2000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'TEST001'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/withdraw', [
            'amount' => 500.00,
            'description' => 'Test withdrawal'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Retrait effectué avec succès'
                ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'retrait',
            'montant' => 500.00,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function withdrawal_fails_with_insufficient_funds()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/withdraw', [
            'amount' => 500.00,
            'description' => 'Test withdrawal'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function user_can_transfer_money()
    {
        // Create recipient
        $recipient = User::factory()->create([
            'telephone' => '772345678',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $recipientClient = Client::create(['user_id' => $recipient->id]);
        $recipientCompte = Compte::create([
            'client_id' => $recipientClient->id,
            'numero_compte' => 'OM87654321',
            'type' => Compte::TYPE_SIMPLE,
            'statut' => 'actif'
        ]);

        // Deposit money to sender
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 2000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'TEST002'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/transfer', [
            'recipient_telephone' => '772345678',
            'amount' => 500.00,
            'description' => 'Test transfer'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Transfert effectué avec succès'
                ]);

        // Check debit transaction
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfert',
            'montant' => 500.00,
            'statut' => 'reussi',
            'destinataire_id' => $recipientCompte->id
        ]);

        // Check credit transaction
        $this->assertDatabaseHas('transactions', [
            'user_id' => $recipient->id,
            'type' => 'transfert',
            'montant' => 500.00,
            'statut' => 'reussi',
            'destinataire_id' => null
        ]);
    }

    /** @test */
    public function transfer_fails_to_self()
    {
        // Deposit money
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 2000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'TEST003'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/transfer', [
            'recipient_telephone' => '771234567', // Same as sender
            'amount' => 500.00,
            'description' => 'Test transfer'
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function user_can_check_balance()
    {
        // Deposit money
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 1000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'TEST004'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/ompay/balance');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Solde récupéré avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'compte_id',
                        'numero_compte',
                        'solde',
                        'devise',
                        'date_consultation'
                    ]
                ]);
    }

    /** @test */
    public function user_can_get_transaction_history()
    {
        // Create some transactions
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 1000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'TEST005'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/ompay/history');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Historique récupéré avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'transactions' => [
                            '*' => [
                                'id', 'type', 'montant', 'statut', 'date_operation', 'reference'
                            ]
                        ],
                        'pagination' => [
                            'current_page', 'per_page', 'total', 'last_page'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function user_can_get_transactions_for_specific_account()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/ompay/transactions/' . $this->compte->id);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Historique récupéré avec succès'
                ]);
    }

    /** @test */
    public function user_can_logout()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/ompay/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Déconnexion réussie'
                ]);
    }
}