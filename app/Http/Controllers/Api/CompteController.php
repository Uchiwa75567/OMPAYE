<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/compte",
     *   summary="Afficher le solde du compte",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Solde affiché", @OA\JsonContent(
     *     @OA\Property(property="solde", type="integer"),
     *     @OA\Property(property="type", type="string")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $compte = $user->compte;

        return response()->json([
            'solde' => $compte ? intval($compte->solde / 100) : 0, // Masquer les centimes
            'type' => $compte ? $compte->type : null,
        ]);
    }
}