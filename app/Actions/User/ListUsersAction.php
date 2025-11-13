<?php

namespace App\Actions\User;

use App\Models\User;

class ListUsersAction
{
    /**
     * Lister les utilisateurs
     */
    public function __invoke(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::paginate(10);
    }
}