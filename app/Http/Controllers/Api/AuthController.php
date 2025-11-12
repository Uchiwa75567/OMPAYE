<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsVerification;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Twilio\Rest\Client;

// Import AfricasTalking
use AfricasTalking\SDK\AfricasTalking;

// Import MessageBird
use MessageBird\Client as MessageBirdClient;
use MessageBird\Objects\SendMessage as MB_SendMessage;

/**
 * @OA\Info(
 *   title="Orange Money API",
 *   version="1.0.0",
 *   description="API for Orange Money Senegal"
 * )
 */
class AuthController extends Controller
{
    /**
     * Format un numéro de téléphone pour l'international
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Supprimer tous les caractères non numériques
        $cleanNumber = preg_replace('/\D/', '', $phoneNumber);
        
        // Gestion du numéro sénégalais
        if (str_starts_with($cleanNumber, '221')) {
            return '+' . $cleanNumber;
        }
        
        // Si commence par 0, le remplacer par +221
        if (str_starts_with($cleanNumber, '0')) {
            return '+221' . substr($cleanNumber, 1);
        }
        
        // Si commence par 7 (numéro local sénégalais)
        if (str_starts_with($cleanNumber, '7')) {
            return '+221' . $cleanNumber;
        }
        
        // Sinon, retourner tel quel (peut déjà avoir le +)
        return '+' . $cleanNumber;
    }

    /**
     * Envoi SMS avec système multi-providers (Twilio + MessageBird + AfricasTalking)
     */
    private function sendSmsWithFallback($formattedNumber, $code)
    {
        $message = "Votre code de vérification OM Paye: $code. Valable 5 minutes.";
        $preferredProvider = env('SMS_PROVIDER', 'twilio');
        $verifiedNumber = env('TWILIO_VERIFIED_NUMBER', '+221785052217');
        
        // Essayer d'abord Twilio (provider principal)
        if ($preferredProvider === 'twilio') {
            $result = $this->sendWithTwilio($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via Twilio - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via Twilio'];
            }
            
            // Si Twilio échoue, essayer MessageBird en fallback
            \Log::warning("Twilio échoué, tentative fallback MessageBird: " . $result['message']);
            $result = $this->sendWithMessageBird($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via MessageBird (fallback) - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via MessageBird (fallback)'];
            }
            
            // Si MessageBird échoue, essayer AfricasTalking en dernier fallback
            \Log::warning("MessageBird échoué, tentative fallback AfricasTalking: " . $result['message']);
            $result = $this->sendWithAfricasTalking($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via AfricasTalking (fallback) - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via AfricasTalking (fallback)'];
            }
            
            \Log::error("Tous les providers SMS ont échoué: " . $result['message']);
            return ['success' => false, 'message' => 'Tous les providers SMS ont échoué'];
        } else {
            // Essayer MessageBird en premier
            $result = $this->sendWithMessageBird($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via MessageBird - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via MessageBird'];
            }
            
            // Si MessageBird échoue, essayer Twilio en fallback
            \Log::warning("MessageBird échoué, tentative fallback Twilio: " . $result['message']);
            $result = $this->sendWithTwilio($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via Twilio (fallback) - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via Twilio (fallback)'];
            }
            
            // Si Twilio échoue, essayer AfricasTalking en dernier fallback
            \Log::warning("Twilio échoué, tentative fallback AfricasTalking: " . $result['message']);
            $result = $this->sendWithAfricasTalking($formattedNumber, $message);
            if ($result['success']) {
                \Log::info("SMS envoyé via AfricasTalking (fallback) - Numéro: $formattedNumber, Code: $code");
                return ['success' => true, 'message' => 'SMS envoyé via AfricasTalking (fallback)'];
            }
            
            \Log::error("Tous les providers SMS ont échoué: " . $result['message']);
            return ['success' => false, 'message' => 'Tous les providers SMS ont échoué'];
        }
    }

