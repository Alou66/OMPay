<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestOTPRequest;
use App\Http\Requests\Auth\VerifyOTPRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Actions\Ompay\GetBalanceAction;
use App\Actions\Ompay\TransferAction;
use App\Actions\Ompay\GetHistoryAction;
use App\Actions\Ompay\LogoutAction;
use App\Actions\Ompay\DepositAction;
use App\Actions\Ompay\WithdrawAction;
use App\Actions\Ompay\GetTransactionsAction;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="OMPAY API",
 *     version="1.0.0",
 *     description="API pour les opérations OMPay - FinTech Sénégalaise"
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Tag(
 *     name="🔐 Auth",
 *     description="Authentification et gestion des utilisateurs"
 * )
 * @OA\Tag(
 *     name="💸 OMPAY Transactions",
 *     description="Opérations financières (dépôt, retrait, transfert)"
 * )
 * @OA\Tag(
 *     name="📊 OMPAY Consultation",
 *     description="Consultation soldes et historiques"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Diop"),
 *     @OA\Property(property="prenom", type="string", example="Amadou"),
 *     @OA\Property(property="telephone", type="string", example="771234567"),
 *     @OA\Property(property="status", type="string", enum={"Actif", "Inactif", "pending_verification"}),
 *     @OA\Property(property="cni", type="string", example="AB123456789"),
 *     @OA\Property(property="sexe", type="string", enum={"Homme", "Femme"}),
 *     @OA\Property(property="role", type="string", enum={"client", "admin"})
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="numero_compte", type="string", example="OM12345678"),
 *     @OA\Property(property="type", type="string", enum={"cheque", "epargne"}),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloqué", "fermé"}),
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert"}),
 *     @OA\Property(property="montant", type="number", format="float", example=1000.00),
 *     @OA\Property(property="statut", type="string", enum={"reussi", "echec", "en_cours"}),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="reference", type="string", example="TXN202511152300034086"),
 *     @OA\Property(property="date_operation", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AuthTokens",
 *     @OA\Property(property="access_token", type="string", example="1|hCrUqzgS8DhPIk3CLIaV1gsvtEmGrKn9IWxsoxkD04360b9a"),
 *     @OA\Property(property="refresh_token", type="string", example="tXV9BauXVgz7NElE7bF4NcM2hqSdFCKDn8kV11oaUn4czTroQSnQUoPGkPWMgN8a"),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=900)
 * )
 */
