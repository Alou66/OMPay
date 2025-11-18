<?php

namespace App\Services;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AccountNotFoundException;
use App\Events\TransactionCreated;
use App\Services\BalanceCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class TransactionService
{
    public function __construct(
        private BalanceCacheService $balanceCache
    ) {}
    /**
     * Effectuer un dépôt sur un compte
     */
    public function deposit(User $user, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $compte = $user->client->comptes()->first();

            if (!$compte) {
                throw new AccountNotFoundException('Aucun compte trouvé pour cet utilisateur');
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => $amount,
                'statut' => 'reussi',
                'date_operation' => now(),
                'description' => $description,
                'reference' => Transaction::genererReference(),
            ]);

            Log::info('Dépôt effectué', [
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'montant' => $amount,
                'reference' => $transaction->reference,
            ]);

            Event::dispatch(new TransactionCreated($transaction));

            // Invalidate balance cache
            $this->balanceCache->invalidateBalance($compte->id);

            return $transaction;
        });
    }

    /**
     * Effectuer un retrait depuis un compte
     */
    public function withdraw(User $user, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $compte = $user->client->comptes()->first();

            if (!$compte) {
                throw new AccountNotFoundException('Aucun compte trouvé pour cet utilisateur');
            }

            $solde = $compte->calculerSolde();

            if ($solde < $amount) {
                throw new InsufficientFundsException();
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'type' => 'retrait',
                'montant' => $amount,
                'statut' => 'reussi',
                'date_operation' => now(),
                'description' => $description,
                'reference' => Transaction::genererReference(),
            ]);

            Log::info('Retrait effectué', [
                'user_id' => $user->id,
                'compte_id' => $compte->id,
                'montant' => $amount,
                'solde_apres' => $solde - $amount,
                'reference' => $transaction->reference,
            ]);

            Event::dispatch(new TransactionCreated($transaction));

            // Invalidate balance cache
            $this->balanceCache->invalidateBalance($compte->id);

            return $transaction;
        });
    }

    /**
     * Effectuer un transfert entre comptes
     */
    public function transfer(User $sender, string $recipientTelephone, float $amount, string $description = null): array
    {
        return DB::transaction(function () use ($sender, $recipientTelephone, $amount, $description) {
            // Récupérer les comptes avec lock pessimiste
            $senderCompte = $sender->client->comptes()->lockForUpdate()->first();
            $recipientUser = User::where('telephone', $recipientTelephone)->first();
            $recipientCompte = $recipientUser->client->comptes()->lockForUpdate()->first();

            if (!$recipientUser) {
                throw new AccountNotFoundException('Utilisateur destinataire introuvable');
            }

            if ($sender->id === $recipientUser->id) {
                throw new \InvalidArgumentException('Impossible de transférer vers son propre compte');
            }

            if (!$senderCompte || !$recipientCompte) {
                throw new AccountNotFoundException('Compte introuvable');
            }

            // Vérifier que le compte destinataire est actif
            if ($recipientCompte->statut !== 'actif') {
                throw new \InvalidArgumentException('Le compte destinataire n\'est pas actif');
            }

            // Vérifier le solde
            $senderBalance = $senderCompte->calculerSolde();
            if ($senderBalance < $amount) {
                throw new InsufficientFundsException();
            }

            $reference = Transaction::genererReference();

            // Transaction de débit pour l'expéditeur
            $debitTransaction = Transaction::create([
                'user_id' => $sender->id,
                'compte_id' => $senderCompte->id,
                'type' => 'transfert',
                'montant' => $amount,
                'statut' => 'reussi',
                'date_operation' => now(),
                'destinataire_id' => $recipientCompte->id,
                'description' => $description,
                'reference' => $reference . 'D', // D pour débit
            ]);

            // Transaction de crédit pour le destinataire
            $creditTransaction = Transaction::create([
                'user_id' => $recipientUser->id,
                'compte_id' => $recipientCompte->id,
                'type' => 'transfert',
                'montant' => $amount,
                'statut' => 'reussi',
                'date_operation' => now(),
                'destinataire_id' => null, // Pour les transactions de crédit (reçues)
                'description' => $description,
                'reference' => $reference . 'C', // C pour crédit
            ]);

            Log::info('Transfert effectué', [
                'expediteur_id' => $sender->id,
                'destinataire_id' => $recipientUser->id,
                'montant' => $amount,
                'reference' => $reference,
            ]);

            Event::dispatch(new TransactionCreated($debitTransaction));
            Event::dispatch(new TransactionCreated($creditTransaction));

            // Invalidate balance caches
            $this->balanceCache->invalidateBalances([$senderCompte->id, $recipientCompte->id]);

            return [
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction,
                'reference' => $reference,
            ];
        });
    }

    /**
     * Consulter le solde d'un compte
     */
    public function getBalance(string $compteId): array
    {
        $compte = Compte::findOrFail($compteId);
        $solde = $this->balanceCache->getBalance($compteId);

        return [
            'solde' => $solde,
            'devise' => 'FCFA',
        ];
    }

    /**
     * Récupérer l'historique des transactions d'un compte
     */
    public function getTransactionHistory(string $compteId, int $limit = 50): array
    {
        $compte = Compte::findOrFail($compteId);

        $transactions = $compte->transactions()
            ->orderBy('date_operation', 'desc')
            ->take($limit)
            ->get();

        return $transactions;
    }

    /**
     * Récupérer l'historique des transactions d'un compte avec pagination et filtrage
     */
    public function getTransactionHistoryPaginated(string $compteId, int $page = 1, int $perPage = 20, ?string $type = null): array
    {
        $compte = Compte::findOrFail($compteId);

        $query = $compte->transactions()
            ->orderBy('date_operation', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ];
    }

    /**
     * Calculer les statistiques d'un compte
     */
    public function getAccountStats(string $compteId): array
    {
        $compte = Compte::findOrFail($compteId);

        $stats = [
            'total_deposits' => $compte->transactions()->where('type', 'depot')->sum('montant'),
            'total_withdrawals' => $compte->transactions()->where('type', 'retrait')->sum('montant'),
            'total_transfers_sent' => $compte->transactions()->where('type', 'transfert')->whereNotNull('destinataire_id')->sum('montant'),
            'total_transfers_received' => $compte->transactions()->where('type', 'transfert')->whereNull('destinataire_id')->sum('montant'),
            'transaction_count' => $compte->transactions()->count(),
        ];

        $stats['current_balance'] = $stats['total_deposits'] + $stats['total_transfers_received'] - $stats['total_withdrawals'] - $stats['total_transfers_sent'];

        return $stats;
    }
}