    /**
     * Envoi SMS via MessageBird (100 SMS gratuits/mois)
     */
    private function sendWithMessageBird($formattedNumber, $message)
    {
        try {
            $accessKey = env('MESSAGEBIRD_ACCESS_KEY');
            $originator = env('MESSAGEBIRD_ORIGINATOR', 'OMPaye');
            
            if (!$accessKey || $accessKey === 'your_messagebird_access_key_here') {
                return ['success' => false, 'message' => 'Configuration MessageBird incomplète'];
            }
            
            // Supprimer le + du numéro pour MessageBird
            $number = ltrim($formattedNumber, '+');
            
            // Instance MessageBird
            $MessageBird = new MessageBirdClient($accessKey);
            
            // Créer le message
            $Message = new MB_SendMessage();
            $Message->originator = $originator;
            $Message->recipients = [$number];
            $Message->body = $message;
            
            // Envoyer le message
            $result = $MessageBird->messages->create($Message);
            
            if ($result && isset($result->recipients[0]->status)) {
                if (in_array($result->recipients[0]->status, ['sent', 'delivered'])) {
                    return ['success' => true, 'message' => 'SMS envoyé via MessageBird'];
                } else {
                    return ['success' => false, 'message' => 'MessageBird: Status ' . $result->recipients[0]->status];
                }
            } else {
                return ['success' => false, 'message' => 'MessageBird: Réponse invalide'];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur MessageBird: ' . $e->getMessage()];
        }
    }

    /**
     * Envoi SMS via AfricasTalking (optimisé pour l'Afrique)
     */
    private function sendWithAfricasTalking($formattedNumber, $message)
    {
        try {
            $username = env('AFRIKASTALKING_USERNAME');
            $apiKey = env('AFRIKASTALKING_API_KEY');
            
            if (!$username || !$apiKey || $username === 'sandbox' || $apiKey === 'sandbox') {
                return ['success' => false, 'message' => 'Configuration AfricasTalking incomplète'];
            }
            
            // Supprimer le + du numéro pour AfricasTalking
            $number = ltrim($formattedNumber, '+');
            
            $AT = new AfricasTalking($username, $apiKey);
            $sms = $AT->sms();
            
            $response = $sms->send([
                'to' => $number,
                'message' => $message
            ]);
            
            if (isset($response['data']->MessageCount) && $response['data']->MessageCount > 0) {
                return ['success' => true, 'message' => 'SMS envoyé via AfricasTalking'];
            } else {
                return ['success' => false, 'message' => 'AfricasTalking: Aucun message envoyé'];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur AfricasTalking: ' . $e->getMessage()];
        }
    }

    /**
     * Envoi SMS via Twilio optimisé pour numéros sénégalais
     */
    private function sendWithTwilio($formattedNumber, $message)
    {
        try {
            $twilioSid = env('TWILIO_SID');
            $twilioToken = env('TWILIO_TOKEN');
            $twilioFrom = env('TWILIO_FROM');
            $verifiedNumber = env('TWILIO_VERIFIED_NUMBER', '+221785052217');
            
            if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                return ['success' => false, 'message' => 'Configuration Twilio incomplète'];
            }
            
            // Vérifier si c'est un numéro sénégalais
            if (str_contains($formattedNumber, '+221') || preg_match('/^2217\d{7}$/', $formattedNumber)) {
                // C'est un numéro sénégalais - on va utiliser une approche différente
                // On utilise notre propre numéro vérifié comme originateur si possible
                
                $twilio = new Client($twilioSid, $twilioToken);
                
                // Essayer avec le numéro FROM configuré d'abord
                try {
                    $twilio->messages->create($formattedNumber, [
                        'from' => $twilioFrom,
                        'body' => $message
                    ]);
                    return ['success' => true, 'message' => 'SMS envoyé via Twilio (numéro sénégalais)'];
                } catch (\Twilio\Exceptions\TwilioException $e) {
                    // Si ça échoue à cause du numéro sénégalais, essayer avec le numéro vérifié comme FROM
                    if (strpos($e->getMessage(), 'unverified') !== false || strpos($e->getMessage(), 'invalid') !== false) {
                        \Log::info("Twilio Sénégal: Tentative avec numéro vérifié comme FROM");
                        
                        // Alternative: On pourrait envoyer à notre numéro vérifié et laisser l'utilisateur reporter
                        // Mais pour l'instant, on retourne l'erreur spécifique
                        return ['success' => false, 'message' => 'Numéro sénégalais non vérifié. Twilio Trial: vérifier ' . $formattedNumber . ' dans la console Twilio ou utiliser un numéro FROM sénégalais'];
                    }
                    throw $e;
                }
            } else {
                // Numéro international normal
                $twilio = new Client($twilioSid, $twilioToken);
                $twilio->messages->create($formattedNumber, [
                    'from' => $twilioFrom,
                    'body' => $message
                ]);
                return ['success' => true, 'message' => 'SMS envoyé via Twilio'];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur Twilio: ' . $e->getMessage()];
        }
    }

    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   tags={"Auth"},
     *   summary="Demande de code SMS",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"telephone"},
     *       @OA\Property(property="telephone", type="string", example="771234567")
     *     )
     *   ),
     *   @OA\Response(response=200, description="SMS envoyé", @OA\JsonContent(
     *     @OA\Property(property="message", type="string"),
     *     @OA\Property(property="session_id", type="string")
     *   )),
     *   @OA\Response(response=400, description="Erreur")
     * )
     */
    public function login(Request $request)
    {
        // Validation personnalisée pour le numéro Orange sénégalais
        $telephone = $request->telephone;
        if (!preg_match('/^(78|77)\d{7}$/', $telephone)) {
            return response()->json([
                'error' => 'Le numéro doit être un numéro Orange sénégalais valide (9 chiffres commençant par 77 ou 78)'
            ], 400);
        }

        // Vérifier si l'utilisateur existe, sinon le créer automatiquement
        $user = User::where('telephone', $request->telephone)->first();
        
        if (!$user) {
            // Créer automatiquement un nouveau utilisateur Orange avec mot de passe temporaire
            $user = User::create([
                'nom' => 'Utilisateur', // Par défaut
                'prenom' => 'Orange',
                'telephone' => $request->telephone,
                'sexe' => 'M', // Par défaut
                'password' => Hash::make('tmp_password_123'), // Mot de passe temporaire
                'role' => 'client', // Rôle par défaut
            ]);
            
            // Créer automatiquement un compte pour le nouvel utilisateur
            Compte::create([
                'user_id' => $user->id,
                'type' => 'client',
            ]);
            
            \Log::info("Nouvel utilisateur créé automatiquement - Téléphone: {$request->telephone}");
        } else {
            // Si l'utilisateur a déjà un mot de passe défini (pas temporaire), informer qu'il peut utiliser le login par mot de passe
            $tempPassword = Hash::make('tmp_password_123');
            if ($user->password !== $tempPassword) {
                // L'utilisateur a déjà un mot de passe, il peut se connecter directement
                return response()->json([
                    'message' => 'Utilisateur existant avec mot de passe défini. Utilisez /api/auth/login-password pour vous connecter.',
                    'has_password' => true,
                    'telephone' => $user->telephone
                ]);
            }
        }

        // Generate SMS code
        $code = rand(100000, 999999);
        $sessionId = Str::uuid();

        SmsVerification::create([
            'telephone' => $request->telephone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Format du numéro pour l'international (Sénégal: +221)
        $formattedNumber = $this->formatPhoneNumber($request->telephone);

        // Vérifier si le mode simulation est activé pour CE numéro
        $isSimulation = false;
        
        if (env('SMS_SIMULATION', false)) {
            $simNumbers = explode(',', env('SMS_SIMULATION_NUMBERS', ''));
            
            // Vérifier le numéro principal
            if (env('SMS_SIMULATION_PHONE') === $formattedNumber) {
                $isSimulation = true;
            } else {
                // Vérifier les numéros supplémentaires
                foreach ($simNumbers as $simNumber) {
                    $simNumber = trim($simNumber);
                    if ($simNumber && str_contains($formattedNumber, $simNumber)) {
                        $isSimulation = true;
                        break;
                    }
                }
            }
        }

        if ($isSimulation) {
            // Mode simulation - afficher le code dans la réponse
            \Log::info("SMS SIMULATION - Numéro: $formattedNumber, Code: $code");
            
            // Stocker le code pour la vérification SMS même en mode simulation
            SmsVerification::create([
                'telephone' => $request->telephone,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);
            
            return response()->json([
                'message' => 'Code SMS envoyé (Mode Simulation)',
                'session_id' => $sessionId,
                'simulation' => true,
                'sms_code' => $code, // Code affiché pour les tests
                'note' => 'Mode simulation activé - SMS envoyé par simulation'
            ]);
        }

        // Envoi SMS via système multi-providers
        $smsResult = $this->sendSmsWithFallback($formattedNumber, $code);
        
        if (!$smsResult['success']) {
            \Log::error("Erreur SMS: " . $smsResult['message']);
            return response()->json(['error' => 'Erreur envoi SMS: ' . $smsResult['message']], 500);
        }

        return response()->json([
            'message' => 'Code SMS envoyé',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/verify-sms",
     *   summary="Vérifier le code SMS et obtenir le token",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"code"},
     *       @OA\Property(property="code", type="string", example="123456"),
     *       @OA\Property(property="password", type="string", example="motdepasse123", nullable=true, description="Mot de passe pour les futures connexions (optionnel pour nouveaux utilisateurs)")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Token obtenu", @OA\JsonContent(
     *     @OA\Property(property="access_token", type="string"),
     *     @OA\Property(property="token_type", type="string"),
     *     @OA\Property(property="user", type="object", @OA\Property(property="nom", type="string"), @OA\Property(property="prenom", type="string")),
     *     @OA\Property(property="first_login", type="boolean", description="True si c'est le premier login")
     *   )),
     *   @OA\Response(response=400, description="Code invalide")
     * )
     */
    public function verifySms(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => 'nullable|string|min:6',
        ]);

        $verification = SmsVerification::where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['error' => 'Code invalide ou expiré'], 400);
        }

        $user = User::where('telephone', $verification->telephone)->first();
        
        $isNewUser = false;
        
        // Si c'est un nouvel utilisateur (mot de passe aléatoire) ou si un mot de passe est fourni
        if ($request->password) {
            // Définir le mot de passe fourni par l'utilisateur
            $user->update(['password' => Hash::make($request->password)]);
            $isNewUser = true;
        } elseif ($user->password === Hash::make('tmp_password_123')) {
            // C'est un nouvel utilisateur qui n'a pas encore défini de mot de passe
            return response()->json([
                'error' => 'Mot de passe requis pour la première connexion'
            ], 400);
        }

        // Create compte if not exists
        if (!$user->compte) {
            Compte::create([
                'user_id' => $user->id,
                'type' => $user->role === 'marchand' ? 'marchand' : 'client',
            ]);
        }

        $verification->update(['used' => true]);

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'first_login' => $isNewUser,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/login-password",
     *   summary="Connexion avec téléphone et mot de passe",
     *   tags={"Auth"},
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
    public function loginPassword(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string|exists:users,telephone',
            'password' => 'required|string',
        ]);

        $user = User::where('telephone', $request->telephone)->first();
        
        // Vérifier que le mot de passe n'est pas le temporaire
        $tempPassword = Hash::make('tmp_password_123');
        if ($user->password === $tempPassword) {
            return response()->json([
                'error' => 'Mot de passe temporaire. Utilisez SMS pour définir votre mot de passe.'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        // Create compte if not exists
        if (!$user->compte) {
            Compte::create([
                'user_id' => $user->id,
                'type' => $user->role === 'marchand' ? 'marchand' : 'client',
            ]);
        }

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/set-pin",
     *   summary="Définir le PIN de sécurité",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"pin"},
     *       @OA\Property(property="pin", type="string", example="1234")
     *     )
     *   ),
     *   @OA\Response(response=200, description="PIN défini"),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4|regex:/^\d{4}$/',
        ]);

        $user = $request->user();
        $user->update(['pin' => Hash::make($request->pin)]);

        return response()->json(['message' => 'PIN défini']);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete(); // Revoke old
        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   summary="Déconnexion",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Déconnexion réussie"),
     *   @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}