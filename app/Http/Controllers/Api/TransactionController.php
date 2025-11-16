<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Compte;
use App\Models\QrCode;
use App\Models\User;
use App\Enums\TypeTransaction;
use App\Enums\StatutTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/compte",
     *   summary="Informations du compte utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Informations du compte", @OA\JsonContent(
     *     @OA\Property(property="solde", type="integer", description="Solde en centimes"),
     *     @OA\Property(property="type", type="string", description="Type de compte")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function getCompte(Request $request)
    {
        $user = $request->user();
        $compte = $user->compte;

        if (!$compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        return response()->json([
            'solde' => $compte->solde,
            'type' => $compte->type,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/historique",
     *   summary="Historique des transactions",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Liste des transactions", @OA\JsonContent(
     *     @OA\Property(property="data", type="array", @OA\Items(type="object", @OA\Property(property="montant", type="integer")))
     *   )),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function historique(Request $request)
    {
        $user = $request->user();
        $compte = $user->compte;

        $transactions = Transaction::where('compte_source_id', $compte->id)
            ->orWhere('compte_dest_id', $compte->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * @OA\Post(
     *   path="/api/transactions/depot",
     *   tags={"Transaction"},
     *   summary="Effectuer un dépôt d'argent",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"montant", "agent_id"},
     *       @OA\Property(property="montant", type="integer", example=5000, description="Montant en XOF (5000 = 5000 XOF)"),
     *       @OA\Property(property="agent_id", type="string", example="uuid", description="ID de l'agent distributeur obligatoire")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Dépôt réussi"),
     *   @OA\Response(response=400, description="Montant invalide ou agent invalide"),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function depot(Request $request)
    {
        $request->validate([
            'montant' => 'required|integer|min:1', // en XOF
            'agent_id' => 'required|uuid|exists:users,id',
        ]);

        // Convertir XOF en centimes (stockage interne)
        $montantEnCentimes = $request->montant * 100;

        $user = $request->user();
        $compte = $user->compte;
        $agent = User::find($request->agent_id);

        if ($agent->role !== 'distributeur') {
            return response()->json(['error' => 'Agent invalide'], 400);
        }

        DB::transaction(function () use ($compte, $request, $montantEnCentimes) {
            $compte->increment('solde', $montantEnCentimes);

            Transaction::create([
                'montant' => $montantEnCentimes,
                'type' => TypeTransaction::DEPOT->value,
                'statut' => StatutTransaction::ENVOYE->value,
                'compte_source_id' => $compte->id,
                'reference' => Str::uuid(),
                'frais' => 0,
            ]);
        });

        return response()->json([
            'message' => 'Dépôt de ' . $request->montant . ' XOF réussi',
            'montant_xof' => $request->montant,
            'montant_centimes' => $montantEnCentimes
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/transactions/retrait",
     *   tags={"Transaction"},
     *   summary="Effectuer un retrait d'argent",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"montant", "agent_id", "pin"},
     *       @OA\Property(property="montant", type="integer", example=2000, description="Montant en XOF"),
     *       @OA\Property(property="agent_id", type="string", example="uuid"),
     *       @OA\Property(property="pin", type="string", example="1234")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Retrait réussi"),
     *   @OA\Response(response=400, description="PIN invalide ou solde insuffisant"),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function retrait(Request $request)
    {
        $request->validate([
            'montant' => 'required|integer|min:1', // en XOF
            'agent_id' => 'required|uuid|exists:users,id',
            'pin' => 'required|string',
        ]);

        // Convertir XOF en centimes (stockage interne)
        $montantEnCentimes = $request->montant * 100;

        $user = $request->user();
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['error' => 'PIN invalide'], 400);
        }

        $compte = $user->compte;
        $agent = User::find($request->agent_id);

        if ($agent->role !== 'distributeur') {
            return response()->json(['error' => 'Agent invalide'], 400);
        }

        if ($compte->solde < $montantEnCentimes) {
            return response()->json(['error' => 'Solde insuffisant'], 400);
        }

        DB::transaction(function () use ($compte, $request, $montantEnCentimes) {
            $compte->decrement('solde', $montantEnCentimes);

            Transaction::create([
                'montant' => $montantEnCentimes,
                'type' => TypeTransaction::RETRAIT->value,
                'statut' => StatutTransaction::ENVOYE->value,
                'compte_source_id' => $compte->id,
                'reference' => Str::uuid(),
                'frais' => 100, // Exemple
            ]);
        });

        return response()->json([
            'message' => 'Retrait de ' . $request->montant . ' XOF réussi',
            'montant_xof' => $request->montant,
            'montant_centimes' => $montantEnCentimes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/transfert",
     *     tags={"Transaction"},
     *     summary="Transférer de l'argent",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"telephone_dest", "montant", "pin"},
     *             @OA\Property(property="telephone_dest", type="string", example="781234567"),
     *             @OA\Property(property="montant", type="integer", example=5000, description="Montant en XOF"),
     *             @OA\Property(property="pin", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Succès", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=400, description="Solde insuffisant ou PIN incorrect")
     * )
     */
    public function transfert(Request $request)
    {
        $request->validate([
            'telephone_dest' => 'required|string|exists:users,telephone',
            'montant' => 'required|integer|min:1', // en XOF
            'pin' => 'required|string',
        ]);

        // Convertir XOF en centimes (stockage interne)
        $montantEnCentimes = $request->montant * 100;

        $user = $request->user();
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['error' => 'PIN invalide'], 400);
        }

        $compteSource = $user->compte;
        $destUser = User::where('telephone', $request->telephone_dest)->first();
        $compteDest = $destUser->compte;

        if ($compteSource->solde < $montantEnCentimes) {
            return response()->json(['error' => 'Solde insuffisant'], 400);
        }

        DB::transaction(function () use ($compteSource, $compteDest, $request, $montantEnCentimes) {
            $compteSource->decrement('solde', $montantEnCentimes);
            $compteDest->increment('solde', $montantEnCentimes);

            Transaction::create([
                'montant' => $montantEnCentimes,
                'type' => TypeTransaction::TRANSFERT->value,
                'statut' => StatutTransaction::ENVOYE->value,
                'compte_source_id' => $compteSource->id,
                'compte_dest_id' => $compteDest->id,
                'reference' => Str::uuid(),
                'frais' => 50,
            ]);
        });

        return response()->json([
            'message' => 'Transfert de ' . $request->montant . ' XOF réussi',
            'montant_xof' => $request->montant,
            'montant_centimes' => $montantEnCentimes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/paiement",
     *     tags={"Transaction"},
     *     summary="Effectuer un paiement via QR",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"code_qr", "montant", "pin"},
     *             @OA\Property(property="code_qr", type="string", example="OM-ABC123"),
     *             @OA\Property(property="montant", type="integer", example=10000, description="Montant en XOF"),
     *             @OA\Property(property="pin", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Paiement réussi", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=400, description="QR invalide ou solde insuffisant")
     * )
     */
    public function paiement(Request $request)
    {
        $request->validate([
            'code_qr' => 'required|string|exists:qr_codes,code',
            'montant' => 'required|integer|min:1', // en XOF
            'pin' => 'required|string',
        ]);

        // Convertir XOF en centimes (stockage interne)
        $montantEnCentimes = $request->montant * 100;

        $user = $request->user();
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['error' => 'PIN invalide'], 400);
        }

        $compte = $user->compte;
        $qr = QrCode::where('code', $request->code_qr)->first();

        if ($qr->statut !== 'active' || $qr->expires_at < now()) {
            return response()->json(['error' => 'QR code invalide'], 400);
        }

        if ($compte->solde < $montantEnCentimes) {
            return response()->json(['error' => 'Solde insuffisant'], 400);
        }

        DB::transaction(function () use ($compte, $qr, $request, $montantEnCentimes) {
            $compte->decrement('solde', $montantEnCentimes);
            $qr->marchand->compte->increment('solde', $montantEnCentimes);

            Transaction::create([
                'montant' => $montantEnCentimes,
                'type' => TypeTransaction::PAIEMENT->value,
                'statut' => StatutTransaction::ENVOYE->value,
                'compte_source_id' => $compte->id,
                'marchand_id' => $qr->marchand_id,
                'reference' => Str::uuid(),
                'frais' => 0,
            ]);

            $qr->update(['statut' => 'used']);
        });

        return response()->json([
            'message' => 'Paiement de ' . $request->montant . ' XOF réussi',
            'montant_xof' => $request->montant,
            'montant_centimes' => $montantEnCentimes
        ]);
    }
}