<?php

namespace App\Actions\Compte;

use App\Models\Compte;

class GetCompteTransactionsAction
{
    /**
     * Récupérer les transactions d'un compte
     */
    public function __invoke(Compte $compte): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $compte->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
}