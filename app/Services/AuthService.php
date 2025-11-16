<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\RefreshToken;
use App\Repositories\UserRepository;
use App\Services\OTPService;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service d'authentification - Gestion des utilisateurs et tokens
 */
class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private OTPManager $otpManager,
        private OTPService $otpService
    ) {}

    /**
     * Inscrit un nouvel utilisateur avec statut en attente de vérification
     * Crée automatiquement un client et un compte inactif
     */
    public function register(array $data): User
    {
        if ($this->userRepository->telephoneExists($data['telephone'])) {
            throw new ApiException('Un utilisateur avec ce numéro de téléphone existe déjà.', 409);
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepository->create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'login' => $data['telephone'],
                'telephone' => $data['telephone'],
                'password' => Hash::make($data['password']),
                'status' => 'pending_verification',
                'cni' => $data['cni'],
                'code' => 'OMPAY' . rand(1000, 9999),
                'sexe' => $data['sexe'],
                'date_naissance' => $data['date_naissance'],
                'role' => 'client',
                'is_verified' => false,
            ]);

            // Créer le client
            $client = Client::create([
                'user_id' => $user->id,
            ]);

            // Créer le compte inactif
            Compte::create([
                'client_id' => $client->id,
                'numero_compte' => 'OM' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'type' => $data['type_compte'] ?? 'cheque',
                'statut' => 'inactif',
            ]);

            DB::commit();

            // Envoyer automatiquement l'OTP d'activation
            // try {
            //     $this->requestOTP($data['telephone']);
            // } catch (\Exception $e) {
            //     // Log l'erreur mais ne pas échouer l'inscription
            //     Log::error('Erreur envoi OTP après inscription: ' . $e->getMessage());
            // }

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate access and refresh tokens for user.
     */
    public function generateTokens(User $user): array
    {
        // Create access token
        $accessToken = $user->createToken('OMPAY Access', ['*'], now()->addMinutes(15));

        // Create refresh token
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30), // 30 days
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'Bearer',
            'expires_in' => 15 * 60, // 15 minutes in seconds
        ];
    }

    /**
     * Refresh access token using refresh token with proper rotation.
     */
    public function refreshToken(string $refreshToken): array
    {
        $refreshTokenRecord = RefreshToken::active()
            ->where('token', $refreshToken)
            ->first();

        if (!$refreshTokenRecord) {
            throw new \Exception('Refresh token invalide ou expiré.');
        }

        $user = $refreshTokenRecord->user;

        // Start transaction for atomic operation
        DB::beginTransaction();
        try {
            // Revoke old refresh token
            $refreshTokenRecord->update(['revoked' => true]);

            // Delete old access tokens for this user
            $user->tokens()->delete();

            // Generate new tokens
            $newTokens = $this->generateTokens($user);

            DB::commit();
            return $newTokens;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Active le compte utilisateur après vérification OTP
     */
    public function activateAccount(User $user): void
    {
        DB::beginTransaction();
        try {
            $user->update([
                'status' => 'Actif',
                'is_verified' => true,
            ]);

            // Activer le compte bancaire
            $compte = $user->client?->comptes()->first();
            if ($compte) {
                $compte->update(['statut' => 'actif']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Demande un OTP selon le statut du compte utilisateur
     * - Si compte en attente : OTP d'activation
     * - Si compte actif : OTP de connexion
     */
    public function requestOTP(string $telephone): void
    {
        $user = $this->userRepository->findByTelephone($telephone);

        if (!$user) {
            throw new \Exception('Utilisateur non trouvé.');
        }

        if (!$this->otpManager->canSendOTP($telephone)) {
            throw new \Exception('Trop de tentatives. Veuillez réessayer dans une heure.');
        }

        $this->otpManager->generateAndSendOTP($telephone);
    }

    /**
     * Vérifie l'OTP et gère l'activation/connexion selon le contexte
     */
    public function verifyOTP(string $telephone, string $otp): array
    {
        if (!$this->otpManager->verifyOTP($telephone, $otp)) {
            throw new \Exception('Code OTP invalide ou expiré.');
        }

        $user = $this->userRepository->findByTelephone($telephone);

        if (!$user) {
            throw new \Exception('Utilisateur non trouvé.');
        }

        // Si compte en attente de vérification, l'activer
        if ($user->status === 'pending_verification' && !$user->is_verified) {
            $this->activateAccount($user);
        }

        // Générer les tokens
        $tokens = $this->generateTokens($user);

        return [
            'user' => $user,
            'tokens' => $tokens,
        ];
    }

    /**
     * Authentifie un utilisateur avec téléphone et mot de passe
     */
    public function authenticate(string $telephone, string $password): User
    {
        // Vérifier si le compte est verrouillé
        $lockoutKey = 'login_lockout_' . $telephone;
        if (Cache::has($lockoutKey)) {
            $remainingTime = Cache::get($lockoutKey) - now()->timestamp;
            throw new \Exception("Compte verrouillé. Réessayez dans {$remainingTime} secondes.");
        }

        $user = $this->userRepository->findByTelephone($telephone);

        if (!$user || !Hash::check($password, $user->password)) {
            // Incrémenter les tentatives échouées
            $attemptsKey = 'login_attempts_' . $telephone;
            $attempts = Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(30));

            if ($attempts >= 5) {
                // Verrouiller pour 15 minutes
                Cache::put($lockoutKey, now()->addMinutes(15)->timestamp, now()->addMinutes(15));
                throw new \Exception('Trop de tentatives. Compte verrouillé pour 15 minutes.');
            }

            throw new \Exception('Identifiants invalides.');
        }

        if ($user->status !== 'Actif' || !$user->is_verified) {
            throw new \Exception('Compte non activé. Veuillez vérifier votre numéro de téléphone.');
        }

        // Réinitialiser les tentatives en cas de succès
        Cache::forget('login_attempts_' . $telephone);
        Cache::forget($lockoutKey);

        return $user;
    }

    /**
     * Logout user by revoking tokens.
     */
    public function logout(User $user): void
    {
        // Revoke all access tokens
        $user->tokens()->delete();

        // Revoke all refresh tokens
        RefreshToken::where('user_id', $user->id)->update(['revoked' => true]);
    }
}