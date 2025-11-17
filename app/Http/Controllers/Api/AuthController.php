<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsVerification;
use App\Models\User;
use App\Models\Compte;
use App\Models\MarchandCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Twilio\Rest\Client;

/**
 */
class AuthController extends Controller
{
    /**
     * Format un numéro de téléphone pour l'international
     */
    private function formatPhoneNumber($phoneNumber)
    {
        $cleanNumber = preg_replace('/\D/', '', $phoneNumber);
        
        if (str_starts_with($cleanNumber, '221')) {
            return '+' . $cleanNumber;
        }
        
        if (str_starts_with($cleanNumber, '0')) {
            return '+221' . substr($cleanNumber, 1);
        }
        
        if (str_starts_with($cleanNumber, '7')) {
            return '+221' . $cleanNumber;
        }
        
        return '+' . $cleanNumber;
    }

    /**
     * Envoi SMS avec fallback
     */
    private function sendSmsWithFallback($formattedNumber, $code)
    {
        $message = "Votre code OTP OM Paye: $code. Valable 5 minutes.";
        $result = $this->sendWithTwilio($formattedNumber, $message);
        
        if ($result['success']) {
            \Log::info("SMS envoyé - Numéro: $formattedNumber, Code: $code");
            return ['success' => true, 'message' => 'SMS envoyé'];
        }
        
        // En mode développement, simuler l'envoi
        if (app()->environment('local')) {
            \Log::info("Mode développement - SMS simulé - Numéro: $formattedNumber, Code: $code");
            return ['success' => true, 'message' => 'SMS simulé (mode développement)'];
        }
        
        return ['success' => false, 'message' => 'Erreur envoi SMS'];
    }

