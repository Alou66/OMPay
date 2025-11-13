<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;

class GetTransactionsAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Récupérer l'historique des transactions d'un compte
     */
    public function __invoke(string $compteId): array
    {
        return $this->transactionService->getTransactionHistory($compteId);
    }
}