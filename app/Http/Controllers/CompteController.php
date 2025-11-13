<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Actions\Compte\ListComptesAction;
use App\Actions\Compte\ShowCompteAction;
use App\Actions\Compte\CreateCompteAction;
use App\Actions\Compte\UpdateCompteAction;
use App\Actions\Compte\DeleteCompteAction;
use App\Actions\Compte\GetCompteTransactionsAction;
use App\Http\Resources\CompteResource;
use App\Http\Requests\CreateCompteRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\ControllerHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CompteController extends Controller
{
    use ApiResponseTrait, ControllerHelperTrait;

    public function __construct(
        private ListComptesAction $listComptesAction,
        private ShowCompteAction $showCompteAction,
        private CreateCompteAction $createCompteAction,
        private UpdateCompteAction $updateCompteAction,
        private DeleteCompteAction $deleteCompteAction,
        private GetCompteTransactionsAction $getCompteTransactionsAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('can-access-bank-operations');
        $this->authorizeAction('viewAny', Compte::class);

        return $this->tryAction(function () use ($request) {
            $validated = $request->validate(['limit' => 'nullable|integer|min:1|max:100']);

            $listComptesAction = $this->listComptesAction;
            $comptes = $listComptesAction($validated['limit'] ?? 10);

            return $this->paginatedResponse(
                CompteResource::collection($comptes),
                $comptes,
                'Comptes récupérés avec succès'
            );
        });
    }

    public function show(string $compteId): JsonResponse
    {
        if (!$this->validateUuid($compteId, 'ID du compte')) {
            return $this->errorResponse('ID du compte invalide', 400);
        }

        $compte = $this->findOrFail(Compte::class, $compteId, 'Compte');
        $this->authorizeAction('view', $compte);

        return $this->tryAction(function () use ($compte) {
            $showCompteAction = $this->showCompteAction;
            $compte = $showCompteAction($compte);

            return $this->successResponse(
                new CompteResource($compte),
                'Compte récupéré avec succès'
            );
        });
    }

    public function store(CreateCompteRequest $request): JsonResponse
    {
        $this->authorizeAction('create', Compte::class);

        return $this->tryAction(function () use ($request) {
            $createCompteAction = $this->createCompteAction;
            $compte = $createCompteAction($request->validated());

            return $this->successResponse(
                new CompteResource($compte),
                'Compte bancaire créé avec succès',
                201
            );
        });
    }

    public function update(UpdateClientRequest $request, string $compteId): JsonResponse
    {
        if (!$this->validateUuid($compteId, 'ID du compte')) {
            return $this->errorResponse('ID du compte invalide', 400);
        }

        $compte = $this->findOrFail(Compte::class, $compteId, 'Compte');
        $this->authorizeAction('update', $compte);

        return $this->tryAction(function () use ($compte, $request) {
            $updateCompteAction = $this->updateCompteAction;
            $compte = $updateCompteAction($compte, $request->validated());

            return $this->successResponse(
                new CompteResource($compte),
                'Informations du client mises à jour avec succès'
            );
        });
    }


    public function destroy(string $id): JsonResponse
    {
        if (!$this->validateUuid($id, 'ID du compte')) {
            return $this->errorResponse('ID du compte invalide', 400);
        }

        $compte = $this->findOrFail(Compte::withoutGlobalScopes(), $id, 'Compte');
        $this->authorizeAction('delete', $compte);

        return $this->tryAction(function () use ($compte) {
            $deleteCompteAction = $this->deleteCompteAction;
            $result = $deleteCompteAction($compte);

            return $this->successResponse($result, 'Compte fermé et supprimé avec succès');
        });
    }

    public function transactions(string $compteId): JsonResponse
    {
        if (!$this->validateUuid($compteId, 'ID du compte')) {
            return $this->errorResponse('ID du compte invalide', 400);
        }

        $compte = $this->findOrFail(Compte::class, $compteId, 'Compte');
        $this->authorizeAction('viewTransactions', $compte);

        return $this->tryAction(function () use ($compte) {
            $getCompteTransactionsAction = $this->getCompteTransactionsAction;
            $transactions = $getCompteTransactionsAction($compte);

            return $this->paginatedResponse(
                $transactions,
                $transactions,
                'Transactions récupérées avec succès'
            );
        });
    }

}