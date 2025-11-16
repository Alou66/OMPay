<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de rate limiting pour les endpoints OTP
 */
class OTPRateLimitMiddleware
{
    private const MAX_REQUESTS_PER_MINUTE = 5;
    private const CACHE_PREFIX = 'otp_rate_limit_';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = self::CACHE_PREFIX . $ip;

        $attempts = Cache::get($key, 0);

        if ($attempts >= self::MAX_REQUESTS_PER_MINUTE) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives OTP. Veuillez réessayer dans une minute.',
            ], 429);
        }

        // Incrémenter le compteur
        Cache::put($key, $attempts + 1, now()->addMinute());

        return $next($request);
    }
}