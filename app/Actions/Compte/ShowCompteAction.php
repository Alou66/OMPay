<?php

namespace App\Actions\Compte;

use App\Models\Compte;
use App\Http\Resources\CompteResource;

class ShowCompteAction
{
    /**
     * Afficher un compte
     */
    public function __invoke(Compte $compte): Compte
    {
        $compte->load(['client.user', 'transactions' => function ($query) {
            $query->latest()->take(10);
        }]);

        return $compte;
    }
}