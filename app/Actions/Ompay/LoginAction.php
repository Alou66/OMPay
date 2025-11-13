<?php

namespace App\Actions\Ompay;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginAction
{
    /**
     * Connecter un utilisateur OMPAY
     */
    public function __invoke(array $credentials): array
    {
        // Normalize telephone number (remove +221 prefix if present)
        $credentials['telephone'] = preg_replace('/^(\+221|221)/', '', $credentials['telephone']);

        if (!Auth::attempt($credentials)) {
            throw new \Exception('Identifiants invalides');
        }

        $user = Auth::user();
        /** @var User $user */
        $token = $user->createToken('OMPAY Access');

        // Get user's account information
        $compte = $user->client?->comptes()->first();

        return [
            'user' => $user,
            'compte' => $compte ? [
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'type' => $compte->type,
                'statut' => $compte->statut,
            ] : null,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }
}