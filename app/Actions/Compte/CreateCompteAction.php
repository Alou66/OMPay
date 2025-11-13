<?php

namespace App\Actions\Compte;

use App\Services\CompteService;

class CreateCompteAction
{
    public function __construct(
        private CompteService $compteService
    ) {}

    /**
     * Créer un compte
     */
    public function __invoke(array $data)
    {
        return $this->compteService->createCompte($data);
    }
}