<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    /**
     * Créer un utilisateur
     */
    public function __invoke(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }
}