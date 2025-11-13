<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Exceptions\AccountNotFoundException;
use Illuminate\Support\Facades\Auth;

class GetBalanceAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Récupérer le solde d'un compte
     */
    public function __invoke(?string $compteId = null): array
    {
        if (!$compteId) {
            $user = Auth::user();
            $compte = $user->client->comptes()->first();

            if (!$compte) {
                throw new AccountNotFoundException();
            }

            $compteId = $compte->id;
        }

        return $this->transactionService->getBalance($compteId);
    }
}