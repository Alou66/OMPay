<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OTPService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send OTP to telephone number.
     */
    public function sendOTP(string $telephone): OtpCode
    {
        // Rate limiting: max 3 OTP per hour per phone
        $cacheKey = "otp_attempts_{$telephone}";
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            throw new \Exception('Trop de tentatives. Veuillez réessayer dans une heure.');
        }

        // Invalidate previous codes
        OtpCode::where('telephone', $telephone)->update(['used' => DB::raw('true')]);

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $otpRecord = OtpCode::create([
            'telephone' => $telephone,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP via SMS
        $this->smsService->sendOtp($telephone, $otp);

        // Increment attempts
        Cache::put($cacheKey, $attempts + 1, now()->addHour());

        return $otpRecord;
    }

    /**
     * Verify OTP code.
     */
    public function verifyOTP(string $telephone, string $otp): bool
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

    /**
     * Check if user can request verification OTP (has pending account).
     */
    public function canSendVerificationOTP(string $telephone): bool
    {
        $user = User::where('telephone', $telephone)->first();

        return $user && $user->status === 'pending_verification' && !$user->is_verified;
    }

    /**
     * Send verification OTP for account activation.
     */
    public function sendVerificationOTP(string $telephone): OtpCode
    {
        return $this->sendOTP($telephone);
    }

    /**
     * Check if user can request login OTP (has active account).
     */
    public function canSendLoginOTP(string $telephone): bool
    {
        $user = User::where('telephone', $telephone)->first();

        return $user && $user->status === 'Actif' && $user->is_verified;
    }

    /**
     * Send login OTP for authentication.
     */
    public function sendLoginOTP(string $telephone): OtpCode
    {
        if (!$this->canSendLoginOTP($telephone)) {
            throw new \Exception('Utilisateur non trouvé ou compte non activé.');
        }

        return $this->sendOTP($telephone);
    }
}