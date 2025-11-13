<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Models\User;

class DepositAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Effectuer un dépôt
     */
    public function __invoke(User $user, float $amount, string $description): array
    {
        $transaction = $this->transactionService->deposit($user, $amount, $description);

        return [
            'transaction' => $transaction,
            'reference' => $transaction->reference,
        ];
    }
}