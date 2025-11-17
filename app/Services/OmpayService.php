<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use App\Models\OtpCode;
use App\Models\Transaction;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OmpayService
{
    protected $smsService;
    protected $transactionService;

    public function __construct(SmsService $smsService, TransactionService $transactionService)
    {
        $this->smsService = $smsService;
        $this->transactionService = $transactionService;
    }

    public function sendVerificationCode(string $telephone): OtpCode
    {
        // Invalidate previous codes
        OtpCode::where('telephone', $telephone)->update(['used' => DB::raw('true')]);

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $otpRecord = OtpCode::create([
            'telephone' => $telephone,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP via SMS (simulated)
        $this->smsService->sendOtp($telephone, $otp);

        return $otpRecord;
    }

    public function verifyOtp(string $telephone, string $otp): bool
    {
        $otpRecord = OtpCode::active()
            ->where('telephone', $telephone)
            ->where('otp_code', $otp)
            ->first();

        if ($otpRecord) {
            $otpRecord->update(['used' => DB::raw('true')]);
            return true;
        }

        return false;
    }

    public function register(array $data): User
    {
        if (User::where('login', $data['telephone'])->exists()) {
            throw new ApiException('Un utilisateur avec ce numéro de téléphone existe déjà.', 400);
        }

        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'login' => $data['telephone'], // Use telephone as login for OMPAY users
            'telephone' => $data['telephone'],
            'password' => Hash::make($data['password']),
            'status' => 'Actif',
            'cni' => $data['cni'],
            'code' => 'OMPAY' . rand(1000, 9999), // Generate a code
            'sexe' => $data['sexe'],
            'date_naissance' => $data['date_naissance'],
            'role' => 'client',
            'is_verified' => true,
        ]);

        $client = Client::create([
            'user_id' => $user->id,
        ]);

        // Create default account
        Compte::create([
            'client_id' => $client->id,
            'numero_compte' => 'OM' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'type' => Compte::TYPE_MARCHAND,
            'statut' => 'actif',
        ]);

        return $user;
    }

    /**
     * @deprecated Utilisez TransactionService::getBalance() à la place
     */
    public function getBalance(User $user): float
    {
        $compte = $user->client->comptes()->first();
        return $compte ? $compte->calculerSolde() : 0;
    }

    /**
     * @deprecated Utilisez TransactionService::transfer() à la place
     */
    public function transfer(User $sender, string $recipientTelephone, float $amount): array
    {
        // Déléguer au TransactionService
        return $this->transactionService->transfer($sender, $recipientTelephone, $amount);
    }

    /**
     * @deprecated Utilisez TransactionService::getTransactionHistory() à la place
     */
    public function getHistory(User $user): array
    {
        $compte = $user->client->comptes()->first();
        if (!$compte) return [];

        return $compte->transactions()
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->toArray();
    }
}