<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Liste des utilisateurs",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste users", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Rôle admin requis")
     * )
     */
    public function users()
    {
        return response()->json(User::with('compte')->paginate(20));
    }

    /**
     * @OA\Get(
     *     path="/api/admin/transactions",
     *     tags={"Admin"},
     *     summary="Toutes les transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste transactions", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Rôle admin requis")
     * )
     */
    public function transactions()
    {
        return response()->json(Transaction::with(['compteSource.user', 'compteDest.user'])->paginate(20));
    }

    /**
     * @OA\Post(
     *     path="/api/admin/create-marchand",
     *     tags={"Admin"},
     *     summary="Créer un compte marchand",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "telephone", "sexe", "password"},
     *             @OA\Property(property="nom", type="string", example="Dupont"),
     *             @OA\Property(property="prenom", type="string", example="Jean"),
     *             @OA\Property(property="telephone", type="string", example="781234567"),
     *             @OA\Property(property="sexe", type="string", example="M"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Marchand créé", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="user", type="object")
     *     )),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Rôle admin requis"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function createMarchand(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'telephone' => 'required|string|unique:users',
            'sexe' => 'required|in:M,F',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'sexe' => $request->sexe,
            'password' => Hash::make($request->password),
            'role' => 'marchand',
        ]);

        Compte::create([
            'user_id' => $user->id,
            'type' => 'marchand',
        ]);

        return response()->json(['message' => 'Marchand créé', 'user' => $user]);
    }
}