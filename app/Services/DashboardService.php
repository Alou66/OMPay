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
        // Get associated compte (assuming one compte per user via client)
        $compte = $user->client?->comptes()->first();

        // Get last 10 transactions
        $transactions = collect([]);
        if ($compte) {
            $transactions = $compte->transactions()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return [
            'user' => $user,
            'compte' => $compte,
            'transactions' => $transactions,
        ];
    }
}