<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $marchand;
    private $compte;
    private $marchandCompte;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create client user
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

        // Create marchand user
        $this->marchand = User::factory()->create([
            'telephone' => '773456789',
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $marchandClient = Client::create(['user_id' => $this->marchand->id]);
        $this->marchandCompte = Compte::create([
            'client_id' => $marchandClient->id,
            'numero_compte' => 'OM98765432',
            'type' => Compte::TYPE_MARCHAND,
            'statut' => 'actif',
            'code_marchand' => 'MCHTEST123'
        ]);

        // Create access token for client
        $this->token = $this->user->createToken('test')->plainTextToken;

        // Deposit money to client
        Transaction::create([
            'user_id' => $this->user->id,
            'compte_id' => $this->compte->id,
            'type' => 'depot',
            'montant' => 10000.00,
            'statut' => 'reussi',
            'date_operation' => now(),
            'reference' => 'DEPOSIT001'
        ]);
    }

    /** @test */
    public function user_can_pay_marchand()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 2500.00
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Paiement effectué avec succès'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'montant',
                        'code_marchand',
                        'solde_client'
                    ]
                ]);

        // Check debit transaction for client
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfert',
            'montant' => 2500.00,
            'statut' => 'reussi',
            'destinataire_id' => $this->marchandCompte->id
        ]);

        // Check credit transaction for marchand
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->marchand->id,
            'type' => 'transfert',
            'montant' => 2500.00,
            'statut' => 'reussi',
            'destinataire_id' => null
        ]);
    }

    /** @test */
    public function payment_fails_with_invalid_marchand_code()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/marchand', [
            'code_marchand' => 'INVALIDCODE',
            'montant' => 2500.00
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function payment_fails_with_insufficient_funds()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 15000.00 // More than balance
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function payment_fails_with_amount_below_minimum()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 50.00 // Below minimum
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function payment_fails_with_amount_above_maximum()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 2000000.00 // Above maximum
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function payment_requires_authentication()
    {
        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 2500.00
        ]);

        $response->assertStatus(401);
    }
}