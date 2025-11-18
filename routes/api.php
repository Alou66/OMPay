<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OmpayController;
use App\Http\Controllers\PaiementController;

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

// Auth Routes
Route::prefix('auth')->middleware('otp.rate.limit')->group(function () {
    Route::post('register', [OmpayController::class, 'register']);
    Route::post('request-otp', [OmpayController::class, 'requestOTP']);
    Route::post('verify-otp', [OmpayController::class, 'verifyOTP']);
    Route::post('login', [OmpayController::class, 'login']);
    Route::post('refresh', [OmpayController::class, 'refreshToken']);
});

// OMPAY Routes (Transactions)
Route::prefix('ompay')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Transactions
    Route::post('deposit', [OmpayController::class, 'deposit']);
    Route::post('withdraw', [OmpayController::class, 'withdraw']);
    Route::post('transfer', [OmpayController::class, 'transfer']);

    // Consultations
    Route::get('balance', [OmpayController::class, 'getBalance']);
    Route::get('history', [OmpayController::class, 'getHistory']);
    Route::get('transactions/{compteId}', [OmpayController::class, 'getTransactions']);

    // Déconnexion
    Route::post('logout', [OmpayController::class, 'logout']);
});

// PAIEMENT Routes
Route::prefix('paiement')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('marchand', [PaiementController::class, 'payerMarchand']);
});

// Dashboard Route
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [OmpayController::class, 'dashboard']);
});
