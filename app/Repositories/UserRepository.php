<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository pour la gestion des utilisateurs
 */
class UserRepository
{
    /**
     * Trouve un utilisateur par téléphone
     */
    public function findByTelephone(string $telephone): ?User
    {
        return User::where('telephone', $telephone)->first();
    }

    /**
     * Trouve un utilisateur par ID
     */
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    /**
     * Vérifie si un téléphone existe déjà
     */
    public function telephoneExists(string $telephone): bool
    {
        return User::where('telephone', $telephone)->exists();
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Trouve les utilisateurs avec un statut spécifique
     */
    public function findByStatus(string $status): Collection
    {
        return User::where('status', $status)->get();
    }

    /**
     * Trouve les utilisateurs actifs et vérifiés
     */
    public function findActiveAndVerified(): Collection
    {
        return User::where('status', 'Actif')
            ->where('is_verified', true)
            ->get();
    }

    /**
     * Trouve les utilisateurs en attente de vérification
     */
    public function findPendingVerification(): Collection
    {
        return User::where('status', 'pending_verification')
            ->where('is_verified', false)
            ->get();
    }
}