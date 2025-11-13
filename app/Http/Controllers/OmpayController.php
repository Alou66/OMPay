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

/**
 * @OA\Tag(
 *     name="OMPay",
 *     description="API pour les opérations OMPay (dépôt, retrait, transfert, etc.)"
 * )
 */
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

    /**
     * @OA\Post(
     *     path="/api/ompay/send-verification",
     *     tags={"OMPay"},
     *     summary="Envoyer un code de vérification OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function sendVerification(SendVerificationRequest $request)
    {
        $sendVerificationAction = $this->sendVerificationAction;
        $sendVerificationAction($request->telephone);

        return $this->successResponse(null, 'Code de vérification envoyé par SMS avec succès actuellement dans le fichier laravel.log pour les tests');
    }

    /**
     * @OA\Post(
     *     path="/api/ompay/register",
     *     tags={"OMPay"},
     *     summary="Inscription utilisateur avec OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="cni", type="string"),
     *             @OA\Property(property="sexe", type="string"),
     *             @OA\Property(property="date_naissance", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Inscription réussie")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/ompay/login",
     *     tags={"OMPay"},
     *     summary="Connexion utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Connexion réussie")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/ompay/balance",
     *     tags={"OMPay"},
     *     summary="Obtenir le solde",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Solde récupéré")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/ompay/transfer",
     *     tags={"OMPay"},
     *     summary="Effectuer un transfert",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="recipient_telephone", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transfert effectué")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/ompay/history",
     *     tags={"OMPay"},
     *     summary="Obtenir l'historique des transactions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Historique récupéré")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/ompay/logout",
     *     tags={"OMPay"},
     *     summary="Déconnexion utilisateur",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Déconnexion réussie")
     * )
     */
    public function logout()
    {
        $logoutAction = $this->logoutAction;
        $logoutAction();
        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/api/ompay/deposit",
     *     tags={"OMPay"},
     *     summary="Effectuer un dépôt",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Dépôt effectué")
     * )
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
     * @OA\Post(
     *     path="/api/ompay/withdraw",
     *     tags={"OMPay"},
     *     summary="Effectuer un retrait",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Retrait effectué")
     * )
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
