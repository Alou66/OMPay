<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OTP Manager - Gestion centralisée des codes OTP
 *
 * Fonctionnalités :
 * - Génération d'OTP 6 chiffres
 * - Expiration 5 minutes
 * - Un seul OTP actif par utilisateur
 * - Rate limiting par utilisateur/IP
 * - Suppression automatique après usage
 */
class OTPManager
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS_PER_HOUR = 3;
    private const CACHE_PREFIX = 'otp_attempts_';

    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * Génère et envoie un OTP pour un numéro de téléphone
     *
     * @param string $telephone Numéro de téléphone normalisé
     * @return OtpCode
     * @throws \Exception
     */
    public function generateAndSendOTP(string $telephone): OtpCode
    {
        // Vérifier le rate limiting
        $this->checkRateLimit($telephone);

        // Invalider les OTP précédents pour cet utilisateur
        $this->invalidatePreviousOTPs($telephone);

        // Générer le code OTP
        $otpCode = $this->generateOTPCode();

        // Créer l'enregistrement OTP
        $otpRecord = OtpCode::create([
            'telephone' => $telephone,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        // Envoyer par SMS via job
        try {
            \App\Jobs\SendSms::dispatch($telephone, "Votre code OTP OMPAY: {$otpCode}. Valide 5 minutes.");
            Log::info("Job OTP envoyé au numéro {$telephone}");
        } catch (\Exception $e) {
            Log::error("Échec dispatch job OTP SMS: {$e->getMessage()}");
            // Supprimer l'OTP si l'envoi échoue
            $otpRecord->delete();
            throw new \Exception('Erreur lors de l\'envoi du SMS OTP');
        }

        // Incrémenter le compteur de tentatives
        $this->incrementAttempts($telephone);

        return $otpRecord;
    }

    /**
     * Vérifie un code OTP
     *
     * @param string $telephone
     * @param string $otpCode
     * @return bool
     */
    public function verifyOTP(string $telephone, string $otpCode): bool
    {
        $otpRecord = OtpCode::active()
            ->where('telephone', $telephone)
            ->where('otp_code', $otpCode)
            ->first();

        if ($otpRecord) {
            // Marquer comme utilisé et supprimer
            $otpRecord->update(['used' => DB::raw('true')]);
            Log::info("OTP vérifié avec succès pour {$telephone}");
            return true;
        }

        Log::warning("Tentative de vérification OTP échouée pour {$telephone}");
        return false;
    }

    /**
     * Vérifie si un utilisateur peut recevoir un OTP
     *
     * @param string $telephone
     * @return bool
     */
    public function canSendOTP(string $telephone): bool
    {
        return !$this->isRateLimited($telephone);
    }

    /**
     * Nettoie les OTP expirés (peut être appelé par un job)
     */
    public function cleanupExpiredOTPs(): void
    {
        $count = OtpCode::where('expires_at', '<', now())
            ->whereRaw('used = false')
            ->delete();

        if ($count > 0) {
            Log::info("Nettoyé {$count} OTP expirés");
        }
    }

    /**
     * Génère un code OTP aléatoire
     */
    private function generateOTPCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifie le rate limiting
     */
    private function checkRateLimit(string $telephone): void
    {
        if ($this->isRateLimited($telephone)) {
            throw new \Exception('Trop de tentatives. Veuillez réessayer dans une heure.');
        }
    }

    /**
     * Vérifie si l'utilisateur est rate limité
     */
    private function isRateLimited(string $telephone): bool
    {
        $attempts = Cache::get(self::CACHE_PREFIX . $telephone, 0);
        return $attempts >= self::MAX_ATTEMPTS_PER_HOUR;
    }

    /**
     * Incrémente le compteur de tentatives
     */
    private function incrementAttempts(string $telephone): void
    {
        $key = self::CACHE_PREFIX . $telephone;
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, now()->addHour());
    }

    /**
     * Invalide les OTP précédents
     */
    private function invalidatePreviousOTPs(string $telephone): void
    {
        OtpCode::where('telephone', $telephone)
            ->whereRaw('used = false')
            ->update(['used' => DB::raw('true')]);
    }
}