class OmpayController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetBalanceAction $getBalanceAction,
        private TransferAction $transferAction,
        private GetHistoryAction $getHistoryAction,
        private LogoutAction $logoutAction,
        private DepositAction $depositAction,
        private WithdrawAction $withdrawAction,
        private GetTransactionsAction $getTransactionsAction,
        private AuthService $authService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"🔐 Auth"},
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Crée un utilisateur avec compte en attente de vérification.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nom", type="string", example="Diop"),
     *             @OA\Property(property="prenom", type="string", example="Amadou"),
     *             @OA\Property(property="telephone", type="string", example="771234567"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="cni", type="string", example="AB123456789"),
     *             @OA\Property(property="sexe", type="string", enum={"Homme", "Femme"}, example="Homme"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="type_compte", type="string", enum={"cheque", "epargne"}, example="cheque")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé – demande de vérification OTP"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Erreur de validation")
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            return $this->successResponse([
                'user' => $user
            ], 'Utilisateur créé – demande de vérification OTP');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/request-otp",
     *     tags={"🔐 Auth"},
     *     summary="Demander un OTP",
     *     description="Envoie un OTP par SMS. Si compte en attente → OTP d'activation. Si compte actif → OTP de connexion.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé par SMS")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Utilisateur non trouvé ou rate limit dépassé"),
     *     @OA\Response(response=429, description="Trop de tentatives")
     * )
     */
    public function requestOTP(RequestOTPRequest $request)
    {
        try {
            $this->authService->requestOTP($request->telephone);
            return $this->successResponse(null, 'Code OTP envoyé par SMS');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     tags={"🔐 Auth"},
     *     summary="Vérifier un OTP",
     *     description="Vérifie l'OTP et active le compte si nécessaire, puis retourne les tokens d'accès.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="771234567"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="tokens", ref="#/components/schemas/AuthTokens")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="OTP invalide ou expiré")
     * )
     */
    public function verifyOTP(VerifyOTPRequest $request)
    {
        try {
            $result = $this->authService->verifyOTP($request->telephone, $request->otp);

            return $this->successResponse([
                'user' => $result['user'],
                'tokens' => $result['tokens'],
            ], 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"🔐 Auth"},
     *     summary="Rafraîchir le token d'accès",
     *     description="Génère un nouveau token d'accès en utilisant le refresh token avec rotation.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", example="refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(property="data", ref="#/components/schemas/AuthTokens")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Refresh token invalide")
     * )
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            $tokens = $this->authService->refreshToken($request->refresh_token);
            return $this->successResponse($tokens, 'Token rafraîchi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"🔐 Auth"},
     *     summary="Connexion avec mot de passe",
     *     description="Authentification classique avec téléphone et mot de passe pour les comptes déjà activés.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="771234567"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", ref="#/components/schemas/AuthTokens")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Identifiants invalides")
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = $this->authService->authenticate($request->telephone, $request->password);
            $tokens = $this->authService->generateTokens($user);

            return $this->successResponse($tokens, 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/ompay/balance",
     *     tags={"📊 OMPAY Consultation"},
     *     summary="Consulter le solde du compte",
     *     description="Récupère le solde actuel du compte principal de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="query",
     *         description="ID du compte (optionnel, utilise le compte principal par défaut)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="compte_id", type="string", format="uuid"),
     *                 @OA\Property(property="numero_compte", type="string", example="OM12345678"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="date_consultation", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token d'authentification manquant ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte trouvé")
     *         )
     *     )
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
     *     path="/ompay/transfer",
     *     tags={"💸 OMPAY Transactions"},
     *     summary="Effectuer un transfert d'argent",
     *     description="Transfère de l'argent vers un autre compte OMPAY",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="recipient_telephone", type="string", example="772345678", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="amount", type="number", format="float", example=500.00, minimum=100, maximum=1000000, description="Montant à transférer (FCFA)"),
     *             @OA\Property(property="description", type="string", maxLength=255, example="Paiement facture", description="Description optionnelle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="debit_transaction", ref="#/components/schemas/Transaction"),
     *                 @OA\Property(property="credit_transaction", ref="#/components/schemas/Transaction"),
     *                 @OA\Property(property="reference", type="string", example="TXN202511152302356175")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou fonds insuffisants",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solde insuffisant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destinataire non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur destinataire introuvable")
     *         )
     *     )
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
     *     path="/ompay/history",
     *     tags={"📊 OMPAY Consultation"},
     *     summary="Obtenir l'historique des transactions",
     *     description="Récupère l'historique paginé des transactions de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Historique récupéré avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="last_page", type="integer", example=8)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getHistory(Request $request)
    {
        try {
            $getHistoryAction = $this->getHistoryAction;
            $history = $getHistoryAction(
                $request->get('page', 1),
                $request->get('per_page', 20),
                $request->get('type')
            );
            return $this->successResponse($history, 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/ompay/logout",
     *     tags={"🔐 Auth"},
     *     summary="Déconnexion utilisateur",
     *     description="Invalide tous les tokens d'accès et de rafraîchissement",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        $user = Auth::user();
        $this->authService->logout($user);
        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Post(
     *     path="/ompay/deposit",
     *     tags={"💸 OMPAY Transactions"},
     *     summary="Effectuer un dépôt d'argent",
     *     description="Ajoute des fonds sur le compte de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float", example=1000.00, minimum=100, maximum=5000000, description="Montant à déposer (FCFA)"),
     *             @OA\Property(property="description", type="string", maxLength=255, example="Dépôt espèces", description="Description optionnelle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", ref="#/components/schemas/Transaction"),
     *                 @OA\Property(property="reference", type="string", example="TXN202511152258103440")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le montant doit être supérieur à 100 FCFA")
     *         )
     *     )
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
