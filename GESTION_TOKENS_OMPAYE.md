# Guide de Gestion des Tokens OMPAYE 🔐

## Vue d'ensemble
L'application OMPAYE utilise **Laravel Passport** pour l'authentification API avec des tokens JWT (JSON Web Tokens).

## Configuration Passport ✅

### 1. Clés de Chiffrement
```bash
# Clés déjà générées et stockées dans storage/
storage/oauth-private.key
storage/oauth-public.key
```

### 2. Clients OAuth Créés ✅

#### Client Personal Access
- **ID** : `2`
- **Nom** : OMPAYE Personal Access Client
- **Usage** : Tokens personnels pour les utilisateurs
- **Scopes** : Tous les scopes (`*`)

#### Client Password Grant
- **ID** : `3`  
- **Nom** : OMPAYE Password Grant Client
- **Usage** : Authentification par mot de passe
- **Client Secret** : `Ms5OTDkPU70bQL1Vpc2o4jOXfZlk5seTK7MoYkzw`

## 🔑 Génération de Tokens

### Token de Test Créé ✅
**Utilisateur** : `781234567`  
**Token** : `eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...`  
**Scopes** : `["*"]` (tous les accès)

```bash
# Test réussi
curl -X GET "http://127.0.0.1:8083/api/compte" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." 
# Résultat : {"solde":0,"type":"client"}
```

### Commandes de Génération

#### Pour Créer de Nouveaux Tokens
```bash
# Via Tinker
php artisan tinker

# Exemple :
$user = App\Models\User::find('user-id');
$token = $user->createToken('Nom du Token', ['scope1', 'scope2'])->accessToken;

# Via Code dans l'application
$token = $user->createToken('Mobile App Token', ['api:read', 'api:write'])->accessToken;
```

#### Pour Créer de Nouveaux Clients
```bash
# Client personnel
php artisan passport:client --personal

# Client password grant
php artisan passport:client --password

# Client standard
php artisan passport:client
```

## 🛡️ Sécurité des Tokens

### Structure JWT
```json
{
  "aud": "2",           // Client ID
  "jti": "token-id",    // Token ID unique
  "iat": 1763117001,    // Issued at (timestamp)
  "nbf": 1763117001,    // Not before
  "exp": 1794653001,    // Expires at (1 an)
  "sub": "user-uuid",   // User ID
  "scopes": ["*"]       // Permissions
}
```

### Types de Tokens
1. **Access Tokens** : Pour authentification API (JWT)
2. **Refresh Tokens** : Pour renouvelement d'access tokens
3. **Personal Access Tokens** : Tokens générés manuellement

## 📋 API Endpoints Protégés

### Authentification Requise
Tous les endpoints dans `routes/api.php` avec middleware `auth:api` :

- `GET /api/compte` - Solde du compte
- `GET /api/historique` - Historique transactions
- `POST /api/transactions/*` - Opérations financières
- `POST /api/marchand/generate-qr` - Génération QR codes
- `GET /api/admin/*` - Administration (role:admin)

### Headers Requises
```bash
Authorization: Bearer {token}
Content-Type: application/json
```

## 🔄 Renouvellement de Tokens

### Via Refresh Token
```javascript
// Client côté frontend
fetch('/oauth/token', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        grant_type: 'refresh_token',
        refresh_token: 'refresh-token-here',
        client_id: 3,
        client_secret: 'Ms5OTDkPU70bQL1Vpc2o4jOXfZlk5seTK7MoYkzw',
    })
});
```

### Via API AuthController
```php
// Dans AuthController::refresh()
public function refresh(Request $request)
{
    $user = $request->user();
    $user->tokens()->delete(); // Révoquer anciens tokens
    $token = $user->createToken('Refresh Token')->accessToken;
    
    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}
```

## 🗑️ Révocation de Tokens

### Révoquer un Token Spécifique
```bash
php artisan tinker

$user = App\Models\User::find('user-id');
$user->tokens()->where('id', 'token-id')->delete();
```

### Révoquer Tous les Tokens d'un Utilisateur
```php
$user->tokens()->delete();
```

### Révoquer par Nom de Token
```php
$user->tokens()->where('name', 'Mobile App Token')->delete();
```

## 📊 Monitoring des Tokens

### Lister les Tokens d'un Utilisateur
```bash
php artisan tinker

$user = App\Models\User::find('user-id');
$tokens = $user->tokens;
foreach($tokens as $token) {
    echo "Token: {$token->id}\n";
    echo "Name: {$token->name}\n";
    echo "Created: {$token->created_at}\n";
    echo "Expires: {$token->expires_at}\n\n";
}
```

### Statistiques Globales
```php
// Nombre total de tokens
$totalTokens = \Laravel\Passport\Token::count();

// Tokens expirés
$expiredTokens = \Laravel\Passport\Token::where('expires_at', '<', now())->count();

// Tokens actifs
$activeTokens = \Laravel\Passport\Token::where('expires_at', '>', now())->count();
```

## 🔧 Configuration Avancée

### Durée de Vie des Tokens
```php
// Dans AuthServiceProvider
public function boot()
{
    Passport::tokensExpireIn(now()->addDays(30));     // 30 jours
    Passport::refreshTokensExpireIn(now()->addDays(60)); // 60 jours
    Passport::personalAccessTokensExpireIn(now()->addMonths(6)); // 6 mois
}
```

### Scopes Personnalisés
```php
// Dans AuthServiceProvider
public function boot()
{
    Passport::tokensCan([
        'read:compte' => 'Lire les informations du compte',
        'write:transaction' => 'Effectuer des transactions',
        'admin:users' => 'Gestion des utilisateurs',
    ]);
}
```

## 🛠️ Utilisation Pratique

### Frontend (JavaScript)
```javascript
// Stockage sécurisé
localStorage.setItem('access_token', token);

// Headers pour requêtes
const headers = {
    'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
    'Content-Type': 'application/json'
};

// Requête protégée
fetch('/api/compte', { headers })
    .then(response => response.json())
    .then(data => console.log(data));
```

### Mobile App (PHP/CURL)
```php
// API call avec token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8083/api/compte');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);
```

## 📝 Bonnes Pratiques

### ✅ À Faire
- Stockage sécurisé des tokens côté client
- Révocation des tokens lors de la déconnexion
- Monitoring des tokens expirés
- Utilisation de scopes spécifiques

### ❌ À Éviter
- Stockage en clair dans localStorage (préférer httpOnly cookies)
- Tokens avec tous les scopes pour tous les usages
- Ne pas vérifier l'expiration côté client
- Logs contenant des tokens complets

## 🚨 Dépannage

### Erreur 401 Unauthorized
```bash
# Vérifier que le token est valide et non expiré
curl -X GET "http://127.0.0.1:8083/api/compte" \
  -H "Authorization: Bearer {token}"
```

### Token Expiré
```json
{
  "error": "Unauthenticated.",
  "message": "Unauthenticated."
}
```
**Solution** : Utiliser refresh token ou se reconnecter

### Token Invalide
```json
{
  "error": "Token has expired",
  "message": "Token has expired"
}
```
**Solution** : Régénérer un nouveau token

---

**Dernière mise à jour** : 14 novembre 2025  
**Version** : 1.0  
**Auteur** : Kilo Code Assistant

🔐 **Votre cadenas de sécurité est maintenant en place !**