    /**
     * Envoi SMS via Twilio
     */
    private function sendWithTwilio($formattedNumber, $message)
    {
        try {
            $twilioSid = env('TWILIO_SID');
            $twilioToken = env('TWILIO_TOKEN');
            $twilioFrom = env('TWILIO_FROM', '+1234567890');
            
            if (!$twilioSid || !$twilioToken) {
                return ['success' => false, 'message' => 'Configuration Twilio manquante'];
            }
            
            $twilio = new Client($twilioSid, $twilioToken);
            $twilio->messages->create($formattedNumber, [
                'from' => $twilioFrom,
                'body' => $message
            ]);
            
            return ['success' => true, 'message' => 'SMS envoyé via Twilio'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur Twilio: ' . $e->getMessage()];
        }
    }

    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   tags={"Auth"},
     *   summary="Créer un nouveau compte (Admin uniquement)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nom", "prenom", "cni", "telephone", "type"},
     *       @OA\Property(property="nom", type="string", example="Dupont"),
     *       @OA\Property(property="prenom", type="string", example="Jean"),
     *       @OA\Property(property="cni", type="string", example="123456789"),
     *       @OA\Property(property="telephone", type="string", example="782345678"),
     *       @OA\Property(property="sexe", type="string", enum={"M", "F"}, example="M"),
     *       @OA\Property(property="type", type="string", enum={"marchand", "utilisateur"}, example="utilisateur"),
     *       @OA\Property(property="password", type="string", example="motdepasse123")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Compte créé avec succès"),
     *   @OA\Response(response=403, description="Accès non autorisé"),
     *   @OA\Response(response=422, description="Données invalides")
     * )
     */
    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   tags={"Auth"},
     *   summary="Créer un nouveau compte utilisateur (admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nom", "prenom", "email", "telephone", "sexe", "type"},
     *       @OA\Property(property="nom", type="string", example="Diallo"),
     *       @OA\Property(property="prenom", type="string", example="Amadou"),
     *       @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
     *       @OA\Property(property="telephone", type="string", example="781234567"),
     *       @OA\Property(property="sexe", type="string", enum={"M", "F"}, example="M"),
     *       @OA\Property(property="type", type="string", enum={"marchand", "utilisateur"}, example="utilisateur"),
     *       @OA\Property(property="password", type="string", example="motdepasse123")
     *     )
     *   ),
     *   @OA\Response(response=201, description="Compte créé avec succès"),
     *   @OA\Response(response=403, description="Accès non autorisé - admin only"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est authentifié et est un admin
        if (!$user || $user->role !== 'admin') {
            return response()->json(['error' => 'Seuls les administrateurs peuvent créer des comptes'], 403);
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'telephone' => 'required|string|unique:users,telephone',
            'sexe' => 'required|string|in:M,F',
            'type' => 'required|string|in:marchand,utilisateur',
            'password' => 'nullable|string|min:6',
        ]);

        // Créer l'utilisateur
        $userData = [
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'sexe' => $request->sexe,
            // Keep request param name 'type' for backward compatibility but store it in DB as 'role'
            'role' => $request->type,
            'active' => false, // Compte inactif par défaut jusqu'à vérification OTP
        ];

        if ($request->password) {
            $userData['password'] = Hash::make($request->password);
        } else {
            // Mot de passe par défaut
            $userData['password'] = Hash::make('default_password_123');
        }

        $newUser = User::create($userData);

        // Créer automatiquement un compte pour l'utilisateur
        Compte::create([
            'user_id' => $newUser->id,
            'solde' => 0, // Solde initial
            'type' => $request->type === 'marchand' ? 'marchand' : 'client',
        ]);

        // Si c'est un marchand, générer automatiquement un code marchand
        if ($request->type === 'marchand') {
            $marchandCode = $this->generateMarchandCode();
            MarchandCode::create([
                'user_id' => $newUser->id,
                'code_marchand' => $marchandCode,
                'actif' => true,
            ]);
        }

        return response()->json([
            'message' => 'Compte créé avec succès',
            'user' => $newUser->load('compte'),
            'code_marchand' => $request->type === 'marchand' ? $this->generateMarchandCode() : null
        ], 201);
    }

    /**
     * Générer un code marchand unique
     */
    private function generateMarchandCode()
    {
        do {
            $code = 'M' . rand(100000, 999999);
        } while (MarchandCode::where('code_marchand', $code)->exists());
        
        return $code;
    }

    /**
     * @OA\Post(
     *   path="/api/auth/send-otp",
     *   tags={"Auth"},
     *   summary="Envoyer un code OTP par SMS",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone"},
     *       @OA\Property(property="telephone", type="string", example="782345678")
     *     )
     *   ),
     *   @OA\Response(response=200, description="OTP envoyé"),
     *   @OA\Response(response=400, description="Numéro invalide")
     * )
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();
        
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Générer code OTP
        $code = rand(100000, 999999);
        $sessionId = Str::uuid();

        // Supprimer les anciens OTP non utilisés
        SmsVerification::where('telephone', $request->telephone)
            ->where('used', false)
            ->delete();

        // Créer nouveau OTP
        SmsVerification::create([
            'telephone' => $request->telephone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'used' => false,
        ]);

        // Format du numéro pour l'international
        $formattedNumber = $this->formatPhoneNumber($request->telephone);

        // Envoyer SMS
        $smsResult = $this->sendSmsWithFallback($formattedNumber, $code);
        
        if (!$smsResult['success']) {
            \Log::error("Erreur SMS pour " . $request->telephone . ": " . $smsResult['message']);
            
            // En mode développement, retourner le code
            if (app()->environment('local')) {
                return response()->json([
                    'message' => 'OTP envoyé (mode développement)',
                    'session_id' => $sessionId,
                    'otp_code' => $code, // Pour les tests en développement
                    'note' => 'Code OTP affiché en mode développement'
                ]);
            }
            
            return response()->json(['error' => 'Erreur envoi SMS: ' . $smsResult['message']], 500);
        }

        // En mode local/développement, inclure aussi le code OTP dans la réponse
        $response = [
            'message' => 'OTP envoyé avec succès',
            'session_id' => $sessionId,
        ];
        
        if (app()->environment('local')) {
            $response['otp_code'] = $code; // Pour les tests en développement
            $response['note'] = 'Code OTP affiché en mode développement';
        }
        
        return response()->json($response);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/verify-otp",
     *   tags={"Auth"},
     *   summary="Vérifier le code OTP et obtenir un token",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone", "code"},
     *       @OA\Property(property="telephone", type="string", example="782345678"),
     *       @OA\Property(property="code", type="string", example="123456")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Token généré", @OA\JsonContent(
     *     @OA\Property(property="access_token", type="string"),
     *     @OA\Property(property="refresh_token", type="string"),
     *     @OA\Property(property="token_type", type="string"),
     *     @OA\Property(property="expires_at", type="string")
     *   )),
     *   @OA\Response(response=400, description="Code invalide")
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string|exists:users,telephone',
            'code' => 'required|string|size:6',
        ]);

        $verification = SmsVerification::where('telephone', $request->telephone)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['error' => 'Code OTP invalide ou expiré'], 400);
        }

        $user = User::where('telephone', $request->telephone)->first();

        // Marquer l'OTP comme utilisé
        $verification->update(['used' => true]);

        // Activer le compte après vérification OTP réussie
        $user->update(['active' => true]);

        // Générer access token (30 jours) - sans scopes pour éviter l'erreur OAuth
        $accessToken = $user->createToken('Mobile App Token', [], Carbon::now()->addDays(30))->accessToken;
        
        // Générer refresh token (90 jours)
        $refreshToken = $user->createToken('Mobile App Refresh Token', [], Carbon::now()->addDays(90))->accessToken;

        // Créer le compte s'il n'existe pas
        if (!$user->compte) {
            Compte::create([
                'user_id' => $user->id,
                'solde' => 0,
                'type' => ($user->role ?? null) === 'marchand' ? 'marchand' : 'client',
            ]);
        }

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addDays(30)->toISOString(),
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   tags={"Auth"},
     *   summary="Connexion avec téléphone et mot de passe",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone", "password"},
     *       @OA\Property(property="telephone", type="string", example="782345678"),
     *       @OA\Property(property="password", type="string", example="motdepasse123")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Connexion réussie", @OA\JsonContent(
     *     @OA\Property(property="access_token", type="string"),
     *     @OA\Property(property="token_type", type="string"),
     *     @OA\Property(property="user", type="object")
     *   )),
     *   @OA\Response(response=401, description="Identifiants invalides")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string|exists:users,telephone',
            'password' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        // Créer le compte s'il n'existe pas
        if (!$user->compte) {
            Compte::create([
                'user_id' => $user->id,
                'solde' => 0,
                'type' => ($user->role ?? null) === 'marchand' ? 'marchand' : 'client',
            ]);
        }

        $token = $user->createToken('Mobile App Token', [], Carbon::now()->addDays(30))->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('compte'),
            'expires_at' => Carbon::now()->addDays(30)->toISOString(),
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   tags={"Auth"},
     *   summary="Déconnexion",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Déconnexion réussie"),
     *   @OA\Response(response=401, description="Token invalide")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * @OA\Get(
     *   path="/api/auth/me",
     *   tags={"Auth"},
     *   summary="Obtenir les informations de l'utilisateur connecté",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Informations utilisateur", @OA\JsonContent(
     *     @OA\Property(property="user", type="object")
     *   )),
     *   @OA\Response(response=401, description="Token invalide")
     * )
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('compte')
        ]);
    }
}