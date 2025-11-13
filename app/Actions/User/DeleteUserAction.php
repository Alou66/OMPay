<?php

namespace App\Actions\User;

use App\Models\User;

class DeleteUserAction
{
    /**
     * Supprimer un utilisateur
     */
    public function __invoke(User $user): bool
    {
        return $user->delete();
    }
}