<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendVerificationRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\OmpayLoginRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Actions\Ompay\SendVerificationAction;
use App\Actions\Ompay\RegisterAction;
use App\Actions\Ompay\LoginAction;
use App\Actions\Ompay\GetBalanceAction;
use App\Actions\Ompay\TransferAction;
use App\Actions\Ompay\GetHistoryAction;
use App\Actions\Ompay\LogoutAction;
use App\Actions\Ompay\DepositAction;
use App\Actions\Ompay\WithdrawAction;
use App\Actions\Ompay\GetTransactionsAction;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class OmpayController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private SendVerificationAction $sendVerificationAction,
        private RegisterAction $registerAction,
        private LoginAction $loginAction,
        private GetBalanceAction $getBalanceAction,
        private TransferAction $transferAction,
        private GetHistoryAction $getHistoryAction,
        private LogoutAction $logoutAction,
        private DepositAction $depositAction,
        private WithdrawAction $withdrawAction,
        private GetTransactionsAction $getTransactionsAction
    ) {}

    public function sendVerification(SendVerificationRequest $request)
    {
        $sendVerificationAction = $this->sendVerificationAction;
        $sendVerificationAction($request->telephone);

        return $this->successResponse(null, 'Code de vérification envoyé par SMS avec succès actuellement dans le fichier laravel.log pour les tests');
    }

    public function register(RegisterRequest $request)
    {
        try {
            $registerAction = $this->registerAction;
            $result = $registerAction($request->validated());

            return $this->successResponse($result, 'Inscription réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function login(OmpayLoginRequest $request)
    {
        try {
            $loginAction = $this->loginAction;
            $result = $loginAction($request->only('telephone', 'password'));

            return $this->successResponse($result, 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function getBalance($compteId = null)
    {
        try {
            $getBalanceAction = $this->getBalanceAction;
            $balance = $getBalanceAction($compteId);
            return $this->successResponse($balance, 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function transfer(TransferRequest $request)
    {
        $user = Auth::user();

        try {
            $transferAction = $this->transferAction;
            $result = $transferAction(
                $user,
                $request->recipient_telephone,
                $request->amount,
                $request->description ?? null
            );

            return $this->successResponse($result, 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function getHistory()
    {
        try {
            $getHistoryAction = $this->getHistoryAction;
            $history = $getHistoryAction();
            return $this->successResponse($history, 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function logout()
    {
        $logoutAction = $this->logoutAction;
        $logoutAction();
        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * Effectuer un dépôt
     */
    public function deposit(DepositRequest $request)
    {
        $user = Auth::user();

        try {
            $depositAction = $this->depositAction;
            $result = $depositAction($user, $request->amount, $request->description);

            return $this->successResponse($result, 'Dépôt effectué avec succès');
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
            $withdrawAction = $this->withdrawAction;
            $result = $withdrawAction($user, $request->amount, $request->description);

            return $this->successResponse($result, 'Retrait effectué avec succès');
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
            $getTransactionsAction = $this->getTransactionsAction;
            $history = $getTransactionsAction($compteId);
            return $this->successResponse($history, 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}
