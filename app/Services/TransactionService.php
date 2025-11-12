<?php

namespace App\Services;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AccountNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
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

            return $transaction;
        });
    }

    /**
     * Effectuer un transfert entre comptes
     */
    public function transfer(User $sender, string $recipientTelephone, float $amount, string $description = null): array
    {
        return DB::transaction(function () use ($sender, $recipientTelephone, $amount, $description) {
            // Récupérer les comptes
            $senderCompte = $sender->client->comptes()->first();
            $recipientUser = User::where('telephone', $recipientTelephone)->first();

            if (!$recipientUser) {
                throw new AccountNotFoundException('Utilisateur destinataire introuvable');
            }

            if ($sender->id === $recipientUser->id) {
                throw new \InvalidArgumentException('Impossible de transférer vers son propre compte');
            }

            $recipientCompte = $recipientUser->client->comptes()->first();

            if (!$senderCompte || !$recipientCompte) {
                throw new AccountNotFoundException('Compte introuvable');
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
                'description' => $description,
                'reference' => $reference . 'C', // C pour crédit
            ]);

            Log::info('Transfert effectué', [
                'expediteur_id' => $sender->id,
                'destinataire_id' => $recipientUser->id,
                'montant' => $amount,
                'reference' => $reference,
            ]);

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
        $solde = $compte->calculerSolde();

        return [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'solde' => $solde,
            'devise' => 'FCFA',
            'date_consultation' => now(),
        ];
    }

    /**
     * Récupérer l'historique des transactions d'un compte
     */
    public function getTransactionHistory(string $compteId, int $limit = 50): array
    {
        $compte = Compte::findOrFail($compteId);

        $transactions = $compte->transactions()
            ->with(['user:id,nom,prenom,telephone'])
            ->orderBy('date_operation', 'desc')
            ->take($limit)
            ->get();

        return [
            'compte_id' => $compte->id,
            'numero_compte' => $compte->numero_compte,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'statut' => $transaction->statut,
                    'date_operation' => $transaction->date_operation,
                    'description' => $transaction->description,
                    'reference' => $transaction->reference,
                    'user' => $transaction->user ? [
                        'nom' => $transaction->user->nom,
                        'prenom' => $transaction->user->prenom,
                        'telephone' => $transaction->user->telephone,
                    ] : null,
                ];
            }),
            'total' => $transactions->count(),
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