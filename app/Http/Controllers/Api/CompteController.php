<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\MarchandCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

/**
 */
class CompteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/comptes/dashboard",
     *   tags={"Comptes"},
     *   summary="Obtenir le tableau de bord du compte connecté",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Informations du tableau de bord", @OA\JsonContent(
     *     @OA\Property(property="user", type="object"),
     *     @OA\Property(property="compte", type="object"),
     *     @OA\Property(property="transactions_recentes", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="statistiques", type="object")
     *   )),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=403, description="Accès non autorisé")
     * )
     */
    public function dashboard(Request $request, $num = null)
    {
        $user = $request->user();
        
        // Si le numéro n'est pas fourni, utiliser le numéro de l'utilisateur connecté
        if ($num === null) {
            $num = $user->telephone;
        }
        
        // Vérifier que l'utilisateur accède à son propre compte ou qu'il est admin
        if ($user->telephone !== $num && $user->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $compteUser = User::where('telephone', $num)->first();
        
        if (!$compteUser || !$compteUser->compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        // Obtenir les transactions récentes (limitées à 10)
        $transactions = Transaction::where(function($query) use ($compteUser) {
                $query->where('compte_source_id', $compteUser->compte->id)
                      ->orWhere('compte_dest_id', $compteUser->compte->id);
            })
            ->with(['compteSource.user', 'compteDest.user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculer les statistiques du mois
        $thisMonthStart = Carbon::now()->startOfMonth();
        $transactionsDuMois = Transaction::where(function($query) use ($compteUser) {
                $query->where('compte_source_id', $compteUser->compte->id)
                      ->orWhere('compte_dest_id', $compteUser->compte->id);
            })
            ->where('created_at', '>=', $thisMonthStart)
            ->get();

        $depenses = $transactionsDuMois->where('compte_source_id', $compteUser->compte->id)->sum('montant');
        $recettes = $transactionsDuMois->where('compte_dest_id', $compteUser->compte->id)->sum('montant');
        $nombreTransactions = $transactionsDuMois->count();

        return response()->json([
            'user' => $compteUser,
            'compte' => $compteUser->compte,
            'transactions_recentes' => $transactions,
            'statistiques' => [
                'depenses_mois' => $depenses,
                'recettes_mois' => $recettes,
                'nombre_transactions_mois' => $nombreTransactions,
                'solde_actuel' => $compteUser->compte->solde
            ]
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/comptes/solde",
     *   tags={"Comptes"},
     *   summary="Obtenir le solde du compte",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Solde du compte", @OA\JsonContent(
     *     @OA\Property(property="solde", type="integer", description="Solde en centimes"),
     *     @OA\Property(property="solde_formate", type="string", description="Solde formaté en FCFA"),
     *     @OA\Property(property="derniere_maj", type="string")
     *   )),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=403, description="Accès non autorisé")
     * )
     */
    public function solde(Request $request, $num = null)
    {
        $user = $request->user();
        
        // Si le numéro n'est pas fourni, utiliser le numéro de l'utilisateur connecté
        if ($num === null) {
            $num = $user->telephone;
        }
        
        if ($user->telephone !== $num && $user->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $compteUser = User::where('telephone', $num)->first();
        
        if (!$compteUser || !$compteUser->compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        return response()->json([
            'solde' => $compteUser->compte->solde,
            'solde_formate' => number_format($compteUser->compte->solde / 100, 0, ',', ' ') . ' FCFA',
            'derniere_maj' => $compteUser->compte->updated_at->toISOString()
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/comptes/transactions",
     *   tags={"Comptes"},
     *   summary="Obtenir l'historique des transactions",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Numéro de page",
     *     @OA\Schema(type="integer", default=1)
     *   ),
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     description="Nombre d'éléments par page",
     *     @OA\Schema(type="integer", default=20)
     *   ),
     *   @OA\Parameter(
     *     name="type",
     *     in="query",
     *     description="Filtrer par type de transaction",
     *     @OA\Schema(type="string", enum={"transfert", "paiement", "depot", "retrait"})
     *   ),
     *   @OA\Response(response=200, description="Liste des transactions", @OA\JsonContent(
     *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="pagination", type="object")
     *   )),
     *   @OA\Response(response=404, description="Compte non trouvé"),
     *   @OA\Response(response=403, description="Accès non autorisé")
     * )
     */
    public function transactions(Request $request, $num = null)
    {
        $user = $request->user();
        
        // Si le numéro n'est pas fourni, utiliser le numéro de l'utilisateur connecté
        if ($num === null) {
            $num = $user->telephone;
        }
        
        if ($user->telephone !== $num && $user->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $compteUser = User::where('telephone', $num)->first();
        
        if (!$compteUser || !$compteUser->compte) {
            return response()->json(['error' => 'Compte non trouvé'], 404);
        }

        $query = Transaction::where(function($q) use ($compteUser) {
                $q->where('compte_source_id', $compteUser->compte->id)
                  ->orWhere('compte_dest_id', $compteUser->compte->id);
            })
            ->with(['compteSource.user', 'compteDest.user']);

        // Filtrer par type si spécifié
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Paginer les résultats
        $perPage = $request->get('per_page', 20);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Enlever les liens de pagination
        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/comptes/transfert",
     *   tags={"Comptes"},
     *   summary="Effectuer un transfert (utilise le token pour l'expéditeur)",
     *   description="Effectue un transfert d'argent de l'utilisateur connecté vers un destinataire. Le numéro et l'authentification de l'expéditeur sont déduits du token.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone_destinataire", "montant"},
     *       @OA\Property(property="telephone_destinataire", type="string", example="771234563"),
     *       @OA\Property(property="montant", type="integer", description="Montant en FCFA", example="5000"),
     *       @OA\Property(property="motif", type="string", example="Transfert d'argent")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Transfert effectué", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="transaction", type="object"),
     *     @OA\Property(property="solde_restant", type="integer")
     *   )),
     *   @OA\Response(response=400, description="Erreur de validation"),
     *   @OA\Response(response=403, description="Accès non autorisé"),
     *   @OA\Response(response=404, description="Compte destinataire non trouvé"),
     *   @OA\Response(response=422, description="Solde insuffisant")
     * )
     * 
     * @OA\Post(
     *   path="/api/comptes/{num}/transfert",
     *   tags={"Comptes"},
     *   summary="Effectuer un transfert (route compatibilité avec numéro)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="num",
     *     in="path",
     *     required=true,
     *     description="Numéro de téléphone de l'expéditeur",
     *     @OA\Schema(type="string", example="782345678")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone_destinataire", "montant", "password"},
     *       @OA\Property(property="telephone_destinataire", type="string", example="783456789"),
     *       @OA\Property(property="montant", type="integer", description="Montant en FCFA", example="1000"),
     *       @OA\Property(property="password", type="string", example="motdepasse123"),
     *       @OA\Property(property="motif", type="string", example="Transfert d'argent")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Transfert effectué", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="transaction", type="object"),
     *     @OA\Property(property="solde_restant", type="integer")
     *   )),
     *   @OA\Response(response=400, description="Erreur de validation"),
     *   @OA\Response(response=403, description="Mot de passe incorrect"),
     *   @OA\Response(response=404, description="Compte destinataire non trouvé"),
     *   @OA\Response(response=422, description="Solde insuffisant")
     * )
     */
    public function transfert(Request $request, $num = null)
    {
        $user = $request->user();
        
        // Si num est fourni, vérifier que l'utilisateur y a accès
        if ($num !== null && $user->telephone !== $num && $user->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Validation différente selon si num est fourni ou non
        if ($num === null) {
            // Endpoint sans num: pas de password requis
            $request->validate([
                'telephone_destinataire' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (!preg_match('/^(78|77)[0-9]{7}$/', $value)) {
                        $fail('Le numéro de téléphone doit commencer par 77 ou 78 et contenir 9 chiffres.');
                    }
                }],
                'montant' => 'required|integer|min:100|max:50000000',
                'motif' => 'nullable|string|max:255',
            ]);
        } else {
            // Endpoint avec num: password requis pour compatibilité
            $request->validate([
                'telephone_destinataire' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (!preg_match('/^(78|77)[0-9]{7}$/', $value)) {
                        $fail('Le numéro de téléphone doit commencer par 77 ou 78 et contenir 9 chiffres.');
                    }
                }],
                'montant' => 'required|integer|min:100|max:50000000',
                'password' => 'required|string',
                'motif' => 'nullable|string|max:255',
            ]);
            
            // Vérifier le mot de passe seulement si fourni
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Mot de passe incorrect'], 403);
            }
        }

        // Vérifier que le solde est suffisant
        if ($user->compte->solde < $request->montant) {
            return response()->json(['error' => 'Solde insuffisant'], 422);
        }

        // Trouver le compte destinataire
        $destinataire = User::where('telephone', $request->telephone_destinataire)->first();
        
        if (!$destinataire || !$destinataire->compte) {
            return response()->json(['error' => 'Compte destinataire non trouvé'], 404);
        }

        // Vérifier qu'on ne transfère pas vers le même compte
        if ($user->id === $destinataire->id) {
            return response()->json(['error' => 'Impossible de transférer vers le même compte'], 400);
        }

        // Effectuer le transfert en transaction
        try {
            DB::beginTransaction();

            // Déduire le montant du compte source
            $user->compte->decrement('solde', $request->montant);

            // Ajouter le montant au compte destination
            $destinataire->compte->increment('solde', $request->montant);

            // Créer la transaction
            $transaction = Transaction::create([
                'compte_source_id' => $user->compte->id,
                'compte_dest_id' => $destinataire->compte->id,
                'montant' => $request->montant,
                'type' => 'transfert',
                'statut' => 'completed',
                'motif' => $request->motif ?: 'Transfert OM Paye',
                'reference' => 'TRF_' . time() . '_' . rand(1000, 9999),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfert effectué avec succès',
                'transaction' => $transaction->load(['compteSource.user', 'compteDest.user']),
                'solde_restant' => $user->fresh()->compte->solde
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors du transfert'], 500);
        }
    }

    /**
     * @OA\Post(
     *   path="/api/comptes/paiement",
     *   tags={"Comptes"},
     *   summary="Effectuer un paiement (utilise le token pour le payeur)",
     *   description="Effectue un paiement via téléphone ou code marchand. L'utilisateur payeur et l'authentification sont déduits du token.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           required={"type", "identifiant_destinataire", "montant"},
     *           @OA\Property(property="type", type="string", enum={"telephone"}, example="telephone"),
     *           @OA\Property(property="identifiant_destinataire", type="string", example="771234563"),
     *           @OA\Property(property="montant", type="integer", example="1500"),
     *           @OA\Property(property="motif", type="string", example="Paiement ami")
     *         ),
     *         @OA\Schema(
     *           required={"type", "identifiant_destinataire", "montant"},
     *           @OA\Property(property="type", type="string", enum={"code_marchand"}, example="code_marchand"),
     *           @OA\Property(property="identifiant_destinataire", type="string", example="MBO001"),
     *           @OA\Property(property="montant", type="integer", example="2500"),
     *           @OA\Property(property="motif", type="string", example="Achat articles")
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(response=200, description="Paiement effectué", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="transaction", type="object"),
     *     @OA\Property(property="solde_restant", type="integer")
     *   )),
     *   @OA\Response(response=400, description="Erreur de validation"),
     *   @OA\Response(response=403, description="Accès non autorisé"),
     *   @OA\Response(response=404, description="Marchand ou destinataire non trouvé"),
     *   @OA\Response(response=422, description="Solde insuffisant")
     * )
     * 
     * @OA\Post(
     *   path="/api/comptes/{num}/paiement",
     *   tags={"Comptes"},
     *   summary="Effectuer un paiement (route compatibilité avec numéro)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="num",
     *     in="path",
     *     required=true,
     *     description="Numéro de téléphone du payeur",
     *     @OA\Schema(type="string", example="782345678")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           required={"type", "identifiant_destinataire", "montant", "password"},
     *           @OA\Property(property="type", type="string", enum={"telephone"}, example="telephone"),
     *           @OA\Property(property="identifiant_destinataire", type="string", example="783456789"),
     *           @OA\Property(property="montant", type="integer", example="1500"),
     *           @OA\Property(property="password", type="string", example="motdepasse123"),
     *           @OA\Property(property="motif", type="string", example="Paiement marchand")
     *         ),
     *         @OA\Schema(
     *           required={"type", "identifiant_destinataire", "montant", "password"},
     *           @OA\Property(property="type", type="string", enum={"code_marchand"}, example="code_marchand"),
     *           @OA\Property(property="identifiant_destinataire", type="string", example="M123456"),
     *           @OA\Property(property="montant", type="integer", example="2500"),
     *           @OA\Property(property="password", type="string", example="motdepasse123"),
     *           @OA\Property(property="motif", type="string", example="Achat marchand")
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(response=200, description="Paiement effectué", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="transaction", type="object"),
     *     @OA\Property(property="solde_restant", type="integer")
     *   )),
     *   @OA\Response(response=400, description="Erreur de validation"),
     *   @OA\Response(response=403, description="Mot de passe incorrect"),
     *   @OA\Response(response=404, description="Marchand non trouvé"),
     *   @OA\Response(response=422, description="Solde insuffisant")
     * )
     */
    public function paiement(Request $request, $num = null)
    {
        $user = $request->user();
        
        // Si num est fourni, vérifier que l'utilisateur y a accès
        if ($num !== null && $user->telephone !== $num && $user->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        // Validation différente selon si num est fourni ou non
        if ($num === null) {
            // Endpoint sans num: pas de password requis
            $request->validate([
                'type' => 'required|in:telephone,code_marchand',
                'identifiant_destinataire' => 'required|string',
                'montant' => 'required|integer|min:100|max:50000000',
                'motif' => 'nullable|string|max:255',
            ]);
        } else {
            // Endpoint avec num: password requis pour compatibilité
            $request->validate([
                'type' => 'required|in:telephone,code_marchand',
                'identifiant_destinataire' => 'required|string',
                'montant' => 'required|integer|min:100|max:50000000',
                'password' => 'required|string',
                'motif' => 'nullable|string|max:255',
            ]);
            
            // Vérifier le mot de passe seulement si fourni
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Mot de passe incorrect'], 403);
            }
        }

        // Vérifier que le solde est suffisant
        if ($user->compte->solde < $request->montant) {
            return response()->json(['error' => 'Solde insuffisant'], 422);
        }

        $destinataire = null;

        if ($request->type === 'telephone') {
            // Paiement par numéro de téléphone
            if (!preg_match('/^(78|77)\d{7}$/', $request->identifiant_destinataire)) {
                return response()->json(['error' => 'Numéro de téléphone invalide'], 400);
            }

            $destinataire = User::where('telephone', $request->identifiant_destinataire)->first();
            
            if (!$destinataire || !$destinataire->compte) {
                return response()->json(['error' => 'Compte destinataire non trouvé'], 404);
            }

        } elseif ($request->type === 'code_marchand') {
            // Paiement par code marchand
            $marchandCode = MarchandCode::where('code_marchand', $request->identifiant_destinataire)
                ->where('actif', true)
                ->first();
            
            if (!$marchandCode) {
                return response()->json(['error' => 'Code marchand invalide ou inactif'], 404);
            }

            $destinataire = $marchandCode->user;
        }

        // Vérifier qu'on ne paie pas vers le même compte
        if ($user->id === $destinataire->id) {
            return response()->json(['error' => 'Impossible de payer vers le même compte'], 400);
        }

        // Effectuer le paiement en transaction
        try {
            DB::beginTransaction();

            // Déduire le montant du compte source
            $user->compte->decrement('solde', $request->montant);

            // Ajouter le montant au compte destination
            $destinataire->compte->increment('solde', $request->montant);

            // Créer la transaction
            $transaction = Transaction::create([
                'compte_source_id' => $user->compte->id,
                'compte_dest_id' => $destinataire->compte->id,
                'montant' => $request->montant,
                'type' => 'paiement',
                'statut' => 'completed',
                'motif' => $request->motif ?: ($request->type === 'telephone' ? 'Paiement téléphone' : 'Paiement marchand'),
                'reference' => ($request->type === 'telephone' ? 'PAY_' : 'PM_') . time() . '_' . rand(1000, 9999),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Paiement effectué avec succès',
                'transaction' => $transaction->load(['compteSource.user', 'compteDest.user']),
                'destinataire' => [
                        'nom' => $destinataire->nom,
                        'prenom' => $destinataire->prenom,
                        'role' => $destinataire->role ?? null
                    ],
                'solde_restant' => $user->fresh()->compte->solde
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors du paiement'], 500);
        }
    }
}