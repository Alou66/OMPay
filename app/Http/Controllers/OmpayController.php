<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendVerificationRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\OmpayLoginRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Services\OmpayService;
use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OmpayController extends Controller
{
    use ApiResponseTrait;

    protected $ompayService;
    protected $transactionService;

    public function __construct(OmpayService $ompayService, TransactionService $transactionService)
    {
        $this->ompayService = $ompayService;
        $this->transactionService = $transactionService;
    }

    public function sendVerification(SendVerificationRequest $request)
    {
        $otp = $this->ompayService->sendVerificationCode($request->telephone);

        return $this->successResponse(null, 'Code de vérification envoyé par SMS');
    }

    public function register(RegisterRequest $request)
    {
        if (!$this->ompayService->verifyOtp($request->telephone, $request->otp)) {
            return $this->errorResponse('Code OTP invalide ou expiré', 400);
        }

        $user = $this->ompayService->register($request->validated());

        $token = $user->createToken('OMPAY Access');

        return $this->successResponse([
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Inscription réussie');
    }

    public function login(OmpayLoginRequest $request)
    {
        if (!Auth::attempt($request->only('telephone', 'password'))) {
            return $this->errorResponse('Identifiants invalides', 401);
        }

        $user = Auth::user();
        $token = $user->createToken('OMPAY Access');

        return $this->successResponse([
            'user' => $user,
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 'Connexion réussie');
    }

    public function getBalance($compteId = null)
    {
        // Si pas de compteId fourni, utiliser le compte de l'utilisateur connecté
        if (!$compteId) {
            $user = Auth::user();
            $compte = $user->client->comptes()->first();

            if (!$compte) {
                return $this->errorResponse('Aucun compte trouvé', 404);
            }

            $compteId = $compte->id;
        }

        try {
            $balance = $this->transactionService->getBalance($compteId);
            return $this->successResponse($balance, 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function transfer(TransferRequest $request)
    {
        $user = Auth::user();

        try {
            $result = $this->transactionService->transfer(
                $user,
                $request->recipient_telephone,
                $request->amount,
                $request->description ?? null
            );

            return $this->successResponse([
                'debit_transaction' => $result['debit_transaction'],
                'credit_transaction' => $result['credit_transaction'],
                'reference' => $result['reference'],
            ], 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getHistory()
    {
        $user = Auth::user();
        $compte = $user->client->comptes()->first();

        if (!$compte) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        }

        try {
            $history = $this->transactionService->getTransactionHistory($compte->id);
            return $this->successResponse($history, 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * Effectuer un dépôt
     */
    public function deposit(DepositRequest $request)
    {
        $user = Auth::user();

        try {
            $transaction = $this->transactionService->deposit(
                $user,
                $request->amount,
                $request->description
            );

            return $this->successResponse([
                'transaction' => $transaction,
                'reference' => $transaction->reference,
            ], 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Effectuer un retrait
     */
    public function withdraw(WithdrawRequest $request)
    {
        $user = Auth::user();

        try {
            $transaction = $this->transactionService->withdraw(
                $user,
                $request->amount,
                $request->description
            );

            return $this->successResponse([
                'transaction' => $transaction,
                'reference' => $transaction->reference,
            ], 'Retrait effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Récupérer l'historique des transactions (amélioré)
     */
    public function getTransactions($compteId)
    {
        try {
            $history = $this->transactionService->getTransactionHistory($compteId);
            return $this->successResponse($history, 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}
