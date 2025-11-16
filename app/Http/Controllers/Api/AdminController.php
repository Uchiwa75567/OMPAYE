<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Compte;
use App\Models\MarchandCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * @OA\Info(
 *   title="OM Paye API",
 *   version="1.0.0",
 *   description="API pour OM Paye - Système de paiement mobile"
 * )
 * @OA\SecurityScheme(
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   securityScheme="bearerAuth",
 *   description="Token JWT pour l'authentification"
 * )
 */
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'admin']);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/users",
     *   tags={"Admin"},
     *   summary="Liste des utilisateurs avec filtres",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="type",
     *     in="query",
     *     description="Filtrer par type d'utilisateur",
     *     @OA\Schema(type="string", enum={"admin", "marchand", "utilisateur"})
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Rechercher par nom, prénom ou téléphone",
     *     @OA\Schema(type="string")
     *   ),
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
     *   @OA\Response(response=200, description="Liste des utilisateurs", @OA\JsonContent(
     *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="pagination", type="object")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function users(Request $request)
    {
        $query = User::with(['compte', 'marchandCode']);

        // Filtrer par type (paramètre kept for backward compatibility) -> map to 'role'
        if ($request->has('type') && $request->type) {
            $query->where('role', $request->type);
        }

        // Recherche par nom, prénom ou téléphone
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'ilike', "%$search%")
                  ->orWhere('prenom', 'ilike', "%$search%")
                  ->orWhere('telephone', 'ilike', "%$search%")
                  ->orWhere('cni', 'ilike', "%$search%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($users);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/users/{id}",
     *   tags={"Admin"},
     *   summary="Détails d'un utilisateur spécifique",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID de l'utilisateur",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Détails utilisateur", @OA\JsonContent(
     *     @OA\Property(property="user", type="object"),
     *     @OA\Property(property="compte", type="object"),
     *     @OA\Property(property="transactions", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="statistiques", type="object")
     *   )),
     *   @OA\Response(response=404, description="Utilisateur non trouvé"),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function userDetails(Request $request, $id)
    {
        $user = User::with(['compte', 'marchandCode'])->find($id);
        
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Obtenir les transactions récentes
        $transactions = Transaction::where(function($query) use ($user) {
                if ($user->compte) {
                    $query->where('compte_source_id', $user->compte->id)
                          ->orWhere('compte_dest_id', $user->compte->id);
                }
            })
            ->with(['compteSource.user', 'compteDest.user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculer les statistiques
        $stats = [];
        if ($user->compte) {
            $thisMonthStart = Carbon::now()->startOfMonth();
            $transactionsDuMois = Transaction::where(function($query) use ($user) {
                    $query->where('compte_source_id', $user->compte->id)
                          ->orWhere('compte_dest_id', $user->compte->id);
                })
                ->where('created_at', '>=', $thisMonthStart)
                ->get();

            $stats = [
                'solde_actuel' => $user->compte->solde,
                'transactions_mois' => $transactionsDuMois->count(),
                'total_depenses_mois' => $transactionsDuMois->where('compte_source_id', $user->compte->id)->sum('montant'),
                'total_recettes_mois' => $transactionsDuMois->where('compte_dest_id', $user->compte->id)->sum('montant'),
                'derniere_transaction' => $transactions->first() ? $transactions->first()->created_at->toISOString() : null
            ];
        }

        return response()->json([
            'user' => $user,
            'transactions' => $transactions,
            'statistiques' => $stats
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/transactions",
     *   tags={"Admin"},
     *   summary="Toutes les transactions avec filtres",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="type",
     *     in="query",
     *     description="Filtrer par type de transaction",
     *     @OA\Schema(type="string", enum={"transfert", "paiement", "depot", "retrait"})
     *   ),
     *   @OA\Parameter(
     *     name="statut",
     *     in="query",
     *     description="Filtrer par statut",
     *     @OA\Schema(type="string", enum={"pending", "completed", "failed", "cancelled"})
     *   ),
     *   @OA\Parameter(
     *     name="date_debut",
     *     in="query",
     *     description="Date de début (YYYY-MM-DD)",
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="date_fin",
     *     in="query",
     *     description="Date de fin (YYYY-MM-DD)",
     *     @OA\Schema(type="string", format="date")
     *   ),
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
     *   @OA\Response(response=200, description="Liste des transactions", @OA\JsonContent(
     *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="pagination", type="object")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['compteSource.user', 'compteDest.user']);

        // Filtrer par type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filtrer par statut
        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        // Filtrer par date
        if ($request->has('date_debut') && $request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && $request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $perPage = $request->get('per_page', 20);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/statistiques",
     *   tags={"Admin"},
     *   summary="Statistiques globales du système",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Statistiques globales", @OA\JsonContent(
     *     @OA\Property(property="utilisateurs", type="object",
     *       @OA\Property(property="total", type="integer"),
     *       @OA\Property(property="utilisateurs_normaux", type="integer"),
     *       @OA\Property(property="marchands", type="integer"),
     *       @OA\Property(property="admins", type="integer")
     *     ),
     *     @OA\Property(property="transactions", type="object",
     *       @OA\Property(property="total", type="integer"),
     *       @OA\Property(property="total_montant", type="integer"),
     *       @OA\Property(property="transferts", type="integer"),
     *       @OA\Property(property="paiements", type="integer"),
     *       @OA\Property(property="depots", type="integer"),
     *       @OA\Property(property="retraits", type="integer")
     *     ),
     *     @OA\Property(property="solde_total", type="integer"),
     *     @OA\Property(property="periode", type="string")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function statistiques(Request $request)
    {
        // Statistiques utilisateurs
        $statsUtilisateurs = [
            'total' => User::count(),
            // Query by 'role' column (standardized)
            'utilisateurs_normaux' => User::where('role', 'utilisateur')->count(),
            'marchands' => User::where('role', 'marchand')->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];

        // Statistiques transactions (mois en cours)
        $thisMonthStart = Carbon::now()->startOfMonth();
        $transactionsMois = Transaction::where('created_at', '>=', $thisMonthStart);
        
        $statsTransactions = [
            'total' => $transactionsMois->count(),
            'total_montant' => $transactionsMois->sum('montant'),
            'transferts' => $transactionsMois->where('type', 'transfert')->count(),
            'paiements' => $transactionsMois->where('type', 'paiement')->count(),
            'depots' => $transactionsMois->where('type', 'depot')->count(),
            'retraits' => $transactionsMois->where('type', 'retrait')->count(),
        ];

        // Solde total dans le système
        $soldeTotal = Compte::sum('solde');

        return response()->json([
            'utilisateurs' => $statsUtilisateurs,
            'transactions' => $statsTransactions,
            'solde_total' => $soldeTotal,
            'periode' => 'Mois en cours (' . Carbon::now()->format('F Y') . ')'
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/admin/marchands",
     *   tags={"Admin"},
     *   summary="Liste des marchands avec leurs codes",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="actif",
     *     in="query",
     *     description="Filtrer par statut actif/inactif",
     *     @OA\Schema(type="boolean")
     *   ),
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
     *   @OA\Response(response=200, description="Liste des marchands", @OA\JsonContent(
     *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *     @OA\Property(property="pagination", type="object")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function marchands(Request $request)
    {
    $query = User::with(['compte', 'marchandCode'])->where('role', 'marchand');

        if ($request->has('actif') && $request->actif !== null) {
            if ($request->actif) {
                $query->whereHas('marchandCode', function($q) {
                    $q->where('actif', true);
                });
            } else {
                $query->whereDoesntHave('marchandCode', function($q) {
                    $q->where('actif', true);
                });
            }
        }

        $perPage = $request->get('per_page', 20);
        $marchands = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($marchands);
    }

    /**
     * @OA\Put(
     *   path="/api/admin/marchands/{id}/toggle-status",
     *   tags={"Admin"},
     *   summary="Activer/désactiver un code marchand",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID de l'utilisateur marchand",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"actif"},
     *       @OA\Property(property="actif", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=200, description="Statut mis à jour", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="marchand", type="object")
     *   )),
     *   @OA\Response(response=404, description="Marchand non trouvé"),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function toggleMarchandStatus(Request $request, $id)
    {
        $user = User::with('marchandCode')->find($id);
        
        if (!$user || ($user->role ?? null) !== 'marchand') {
            return response()->json(['error' => 'Marchand non trouvé'], 404);
        }

        $request->validate([
            'actif' => 'required|boolean',
        ]);

        if ($user->marchandCode) {
            $user->marchandCode->update(['actif' => $request->actif]);
        } else {
            // Créer le code marchand s'il n'existe pas
            $code = 'M' . rand(100000, 999999);
            MarchandCode::create([
                'user_id' => $user->id,
                'code_marchand' => $code,
                'actif' => $request->actif,
            ]);
        }

        return response()->json([
            'message' => 'Statut du marchand mis à jour',
            'marchand' => $user->fresh(['marchandCode'])
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/admin/users/{id}",
     *   tags={"Admin"},
     *   summary="Supprimer un utilisateur",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID de l'utilisateur à supprimer",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Utilisateur supprimé", @OA\JsonContent(
     *     @OA\Property(property="message", type="string")
     *   )),
     *   @OA\Response(response=404, description="Utilisateur non trouvé"),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Accès administrateur requis")
     * )
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Empêcher la suppression de soi-même
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'Impossible de supprimer votre propre compte'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}