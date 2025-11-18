<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaiementMarchandTest extends TestCase
{
    use RefreshDatabase;

    private $client;
    private $marchand;
    private $clientCompte;
    private $marchandCompte;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client avec compte simple
        $this->client = User::factory()->create([
            'telephone' => '771234567',
            'status' => 'Actif',
            'is_verified' => true
        ]);
        $clientClient = Client::factory()->create(['user_id' => $this->client->id]);
        $this->clientCompte = Compte::factory()->create([
            'client_id' => $clientClient->id,
            'type' => Compte::TYPE_SIMPLE,
            'statut' => 'actif'
        ]);

        // Créer un marchand avec compte marchand
        $this->marchand = User::factory()->create([
            'telephone' => '772345678',
            'status' => 'Actif',
            'is_verified' => true
        ]);
        $marchandClient = Client::factory()->create(['user_id' => $this->marchand->id]);
        $this->marchandCompte = Compte::factory()->create([
            'client_id' => $marchandClient->id,
            'type' => Compte::TYPE_MARCHAND,
            'statut' => 'actif',
            'code_marchand' => 'MCHTEST123'
        ]);

        // Ajouter un solde au client
        Transaction::factory()->create([
            'user_id' => $this->client->id,
            'compte_id' => $this->clientCompte->id,
            'type' => 'depot',
            'montant' => 10000,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function paiement_marchand_reussi()
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 5000
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Paiement effectué avec succès',
                     'data' => [
                         'montant' => 5000,
                         'code_marchand' => 'MCHTEST123',
                         'solde_client' => 5000 // 10000 - 5000
                     ]
                 ]);

        // Vérifier les transactions créées
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->client->id,
            'compte_id' => $this->clientCompte->id,
            'type' => 'transfert',
            'montant' => 5000,
            'statut' => 'reussi',
            'destinataire_id' => $this->marchandCompte->id
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->marchand->id,
            'compte_id' => $this->marchandCompte->id,
            'type' => 'transfert',
            'montant' => 5000,
            'statut' => 'reussi'
        ]);
    }

    /** @test */
    public function paiement_marchand_solde_insuffisant()
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 15000 // Plus que le solde de 10000
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Solde insuffisant pour effectuer cette opération'
                 ]);

        // Vérifier qu'aucune transaction n'a été créée
        $this->assertDatabaseMissing('transactions', [
            'type' => 'transfert',
            'montant' => 15000
        ]);
    }

    /** @test */
    public function paiement_marchand_code_invalide()
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'INVALIDCODE',
            'montant' => 1000
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['code_marchand']);
    }

    /** @test */
    public function paiement_marchand_compte_inactif()
    {
        // Désactiver le compte marchand
        $this->marchandCompte->update(['statut' => 'inactif']);

        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 1000
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Le compte marchand n\'est pas actif'
                 ]);
    }

    /** @test */
    public function paiement_marchand_non_authentifie()
    {
        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 1000
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function paiement_marchand_vers_soi_meme_impossible()
    {
        // Créer un code marchand pour le client
        $this->clientCompte->update([
            'type' => Compte::TYPE_MARCHAND,
            'code_marchand' => 'MCHCLIENT123'
        ]);

        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHCLIENT123',
            'montant' => 1000
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Impossible de payer son propre compte marchand'
                 ]);
    }

    /** @test */
    public function paiement_marchand_montant_minimum()
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 50 // Moins que 100
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['montant']);
    }

    /** @test */
    public function paiement_marchand_montant_maximum()
    {
        Sanctum::actingAs($this->client);

        $response = $this->postJson('/api/paiement/marchand', [
            'code_marchand' => 'MCHTEST123',
            'montant' => 2000000 // Plus que 1000000
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['montant']);
    }
}