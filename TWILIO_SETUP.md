# Configuration Twilio pour OM Paye

Ce guide vous explique comment configurer Twilio pour l'envoi de SMS réel dans votre application OM Paye.

## 📋 Étapes de Configuration

### 1. Créer un Compte Twilio

1. Allez sur [twilio.com](https://www.twilio.com)
2. Créez un compte gratuit ou connectez-vous
3. Vérifiez votre email et numéro de téléphone

### 2. Obtenir vos Identifiants

1. Connectez-vous à votre **Twilio Console**
2. Allez dans **Account** → **API Keys**
3. Créez une nouvelle **API Key** ou utilisez votre **Account SID** et **Auth Token**
4. Notez ces informations :
   - **Account SID** (ex: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **Auth Token** (ex: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **Phone Number** (ex: `+14155238886`)

### 3. Vérifier un Numéro de Téléphone

1. Dans Twilio Console, allez dans **Phone Numbers** → **Verified Caller IDs**
2. Ajoutez le numéro de téléphone que vous voulez utiliser pour tester
3. **Important :** Twilio exige que les numéros de téléphone soient vérifiés avant l'envoi

### 4. Configuration de l'Application

Modifiez votre fichier `.env` avec vos vraies credentials Twilio :

```env
# Configuration Twilio pour SMS
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_FROM=+14155238886
```

## 🔧 Fonctionnement de l'Application

L'application a déjà été configurée pour utiliser Twilio automatiquement :

- **En mode production :** Envoie des SMS réels via Twilio
- **En mode développement :** Simule l'envoi (affiche le code dans les logs)

### Logique Automatique

Dans `AuthController.php`, ligne 61-81 :

```php
try {
    $twilioSid = env('TWILIO_SID');
    $twilioToken = env('TWILIO_TOKEN');
    $twilioFrom = env('TWILIO_FROM');
    
    if ($twilioSid && $twilioToken && $twilioFrom) {
        $twilio = new Client($twilioSid, $twilioToken);
        $twilio->messages->create($request->telephone, [
            'from' => $twilioFrom,
            'body' => "Votre code de vérification Orange Money: $code"
        ]);
        $smsStatus = 'SMS envoyé via Twilio';
    } else {
        // Mode développement - simulation SMS
        $smsStatus = 'Mode développement - SMS simulé (Code: ' . $code . ')';
        \Log::info("SIMULATION SMS - Numéro: {$request->telephone}, Code: $code");
    }
} catch (\Exception $e) {
    return response()->json(['error' => 'Erreur envoi SMS: ' . $e->getMessage()], 500);
}
```

## 📱 Format du Numéro de Téléphone

Twilio accepte les formats suivants :

- **Format international :** `+771234567` (pour le Sénégal)
- **Format local :** `771234567` (sera converti automatiquement)

L'application convertir automatiquement les numéros sénégalais vers le format international.

## 🧪 Test de Configuration

### 1. Vérifier les Variables d'Environnement

```bash
cd app_om_paye
php artisan tinker --execute="echo 'TWILIO_SID: ' . (env('TWILIO_SID') ? 'Configured' : 'Not configured');"
```

### 2. Tester l'Envoi de SMS

```bash
curl -X POST -H "Content-Type: application/json" \
     -d '{"telephone":"+221771234567"}' \
     http://localhost:8001/api/auth/login
```

Si configuré correctement, vous recevrez un SMS réel avec le code de vérification.

## 💰 Coûts Twilio

Twilio propose un **crédit gratuit de $15.50** pour les nouveaux comptes.

**Tarifs approximatifs pour le Sénégal :**
- SMS local : ~$0.001 à $0.005 par message
- SMS international : ~$0.007 à $0.015 par message

## 🚨 Points Importants

### 1. Vérification des Numéros
- Twilio **exige** la vérification des numéros avant l'envoi
- Ajoutez vos numéros de test dans Twilio Console
- Pour la production, vous devrez **acheter un numéro Twilio**

### 2. Numéro d'Origine
- Utilisez le **numéro Twilio** fourni (ex: `+14155238886`)
- Pour le Sénégal, vous pourriez avoir besoin d'un **numéro local**

### 3. Gestion d'Erreurs
- L'application gère gracieusement les erreurs Twilio
- En cas d'erreur, elle bascule en mode simulation
- Vérifiez les logs Laravel pour les détails d'erreur

## 🔍 Dépannage

### Problèmes Courants

1. **"Invalid phone number format"**
   - Vérifiez le format du numéro (+221771234567)
   - Assurez-vous que le numéro est vérifié dans Twilio

2. **"Unauthorized"**
   - Vérifiez vos TWILIO_SID et TWILIO_TOKEN
   - Assurez-vous que l'API Key a les bonnes permissions

3. **SMS non reçu**
   - Vérifiez que le numéro est dans la liste des "Verified Caller IDs"
   - Testez avec un numéro différent
   - Vérifiez les logs Laravel pour plus de détails

### Logs de Débogage

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Rechercher les erreurs SMS
grep -i "sms" storage/logs/laravel.log
```

## 📞 Support

En cas de problème :
1. Consultez la [documentation Twilio](https://www.twilio.com/docs/sms)
2. Vérifiez les logs de votre application
3. Testez avec le Twilio Console directement

## 🎯 Prochaines Étapes

1. **Configuration complète :** Ajoutez vos vraies credentials Twilio
2. **Test complet :** Vérifiez l'envoi de SMS avec plusieurs numéros
3. **Production :** Achetez un numéro Twilio pour votre région
4. **Monitoring :** Configurez des alertes pour les échecs d'envoi

---

**Note :** Cette configuration fonctionne automatiquement avec votre code existant. Une fois les credentials ajoutées, l'envoi de SMS réel sera activé sans modification de code supplémentaire.