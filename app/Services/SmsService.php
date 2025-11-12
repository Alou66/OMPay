<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send OTP via simulated SMS (Laravel Log)
     *
     * @param string $telephone
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $telephone, string $otp): bool
    {
        try {
            // Simulation d'envoi SMS via Log Laravel
            Log::info("📱 SMS OMPAY - OTP envoyé", [
                'destinataire' => $telephone,
                'message' => "Votre code de vérification OMPAY est : {$otp}",
                'validite' => '5 minutes',
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS OTP", [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send generic SMS (for future use)
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    public function sendSms(string $telephone, string $message): bool
    {
        try {
            Log::info("📱 SMS Générique envoyé", [
                'destinataire' => $telephone,
                'message' => $message,
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS générique", [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}