<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_and_receive_otp()
    {
        $data = [
            'nom' => 'Test',
            'prenom' => 'User',
            'telephone' => '771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cni' => 'AB123456789',
            'sexe' => 'Homme',
            'date_naissance' => '1990-01-01',
            'type_compte' => \App\Models\Compte::TYPE_SIMPLE
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Utilisateur créé avec succès – demande de vérification OTP'
                 ]);

        $this->assertDatabaseHas('users', [
            'telephone' => '771234567',
            'status' => 'pending_verification'
        ]);
    }

    /** @test */
    public function otp_verification_activates_account()
    {
        // Create user
        $user = User::factory()->create([
            'status' => 'pending_verification',
            'telephone' => '771234567',
            'is_verified' => false
        ]);

        // Create OTP
        OtpCode::create([
            'telephone' => '771234567',
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(5)
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'telephone' => '771234567',
            'otp' => '123456'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Connexion réussie'
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'tokens' => [
                             'access_token',
                             'refresh_token',
                             'token_type',
                             'expires_in'
                         ]
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'Actif',
            'is_verified' => true
        ]);
    }

    /** @test */
    public function login_with_password_works()
    {
        $user = User::factory()->create([
            'telephone' => '771234567',
            'password' => bcrypt('password123'),
            'status' => 'Actif',
            'is_verified' => true
        ]);

        $response = $this->postJson('/api/auth/login', [
            'telephone' => '771234567',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Connexion réussie'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in'
                    ]
                ]);
    }

    /** @test */
    public function account_lockout_after_failed_attempts()
    {
        $user = User::factory()->create([
            'telephone' => '771234567',
            'password' => bcrypt('password123'),
            'status' => 'Actif',
            'is_verified' => true
        ]);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'telephone' => '771234567',
                'password' => 'wrongpassword'
            ]);
        }

        // 6th attempt should be locked
        $response = $this->postJson('/api/auth/login', [
            'telephone' => '771234567',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429)
                ->assertJson([
                    'success' => false
                ]);
    }

    /** @test */
    public function otp_rate_limiting_works()
    {
        // 4 requests (1 initial + 3 retries)
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/auth/request-otp', [
                'telephone' => '771234567'
            ]);
        }

        // 5th should be rate limited
        $response = $this->postJson('/api/auth/request-otp', [
            'telephone' => '771234567'
        ]);

        $response->assertJson([
                    'success' => false
                ]);
    }
}