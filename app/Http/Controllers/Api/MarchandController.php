<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarchandController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/marchand/generate-qr",
     *   tags={"Marchand"},
     *   summary="Générer un QR code de paiement",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"montant"},
     *       @OA\Property(property="montant", type="integer", example=15000)
     *     )
     *   ),
     *   @OA\Response(response=200, description="QR généré", @OA\JsonContent(
     *     @OA\Property(property="code", type="string"),
     *     @OA\Property(property="lien", type="string")
     *   )),
     *   @OA\Response(response=401, description="Non autorisé"),
     *   @OA\Response(response=403, description="Rôle marchand requis")
     * )
     */
    public function generateQr(Request $request)
    {
        $request->validate([
            'montant' => 'required|integer|min:100',
        ]);

        $user = $request->user();

        if ($user->role !== 'marchand') {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $code = Str::random(10);

        $qr = QrCode::create([
            'marchand_id' => $user->id,
            'montant' => $request->montant,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(30),
        ]);

        return response()->json([
            'code' => $code,
            'lien' => url('/api/qr/' . $code),
        ]);
    }
}