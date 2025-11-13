<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OmpayController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// OMPAY Routes
Route::prefix('ompay')->group(function () {
    // Inscription (2 étapes)
    Route::post('send-verification', [OmpayController::class, 'sendVerification']);
    Route::post('register', [OmpayController::class, 'register']);

    // Authentification
    Route::post('login', [OmpayController::class, 'login']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        // Transactions
        Route::post('deposit', [OmpayController::class, 'deposit']);
        Route::post('withdraw', [OmpayController::class, 'withdraw']);
        Route::post('transfer', [OmpayController::class, 'transfer']);

        // Consultations
        Route::get('balance/{compteId}', [OmpayController::class, 'getBalance']);
        Route::get('balance', [OmpayController::class, 'getBalance']); // Added for consistency
        Route::get('history', [OmpayController::class, 'getHistory']); // Added for consistency
        Route::get('transactions/{compteId}', [OmpayController::class, 'getTransactions']);

        // Anciennes routes (maintenues pour compatibilité)
        Route::get('wallet/balance', [OmpayController::class, 'getBalance']);
        Route::post('wallet/transfer', [OmpayController::class, 'transfer']);
        Route::get('wallet/history', [OmpayController::class, 'getHistory']);
        Route::post('logout', [OmpayController::class, 'logout']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

    Route::middleware(['auth:sanctum', 'logging'])->prefix('v1')->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('admin/dashboard', [AdminController::class, 'dashboard']);
            Route::apiResource('users', UserController::class);
        });

        Route::apiResource('comptes', CompteController::class)
            ->middleware('can:viewAny,App\Models\Compte');

        Route::get('comptes/{compte}/transactions', [CompteController::class, 'transactions'])
            ->middleware('can:viewTransactions,compte');
    });




 

























Route::prefix('oauth')->group(function () {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->middleware(['throttle:60,1'])
        ->name('passport.token');

    Route::get('/authorize', [AuthorizationController::class, 'authorize'])
        ->name('passport.authorizations.authorize');

    Route::post('/authorize', [ApproveAuthorizationController::class, 'approve'])
        ->name('passport.authorizations.approve');

    Route::delete('/authorize', [DenyAuthorizationController::class, 'deny'])
        ->name('passport.authorizations.deny');

    Route::post('/personal-access-tokens', [PersonalAccessTokenController::class, 'store'])
        ->name('passport.personal.tokens');

    Route::get('/token/refresh', [TransientTokenController::class, 'refresh'])
        ->middleware('auth:api')
        ->name('passport.token.refresh');
});
