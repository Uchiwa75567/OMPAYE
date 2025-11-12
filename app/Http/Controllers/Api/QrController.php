<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;

class QrController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/qr/{code}",
     *     tags={"Marchand"},
     *     summary="Afficher les infos du QR code",
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Infos QR", @OA\JsonContent(
     *         @OA\Property(property="marchand", type="string"),
     *         @OA\Property(property="montant", type="integer"),
     *         @OA\Property(property="code", type="string")
     *     )),
     *     @OA\Response(response=404, description="QR non trouvé")
     * )
     */
    public function show($code)
    {
        $qr = QrCode::where('code', $code)->first();

        if (!$qr || $qr->statut !== 'active' || $qr->expires_at < now()) {
            return response()->json(['error' => 'QR code invalide'], 404);
        }

        return response()->json([
            'marchand' => $qr->marchand->nom . ' ' . $qr->marchand->prenom,
            'montant' => $qr->montant,
            'code' => $qr->code,
        ]);
    }
}