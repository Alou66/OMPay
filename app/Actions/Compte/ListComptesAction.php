<?php

namespace App\Actions\Compte;

use App\Models\Compte;
use App\Http\Resources\CompteResource;

class ListComptesAction
{
    /**
     * Lister les comptes
     */
    public function __invoke(int $limit = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Compte::with(['client.user', 'transactions'])
            ->paginate($limit);
    }
}