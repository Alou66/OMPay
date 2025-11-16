<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
    }
    /**
     * Send OTP via Twilio SMS
     *
     * @param string $telephone
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $telephone, string $otp): bool
    {
        // Always log the OTP
        Log::info("📱 SMS OMPAY - OTP généré", [
            'destinataire' => $telephone,
            'otp_code' => $otp,
            'message' => "Votre code de vérification OMPAY est : {$otp}",
            'validite' => '5 minutes',
            'timestamp' => now()->toISOString()
        ]);

        try {
            if (!env('TWILIO_ENABLED', false)) {
                Log::info("📱 SMS OMPAY - Envoi SMS désactivé (TWILIO_ENABLED=false)", [
                    'destinataire' => $telephone,
                    'otp_code' => $otp,
                    'timestamp' => now()->toISOString()
                ]);
                return true;
            }

            // Ensure telephone is in international format for Twilio
            $to = $telephone;
            if (!str_starts_with($telephone, '+')) {
                $to = '+221' . $telephone;
            }

            $message = $this->client->messages->create(
                $to,
                [
                    'from' => env('TWILIO_FROM'),
                    'body' => "Votre code de vérification OMPAY est : {$otp}"
                ]
            );

            Log::info("📱 SMS OMPAY - OTP envoyé via Twilio", [
                'destinataire' => $telephone,
                'otp_code' => $otp,
                'message_sid' => $message->sid,
                'status' => $message->status,
                'validite' => '5 minutes',
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS OTP via Twilio", [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send generic SMS via Twilio
     *
     * @param string $telephone
     * @param string $message
     * @return bool
     */
    public function sendSms(string $telephone, string $message): bool
    {
        // Always log the SMS attempt
        Log::info("📱 SMS Générique - Tentative d'envoi", [
            'destinataire' => $telephone,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ]);

        try {
            if (!env('TWILIO_ENABLED', false)) {
                Log::info("📱 SMS Générique - Envoi SMS désactivé (TWILIO_ENABLED=false)", [
                    'destinataire' => $telephone,
                    'message' => $message,
                    'timestamp' => now()->toISOString()
                ]);
                return true;
            }

            // Ensure telephone is in international format for Twilio
            $to = $telephone;
            if (!str_starts_with($telephone, '+')) {
                $to = '+221' . $telephone;
            }

            $sms = $this->client->messages->create(
                $to,
                [
                    'from' => env('TWILIO_FROM'),
                    'body' => $message
                ]
            );

            Log::info("📱 SMS Générique envoyé via Twilio", [
                'destinataire' => $telephone,
                'message_sid' => $sms->sid,
                'status' => $sms->status,
                'timestamp' => now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi SMS générique via Twilio", [
                'telephone' => $telephone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}