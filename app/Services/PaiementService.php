<?php

namespace App\Services;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\AccountNotFoundException;
use App\Events\TransactionCreated;
use App\Services\BalanceCacheService;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class PaiementService
{
    public function __construct(
        private BalanceCacheService $balanceCache,
        private SmsService $smsService
    ) {}

    /**
     * Effectuer un paiement vers un marchand via son code_marchand
     */
    public function payerMarchand(User $client, string $codeMarchand, float $montant): array
    {
        return DB::transaction(function () use ($client, $codeMarchand, $montant) {
            // Récupérer le compte client avec lock pessimiste
            $clientCompte = $client->client->comptes()->lockForUpdate()->first();

            if (!$clientCompte) {
                throw new AccountNotFoundException('Aucun compte trouvé pour ce client');
            }

            // Récupérer le compte marchand avec lock pessimiste
            $marchandCompte = Compte::where('code_marchand', $codeMarchand)->lockForUpdate()->first();

            if (!$marchandCompte) {
                throw new AccountNotFoundException('Code marchand invalide');
            }

            // Vérifier que le compte marchand est actif
            if ($marchandCompte->statut !== 'actif') {
                throw new \InvalidArgumentException('Le compte marchand n\'est pas actif');
            }

            // Vérifier que le marchand a un type 'marchand'
            if ($marchandCompte->type !== Compte::TYPE_MARCHAND) {
                throw new \InvalidArgumentException('Ce compte n\'est pas un compte marchand');
            }

            // Vérifier que le client et le marchand ne sont pas la même personne
            if ($client->id === $marchandCompte->client->user->id) {
                throw new \InvalidArgumentException('Impossible de payer son propre compte marchand');
            }

            // Vérifier le solde du client
            $clientSolde = $clientCompte->calculerSolde();
            if ($clientSolde < $montant) {
                throw new InsufficientFundsException();
            }

            $reference = Transaction::genererReference();

            // Transaction de débit pour le client (sortante)
            $debitTransaction = Transaction::create([
                'user_id' => $client->id,
                'compte_id' => $clientCompte->id,
                'type' => 'transfert',
                'montant' => $montant,
                'statut' => 'reussi',
                'date_operation' => now(),
                'destinataire_id' => $marchandCompte->id,
                'description' => "Paiement marchand - Code: {$codeMarchand}",
                'reference' => $reference . 'D', // D pour débit
            ]);

            // Transaction de crédit pour le marchand (entrante)
            $creditTransaction = Transaction::create([
                'user_id' => $marchandCompte->client->user->id,
                'compte_id' => $marchandCompte->id,
                'type' => 'transfert',
                'montant' => $montant,
                'statut' => 'reussi',
                'date_operation' => now(),
                'destinataire_id' => null, // Pour les transactions de crédit (reçues)
                'description' => "Paiement reçu - Client: {$client->nom} {$client->prenom}",
                'reference' => $reference . 'C', // C pour crédit
            ]);

            Log::info('Paiement marchand effectué', [
                'client_id' => $client->id,
                'marchand_id' => $marchandCompte->client->user->id,
                'code_marchand' => $codeMarchand,
                'montant' => $montant,
                'reference' => $reference,
            ]);

            Event::dispatch(new TransactionCreated($debitTransaction));
            Event::dispatch(new TransactionCreated($creditTransaction));

            // Invalidate balance caches
            $this->balanceCache->invalidateBalances([$clientCompte->id, $marchandCompte->id]);

            // Envoyer SMS de confirmation
            $this->envoyerSmsConfirmation($client, $marchandCompte, $montant, $reference);

            return [
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction,
                'reference' => $reference,
                'solde_client' => $clientSolde - $montant,
            ];
        });
    }

    /**
     * Envoyer SMS de confirmation
     */
    private function envoyerSmsConfirmation(User $client, Compte $marchandCompte, float $montant, string $reference): void
    {
        $marchandUser = $marchandCompte->client->user;

        // SMS au client
        $messageClient = "Paiement de {$montant} FCFA effectué vers {$marchandUser->nom} {$marchandUser->prenom}. Référence: {$reference}. Solde restant: " . ($client->client->comptes()->first()->calculerSolde()) . " FCFA.";
        $this->smsService->sendSms($client->telephone, $messageClient);

        // SMS au marchand
        $messageMarchand = "Paiement de {$montant} FCFA reçu de {$client->nom} {$client->prenom}. Référence: {$reference}. Nouveau solde: " . ($marchandCompte->calculerSolde()) . " FCFA.";
        $this->smsService->sendSms($marchandUser->telephone, $messageMarchand);
    }
}