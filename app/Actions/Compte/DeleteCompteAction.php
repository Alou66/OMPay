<?php

namespace App\Actions\Compte;

use App\Models\Compte;

class DeleteCompteAction
{
    /**
     * Fermer et supprimer un compte
     */
    public function __invoke(Compte $compte): array
    {
        if ($compte->statut === 'fermé') {
            throw new \Exception('Ce compte est déjà fermé');
        }

        $compte->delete();

        return [
            'id' => $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'statut' => $compte->statut,
            'dateFermeture' => $compte->date_fermeture?->toISOString(),
        ];
    }
}