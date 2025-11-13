<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        Route::get('balance', [OmpayController::class, 'getBalance']);
        Route::get('history', [OmpayController::class, 'getHistory']);

        // Déconnexion
        Route::post('logout', [OmpayController::class, 'logout']);
    });
});
