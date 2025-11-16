<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTransaction implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $transaction = $event->transaction;

        Log::channel('audit')->info('Transaction créée', [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'montant' => $transaction->montant,
            'user_id' => $transaction->user_id,
            'compte_id' => $transaction->compte_id,
            'reference' => $transaction->reference,
            'date_operation' => $transaction->date_operation,
        ]);
    }
}
