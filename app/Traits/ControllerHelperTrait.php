<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ControllerHelperTrait
{
    /**
     * Valider un UUID et retourner une réponse d'erreur si invalide
     */
    protected function validateUuid(string $id, string $fieldName = 'ID'): bool
    {
        if (!Str::isUuid($id)) {
            abort(400, "{$fieldName} invalide");
        }
        return true;
    }

    /**
     * Récupérer un modèle ou lever une exception
     */
    protected function findOrFail($model, string $id, string $modelName = 'Ressource')
    {
        try {
            return $model::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, "{$modelName} non trouvé");
        }
    }

    /**
     * Vérifier l'autorisation et lever une exception si refusée
     */
    protected function authorizeAction(string $ability, $model = null): void
    {
        $this->authorize($ability, $model);
    }

    /**
     * Wrapper pour les opérations avec gestion d'erreur
     */
    protected function tryAction(callable $action, string $errorMessage = 'Erreur interne')
    {
        try {
            return $action();
        } catch (\Exception $e) {
            return $this->errorResponse($errorMessage, 500);
        }
    }
}