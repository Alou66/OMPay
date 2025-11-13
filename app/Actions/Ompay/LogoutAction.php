<?php

namespace App\Actions\Ompay;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LogoutAction
{
    /**
     * Déconnecter l'utilisateur
     */
    public function __invoke(): void
    {
        $user = Auth::user();
        /** @var User $user */
        $user->currentAccessToken()->delete();
    }
}