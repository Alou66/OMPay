<?php

namespace App\Actions\User;

use App\Models\User;

class UpdateUserAction
{
    /**
     * Mettre à jour un utilisateur
     */
    public function __invoke(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }
}