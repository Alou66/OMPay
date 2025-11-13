<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Models\User;

class WithdrawAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Effectuer un retrait
     */
    public function __invoke(User $user, float $amount, string $description): array
    {
        $transaction = $this->transactionService->withdraw($user, $amount, $description);

        return [
            'transaction' => $transaction,
            'reference' => $transaction->reference,
        ];
    }
}