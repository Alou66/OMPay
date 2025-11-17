<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * Get dashboard data for authenticated user
     */
    public function getDashboardData(User $user): array
    {
        // Get user info
        $userData = [
            'id' => $user->id,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'telephone' => $user->telephone,
            'email' => $user->email,
        ];

        // Get associated compte (assuming one compte per user via client)
        $compte = $user->client?->comptes()->first();

        $compteData = null;
        if ($compte) {
            $compteData = [
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'type' => $compte->type,
                'statut' => $compte->statut,
                'solde' => $compte->solde,
            ];
        }

        // Get last 10 transactions
        $transactions = collect([]);
        if ($compte) {
            $transactions = $compte->transactions()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'montant' => $transaction->montant,
                        'type' => $transaction->type,
                        'date_creation' => $transaction->created_at->toISOString(),
                    ];
                });
        }

        return [
            'user' => $userData,
            'compte' => $compteData,
            'transactions' => $transactions,
        ];
    }
}