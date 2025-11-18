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
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\BalanceResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionHistoryResource;
use App\Http\Resources\DashboardResource;
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
 * @OA\Server(
 *     url="https://api.ompay.sn/api",
 *     description="Serveur de production"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Enter token in format: Bearer {token}"
 * )
 * @OA\Tag(
 *     name="🔐 Authentification",
 *     description="Authentification et gestion des utilisateurs"
 * )
 * @OA\Tag(
 *     name="💸 Transactions",
 *     description="Opérations financières (dépôt, retrait, transfert)"
 * )
 * @OA\Tag(
 *     name="📊 Consultation",
 *     description="Consultation soldes et historiques"
 * )
 * @OA\Tag(
 *     name="🏪 Paiements",
 *     description="Paiements vers les marchands"
 * )
 * @OA\Tag(
 *     name="📈 Dashboard",
 *     description="Tableau de bord utilisateur"
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="nom", type="string", example="Diop"),
 *     @OA\Property(property="prenom", type="string", example="Amadou"),
 *     @OA\Property(property="telephone", type="string", example="771234567"),
 *     @OA\Property(property="email", type="string", example="amadou.diop@example.com")
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     @OA\Property(property="numero_compte", type="string", example="OM12345678"),
 *     @OA\Property(property="type", type="string", enum={"marchand", "simple"}),
 *     @OA\Property(property="statut", type="string", enum={"actif", "inactif", "bloqué", "fermé"}),
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="code_marchand", type="string", nullable=true, example="MCHABC123456")
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert"}),
 *     @OA\Property(property="montant", type="number", format="float", example=1000.00),
 *     @OA\Property(property="statut", type="string", enum={"reussi", "echec", "en_cours"}),
 *     @OA\Property(property="date_operation", type="string", format="date-time"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="reference", type="string", example="TXN202511152300034086")
 * )
 *
 * @OA\Schema(
 *     schema="Balance",
 *     @OA\Property(property="solde", type="number", format="float", example=1500.50),
 *     @OA\Property(property="devise", type="string", example="FCFA")
 * )
 *
 * @OA\Schema(
 *     schema="TransactionHistory",
 *     @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
 *     @OA\Property(property="pagination", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=20),
 *         @OA\Property(property="total", type="integer", example=150),
 *         @OA\Property(property="last_page", type="integer", example=8)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Dashboard",
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="compte", ref="#/components/schemas/Compte"),
 *     @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AuthTokens",
 *     @OA\Property(property="access_token", type="string", example="1|hCrUqzgS8DhPIk3CLIaV1gsvtEmGrKn9IWxsoxkD04360b9a"),
 *     @OA\Property(property="refresh_token", type="string", example="tXV9BauXVgz7NElE7bF4NcM2hqSdFCKDn8kV11oaUn4czTroQSnQUoPGkPWMgN8a"),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=900)
 * )
 *
 * @OA\Schema(
 *     schema="TransferResponse",
 *     @OA\Property(property="debit_transaction", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="credit_transaction", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="reference", type="string", example="TXN202511152302356175")
 * )
 *
 * @OA\Schema(
 *     schema="DepositWithdrawResponse",
 *     @OA\Property(property="transaction", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="reference", type="string", example="TXN202511152258103440")
 * )
 *
 * @OA\Schema(
 *     schema="MerchantPaymentResponse",
 *     @OA\Property(property="debit_transaction", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="credit_transaction", ref="#/components/schemas/Transaction"),
 *     @OA\Property(property="reference", type="string", example="TXN202511152302356175")
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
        private AuthService $authService,
        private DashboardService $dashboardService
    ) {}

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"🔐 Authentification"},
     *     summary="Inscription d'un nouvel utilisateur",
     *     description="Crée un utilisateur avec un compte en attente de vérification par OTP. Un SMS avec code OTP sera envoyé.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom","prenom","telephone","password","password_confirmation","cni","sexe","date_naissance"},
     *             @OA\Property(property="nom", type="string", maxLength=255, example="Diop"),
     *             @OA\Property(property="prenom", type="string", maxLength=255, example="Amadou"),
     *             @OA\Property(property="telephone", type="string", pattern="^77[0-9]{7}$|^78[0-9]{7}$|^76[0-9]{7}$|^70[0-9]{7}$", example="771234567"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="cni", type="string", pattern="^[A-Z]{2}[0-9]{9}$", example="AB123456789"),
     *             @OA\Property(property="sexe", type="string", enum={"Homme", "Femme"}, example="Homme"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="type_compte", type="string", enum={"marchand", "simple"}, default="simple", example="simple")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="code_marchand", type="string", nullable=true, example="MCHABC123456")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object", example={"telephone": {"Le numéro de téléphone est déjà utilisé."}})
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Utilisateur déjà existant",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Un utilisateur avec ce numéro de téléphone existe déjà.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
     *         )
     *     )
     * )
     */

    // public function register(RegisterRequest $request)
    // {
    //     try {
    //         $user = $this->authService->register($request->validated());
    //         return $this->successResponse(
    //             null,
    //           'Utilisateur créé avec succès');
    //     } catch (\Exception $e) {
    //         return $this->errorResponse($e->getMessage(), 400);
    //     }
    // }


    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            $compte = $user->client->comptes()->first();

            return $this->successResponse([
                'code_marchand' => $compte->code_marchand ?? null
            ], 'Utilisateur créé avec succès – demande de vérification OTP');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    /**
     * @OA\Post(
     *     path="/auth/request-otp",
     *     tags={"🔐 Authentification"},
     *     summary="Demander un code OTP",
     *     description="Envoie un code OTP par SMS pour activation du compte ou connexion. Rate limité à 3 demandes par heure.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", pattern="^77[0-9]{7}$|^78[0-9]{7}$|^76[0-9]{7}$|^70[0-9]{7}$", example="771234567")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Utilisateur non trouvé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Utilisateur non trouvé.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de tentatives - Rate limit dépassé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Trop de tentatives. Veuillez réessayer dans une heure.")
     *                 )
     *             }
     *         )
     *     )
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
     *     tags={"🔐 Authentification"},
     *     summary="Vérifier un code OTP",
     *     description="Vérifie le code OTP, active le compte si nécessaire et retourne les tokens d'accès pour l'authentification.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","otp"},
     *             @OA\Property(property="telephone", type="string", pattern="^77[0-9]{7}$|^78[0-9]{7}$|^76[0-9]{7}$|^70[0-9]{7}$", example="771234567"),
     *             @OA\Property(property="otp", type="string", pattern="^[0-9]{6}$", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié avec succès - tokens retournés",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="tokens", ref="#/components/schemas/AuthTokens")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OTP invalide ou expiré",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Code OTP invalide ou expiré.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function verifyOTP(VerifyOTPRequest $request)
    {
        try {
            $result = $this->authService->verifyOTP($request->telephone, $request->otp);

            return $this->successResponse([
                'tokens' => $result['tokens'],
            ], 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"🔐 Authentification"},
     *     summary="Rafraîchir le token d'accès",
     *     description="Génère un nouveau token d'accès en utilisant le refresh token avec rotation automatique pour sécurité.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="tXV9BauXVgz7NElE7bF4NcM2hqSdFCKDn8kV11oaUn4czTroQSnQUoPGkPWMgN8a")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/AuthTokens")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Refresh token invalide ou expiré",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Refresh token invalide ou expiré.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
     *         )
     *     )
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
     *     tags={"🔐 Authentification"},
     *     summary="Connexion avec mot de passe",
     *     description="Authentification classique avec téléphone et mot de passe pour les comptes déjà activés. Rate limité après 5 tentatives échouées.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","password"},
     *             @OA\Property(property="telephone", type="string", pattern="^77[0-9]{7}$|^78[0-9]{7}$|^76[0-9]{7}$|^70[0-9]{7}$", example="771234567"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/AuthTokens")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Compte verrouillé ou non activé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Compte non activé. Veuillez vérifier votre numéro de téléphone.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Identifiants invalides.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de tentatives - compte verrouillé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Trop de tentatives. Compte verrouillé pour 15 minutes.")
     *                 )
     *             }
     *         )
     *     )
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
     *     tags={"📊 Consultation"},
     *     summary="Consulter le solde du compte",
     *     description="Récupère le solde actuel du compte principal de l'utilisateur connecté",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="query",
     *         description="ID du compte spécifique (optionnel, utilise le compte principal par défaut)",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Balance")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token d'authentification manquant ou invalide",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Unauthenticated.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Aucun compte trouvé pour cet utilisateur")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function getBalance($compteId = null)
    {
        try {
            $getBalanceAction = $this->getBalanceAction;
            $balance = $getBalanceAction($compteId);
            return $this->successResponse(new BalanceResource($balance), 'Solde récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/ompay/transfer",
     *     tags={"💸 Transactions"},
     *     summary="Effectuer un transfert d'argent",
     *     description="Transfère de l'argent vers un autre compte OMPAY. Crée deux transactions : débit pour l'expéditeur, crédit pour le destinataire.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipient_telephone","amount"},
     *             @OA\Property(property="recipient_telephone", type="string", pattern="^77[0-9]{7}$|^78[0-9]{7}$|^76[0-9]{7}$|^70[0-9]{7}$", example="772345678", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="amount", type="number", format="float", example=500.00, minimum=100, maximum=1000000, description="Montant à transférer (FCFA)"),
     *             @OA\Property(property="description", type="string", maxLength=255, example="Paiement facture", description="Description optionnelle du transfert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TransferResponse")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou fonds insuffisants",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Solde insuffisant")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destinataire non trouvé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Utilisateur destinataire introuvable")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
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

            return $this->successResponse([
                'debit_transaction' => new TransactionResource($result['debit_transaction']),
                'credit_transaction' => new TransactionResource($result['credit_transaction']),
                'reference' => $result['reference'],
            ], 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/ompay/history",
     *     tags={"📊 Consultation"},
     *     summary="Obtenir l'historique des transactions",
     *     description="Récupère l'historique paginé des transactions du compte principal de l'utilisateur",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour la pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page (max 100)",
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
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/TransactionHistory")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Unauthenticated.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Paramètres de pagination invalides",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="errors", type="object")
     *                 )
     *             }
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
            return $this->successResponse(new TransactionHistoryResource($history), 'Historique récupéré avec succès');
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
        // $this->authService->logout($user);
        $logoutAction = $this->logoutAction;
        $logoutAction();

        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * @OA\Get(
     *     path="/dashboard",
     *     tags={"📈 Dashboard"},
     *     summary="Données du tableau de bord",
     *     description="Récupère les informations complètes de l'utilisateur connecté : profil, compte avec solde calculé dynamiquement, et les 10 dernières transactions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard récupéré avec succès",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/Dashboard")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Unauthenticated.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Erreur interne du serveur")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();
            $data = $this->dashboardService->getDashboardData($user);
            return $this->successResponse(new DashboardResource($data), 'Dashboard utilisateur');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
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

            return $this->successResponse([
                'transaction' => new TransactionResource($result['transaction']),
                'reference' => $result['reference'],
            ], 'Dépôt effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/ompay/withdraw",
     *     tags={"💸 OMPAY Transactions"},
     *     summary="Effectuer un retrait d'argent",
     *     description="Retire des fonds du compte de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float", example=500.00, minimum=100, maximum=1000000, description="Montant à retirer (FCFA)"),
     *             @OA\Property(property="description", type="string", maxLength=255, example="Retrait DAB", description="Description optionnelle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retrait effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrait effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", ref="#/components/schemas/Transaction"),
     *                 @OA\Property(property="reference", type="string", example="TXN202511152259204561")
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
     *     )
     * )
     */
    public function withdraw(WithdrawRequest $request)
    {
        $user = Auth::user();

        try {
            $withdrawAction = $this->withdrawAction;
            $result = $withdrawAction($user, $request->amount, $request->description);

            return $this->successResponse([
                'transaction' => new TransactionResource($result['transaction']),
                'reference' => $result['reference'],
            ], 'Retrait effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/ompay/transactions/{compteId}",
     *     tags={"📊 OMPAY Consultation"},
     *     summary="Obtenir l'historique des transactions d'un compte",
     *     description="Récupère l'historique détaillé des transactions pour un compte spécifique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Historique récupéré avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
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
     *             @OA\Property(property="message", type="string", example="Compte introuvable")
     *         )
     *     )
     * )
     */
    public function getTransactions($compteId)
    {
        try {
            $getTransactionsAction = $this->getTransactionsAction;
            $history = $getTransactionsAction($compteId);
            return $this->successResponse(TransactionResource::collection($history), 'Historique récupéré avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}
