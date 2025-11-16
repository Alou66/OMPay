<?php

namespace App\Actions\Ompay;

use App\Services\TransactionService;
use App\Models\Compte;
use Illuminate\Support\Facades\Auth;

class GetTransactionsAction
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Récupérer l'historique des transactions d'un compte (appartenant à l'utilisateur)
     */
    public function __invoke(string $compteId): array
    {
        $user = Auth::user();

        // Vérifier que le compte appartient à l'utilisateur
        $compte = Compte::where('id', $compteId)
            ->whereHas('client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$compte) {
            throw new \Exception('Compte non trouvé ou accès non autorisé.');
        }

        return $this->transactionService->getTransactionHistory($compteId);
    }
}