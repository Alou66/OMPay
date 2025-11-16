<?php

namespace App\Services;

use App\Models\Compte;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BalanceCacheService
{
    private const CACHE_PREFIX = 'balance_';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get cached balance or calculate and cache it
     */
    public function getBalance(string $compteId): float
    {
        $cacheKey = self::CACHE_PREFIX . $compteId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($compteId) {
            $compte = Compte::findOrFail($compteId);
            $balance = $compte->calculerSolde();

            Log::info("Balance calculated and cached for compte {$compteId}: {$balance}");

            return $balance;
        });
    }

    /**
     * Invalidate balance cache for a compte
     */
    public function invalidateBalance(string $compteId): void
    {
        $cacheKey = self::CACHE_PREFIX . $compteId;
        Cache::forget($cacheKey);

        Log::info("Balance cache invalidated for compte {$compteId}");
    }

    /**
     * Invalidate balances for multiple comptes
     */
    public function invalidateBalances(array $compteIds): void
    {
        foreach ($compteIds as $compteId) {
            $this->invalidateBalance($compteId);
        }
    }

    /**
     * Warm up balance cache for active comptes
     */
    public function warmupBalances(): void
    {
        $activeComptes = Compte::where('statut', 'actif')->pluck('id');

        foreach ($activeComptes as $compteId) {
            $this->getBalance($compteId);
        }

        Log::info("Balance cache warmed up for {$activeComptes->count()} comptes");
    }
}