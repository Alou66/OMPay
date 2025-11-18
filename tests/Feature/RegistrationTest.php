<?php

namespace Tests\Feature;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration with simple account type.
     */
    public function test_registration_with_simple_account(): void
    {
        $data = [
            'nom' => 'Diop',
            'prenom' => 'Amadou',
            'telephone' => '771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'AB123456789',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            'type_compte' => Compte::TYPE_SIMPLE,
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Utilisateur créé avec succès – demande de vérification OTP',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                 ])
                 ->assertJsonMissing(['data']);

        $user = User::where('telephone', '771234567')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending_verification', $user->status);

        $compte = $user->client->comptes()->first();
        $this->assertNotNull($compte);
        $this->assertEquals(Compte::TYPE_SIMPLE, $compte->type);
        $this->assertNull($compte->code_marchand);
        $this->assertEquals('inactif', $compte->statut);
    }

    /**
     * Test registration with marchand account type.
     */
    public function test_registration_with_marchand_account(): void
    {
        $data = [
            'nom' => 'Fall',
            'prenom' => 'Fatou',
            'telephone' => '772345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'CD987654321',
            'sexe' => 'Femme',
            'date_naissance' => '1985-05-15',
            'type_compte' => Compte::TYPE_MARCHAND,
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Utilisateur créé avec succès – demande de vérification OTP',
                 ])
                 ->assertJsonStructure([
                     'data' => ['code_marchand']
                 ]);

        $user = User::where('telephone', '772345678')->first();
        $this->assertNotNull($user);

        $compte = $user->client->comptes()->first();
        $this->assertNotNull($compte);
        $this->assertEquals(Compte::TYPE_MARCHAND, $compte->type);
        $this->assertNotNull($compte->code_marchand);
        $this->assertStringStartsWith('MCH', $compte->code_marchand);
        $this->assertEquals(11, strlen($compte->code_marchand)); // MCH + 8 chars
    }

    /**
     * Test that code_marchand is unique for marchand accounts.
     */
    public function test_code_marchand_is_unique(): void
    {
        // Create first marchand account
        $data1 = [
            'nom' => 'Test1',
            'prenom' => 'User1',
            'telephone' => '773456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'EF111111111',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            'type_compte' => Compte::TYPE_MARCHAND,
        ];

        $this->postJson('/api/auth/register', $data1);

        // Create second marchand account
        $data2 = [
            'nom' => 'Test2',
            'prenom' => 'User2',
            'telephone' => '774567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'EF222222222',
            'sexe' => 'Femme',
            'date_naissance' => '1990-01-01',
            'type_compte' => Compte::TYPE_MARCHAND,
        ];

        $this->postJson('/api/auth/register', $data2);

        $comptes = Compte::where('type', Compte::TYPE_MARCHAND)->get();
        $this->assertCount(2, $comptes);

        $codes = $comptes->pluck('code_marchand')->toArray();
        $this->assertCount(2, array_unique($codes)); // All unique
    }

    /**
     * Test registration with invalid data.
     */
    public function test_registration_with_invalid_data(): void
    {
        $data = [
            'nom' => '',
            'prenom' => 'Amadou',
            'telephone' => '771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'AB123456789',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            'type_compte' => 'invalid',
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                 ]);
    }

    /**
     * Test registration with duplicate telephone.
     */
    public function test_registration_with_duplicate_telephone(): void
    {
        // First registration
        $data = [
            'nom' => 'Diop',
            'prenom' => 'Amadou',
            'telephone' => '771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'AB123456789',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            'type_compte' => Compte::TYPE_SIMPLE,
        ];

        $this->postJson('/api/auth/register', $data);

        // Second registration with same telephone
        $data['nom'] = 'Dupont';
        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(422); // Validation error for duplicate
    }

    /**
     * Test backward compatibility: registration without type_compte defaults to simple.
     */
    public function test_backward_compatibility_default_simple_account(): void
    {
        $data = [
            'nom' => 'Default',
            'prenom' => 'User',
            'telephone' => '775678901',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'GH123456789',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            // No type_compte specified
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(200);

        $user = User::where('telephone', '775678901')->first();
        $compte = $user->client->comptes()->first();
        $this->assertEquals(Compte::TYPE_SIMPLE, $compte->type);
        $this->assertNull($compte->code_marchand);
    }
}
