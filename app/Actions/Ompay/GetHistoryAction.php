<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Exceptions\AccountNotFoundException;
use Illuminate\Support\Facades\Auth;

class GetHistoryAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Récupérer l'historique des transactions avec pagination et filtrage
     */
    public function __invoke(int $page = 1, int $perPage = 20, ?string $type = null): array
    {
        $user = Auth::user();
        $compte = $user->client->comptes()->first();

        if (!$compte) {
            throw new AccountNotFoundException();
        }

        return $this->transactionService->getTransactionHistoryPaginated($compte->id, $page, $perPage, $type);
    }
}