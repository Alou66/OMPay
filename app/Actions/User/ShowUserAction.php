<?php

namespace App\Actions\User;

use App\Models\User;

class ShowUserAction
{
    /**
     * Afficher un utilisateur
     */
    public function __invoke(User $user): User
    {
        return $user;
    }
}