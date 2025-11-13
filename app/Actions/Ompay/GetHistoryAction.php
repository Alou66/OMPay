<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;

class GetHistoryAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Récupérer l'historique des transactions
     */
    public function __invoke(): array
    {
        $user = Auth::user();
        $compte = $user->client->comptes()->first();

        if (!$compte) {
            throw new \Exception('Aucun compte trouvé');
        }

        return $this->transactionService->getTransactionHistory($compte->id);
    }
}