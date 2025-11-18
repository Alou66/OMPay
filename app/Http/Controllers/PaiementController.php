<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaiementMarchandRequest;
use App\Services\PaiementService;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="🏪 Paiements",
 *     description="Paiements vers les comptes marchands"
 * )
 */
class PaiementController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private PaiementService $paiementService
    ) {}

    /**
     * @OA\Post(
     *     path="/paiement/marchand",
     *     tags={"🏪 Paiements"},
     *     summary="Effectuer un paiement vers un marchand",
     *     description="Permet à un client de payer un marchand en utilisant son code marchand unique. Le paiement crée deux transactions : débit du client et crédit du marchand. Un SMS de confirmation est envoyé aux deux parties.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand","montant"},
     *             @OA\Property(property="code_marchand", type="string", pattern="^MCH[A-Z0-9]{8}$", example="MCHABC123456", description="Code marchand unique du destinataire (format: MCHXXXXXXXX)"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00, minimum=100, maximum=1000000, description="Montant à payer en FCFA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès - SMS de confirmation envoyés",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="data", ref="#/components/schemas/MerchantPaymentResponse")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou paiement impossible",
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
     *         response=404,
     *         description="Code marchand invalide ou compte marchand non trouvé",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="message", type="string", example="Code marchand invalide")
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
    public function payerMarchand(PaiementMarchandRequest $request)
    {
        $user = Auth::user();

        try {
            $result = $this->paiementService->payerMarchand(
                $user,
                $request->code_marchand,
                $request->montant
            );

            return $this->successResponse([
                'debit_transaction' => new TransactionResource($result['debit_transaction']),
                'credit_transaction' => new TransactionResource($result['credit_transaction']),
                'reference' => $result['reference'],
            ], 'Paiement effectué avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}