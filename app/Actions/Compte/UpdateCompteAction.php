<?php

namespace App\Actions\Compte;

use App\Models\Compte;
use App\Services\CompteService;

class UpdateCompteAction
{
    public function __construct(
        private CompteService $compteService
    ) {}

    /**
     * Mettre à jour les informations client d'un compte
     */
    public function __invoke(Compte $compte, array $data): Compte
    {
        $this->compteService->updateClientInfo($compte, $data);
        return $compte;
    }
}