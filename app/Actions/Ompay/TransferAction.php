<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Models\User;

class TransferAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Effectuer un transfert
     */
    public function __invoke(User $user, string $recipientTelephone, float $amount, ?string $description = null): array
    {
        $result = $this->transactionService->transfer($user, $recipientTelephone, $amount, $description);

        return [
            'debit_transaction' => $result['debit_transaction'],
            'credit_transaction' => $result['credit_transaction'],
            'reference' => $result['reference'],
        ];
    }
}