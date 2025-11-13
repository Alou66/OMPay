<?php

namespace App\Actions\Ompay;

use App\Services\OmpayService;

class SendVerificationAction
{
    public function __construct(
        private OmpayService $ompayService
    ) {}

    /**
     * Envoyer un code de vérification OTP
     */
    public function __invoke(string $telephone): void
    {
        // Normalize telephone number (remove +221 prefix if present)
        $normalizedTelephone = preg_replace('/^(\+221|221)/', '', $telephone);
        $this->ompayService->sendVerificationCode($normalizedTelephone);
    }
}