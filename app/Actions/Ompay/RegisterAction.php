<?php

namespace App\Actions\Ompay;

use App\Services\OmpayService;
use App\Models\User;

class RegisterAction
{
    public function __construct(
        private OmpayService $ompayService
    ) {}

    /**
     * Inscrire un nouvel utilisateur OMPAY
     */
    public function __invoke(array $data): array
    {
        // Normalize telephone number (remove +221 prefix if present)
        $normalizedTelephone = preg_replace('/^(\+221|221)/', '', $data['telephone']);

        if (!$this->ompayService->verifyOtp($normalizedTelephone, $data['otp'])) {
            throw new \Exception('Code OTP invalide ou expiré');
        }

        $data['telephone'] = $normalizedTelephone;
        $user = $this->ompayService->register($data);
        /** @var User $user */
        $token = $user->createToken('OMPAY Access');

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}