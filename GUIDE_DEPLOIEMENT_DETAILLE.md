# 🚀 Guide de Déploiement OMPAYE - Étapes Complètes

## 📋 **Prérequis**

Avant de commencer, assurez-vous d'avoir :
- ✅ **Compte GitHub** (pour héberger le code)
- ✅ **Compte Docker Hub** (optionnel pour images)
- ✅ **Compte Render.com** (gratuit)

## 🗂️ **Étape 1: Préparer le Code**

### 1.1 Copier le fichier de production
```bash
# Copier la configuration de production vers .env
cp .env.production .env
```

### 1.2 Créer un Dockerfile de production optimisé
```dockerfile
# Dockerfile.prod
FROM php:8.3-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql

WORKDIR /var/www

# Créer les répertoires nécessaires
RUN mkdir -p bootstrap/cache storage/logs storage/framework/{cache,sessions,views} public/storage

# Copier les fichiers de l'application
COPY . .

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances
RUN composer install --optimize-autoloader --no-dev

# Permissions et stockage
RUN rm -f public/storage && mkdir -p public/storage
RUN mkdir -p storage/app/public && chown -R www-data:www-data storage
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

# Commande de démarrage
EXPOSE 80
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
```

## 🗄️ **Étape 2: Base de Données PostgreSQL**

### 2.1 Créer une base de données PostgreSQL sur Render

1. **Aller sur Render.com** et se connecter
2. **Cliquer "New +"** puis **"Database"**
3. **Choisir "PostgreSQL"**
4. **Configuration** :
   - **Name** : `ompaye-db`
   - **Region** : `Oregon (US West)`
   - **Plan** : `Free Tier` (pour les tests)
5. **Créer la base** et noter les informations :
   - **Host** : `dpg-xxxxxxxxx-a.oregon-postgres.render.com`
   - **Port** : `5432`
   - **Database** : `ompaye_xxxxxxxx`
   - **Username** : `ompaye_xxxxxxxx`
   - **Password** : `xxxxxxxxxxxx`

### 2.2 Copier la DATABASE_URL
Render vous donne une URL complète comme :
```
postgresql://ompaye_xxxxxxxx_user:xxxxxxxxxxxx@dpg-xxxxxxxxx-a.oregon-postgres.render.com:5432/ompaye_xxxxxxxx
```

## 🎯 **Étape 3: Créer l'Application sur Render**

### 3.1 Nouveau Web Service
1. **Dashboard Render** → **"New +"** → **"Web Service"**
2. **Connecter votre repo GitHub** (ou sélectionner "Build and deploy from a Git repository")
3. **Nom du service** : `ompaye-api`
4. **Runtime** : **Docker**
5. **Dockerfile Path** : `Dockerfile.prod` (ou `Dockerfile` si à la racine)
6. **Root Directory** : `/` (racine)

### 3.2 Variables d'Environnement Render

Copier-coller ces variables dans **"Environment"** sur Render :

```env
# Application
APP_NAME=OM Paye
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ompaye-api.onrender.com

# Base de données (utiliser votre DATABASE_URL de Render)
DATABASE_URL=postgresql://ompaye_xxxxxxxx_user:xxxxxxxxxxxx@dpg-xxxxxxxxx-a.oregon-postgres.render.com:5432/ompaye_xxxxxxxx

# Cache et sessions
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Passport OAuth
PASSPORT_CLIENT_ID=1
PASSPORT_CLIENT_SECRET=n8z22zCwFndtKxhHxq3YYSvFZ7mnEKJLfm64VBEy

# SMS Configuration (Mode simulation pour les tests)
SMS_PROVIDER=twilio
SMS_SIMULATION=true
SMS_SIMULATION_NUMBERS=781299999,781111111,782345678
TWILIO_SIMULATION=true
MESSAGEBIRD_SIMULATION=true
AFRICASTALKING_SIMULATION=true

# API Documentation
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://ompaye-api.onrender.com
```

### 3.3 Configuration Avancée
- **Port** : `80`
- **Auto-Deploy** : `Enabled`
- **Region** : `Oregon (US West)` (proche de votre DB)

### 3.4 Déployer
1. **Cliquer "Create Web Service"**
2. **Attendre 5-10 minutes** (construction Docker + déploiement)
3. **Notifier "Live"** en vert

## 🧪 **Étape 4: Tester le Déploiement**

### 4.1 Test de Base
```bash
# Remplacer par votre URL Render
curl https://ompaye-api.onrender.com/health
```

**Réponse attendue** :
```
healthy
```

### 4.2 Test de l'API
```bash
# Page d'accueil
curl https://ompaye-api.onrender.com/

# Documentation API
curl https://ompaye-api.onrender.com/api/documentation
```

### 4.3 Test d'Authentification SMS
```bash
# Demande de code SMS
curl -X POST https://ompaye-api.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781299999"}'
```

**Réponse attendue** :
```json
{
    "message": "Code SMS envoyé (Mode Simulation)",
    "session_id": "uuid-here",
    "simulation": true,
    "sms_code": 123456,
    "note": "Mode simulation activé - SMS envoyé par simulation"
}
```

### 4.4 Test de Vérification SMS
```bash
# Utiliser le code affiché dans la réponse précédente
curl -X POST https://ompaye-api.onrender.com/api/auth/verify-sms \
  -H "Content-Type: application/json" \
  -d '{"code": "123456", "password": "motdepasse123"}'
```

**Réponse attendue** :
```json
{
    "access_token": "eyJ...",
    "token_type": "Bearer",
    "user": {...},
    "first_login": true
}
```

### 4.5 Test avec Token JWT
```bash
# Remplacer YOUR_JWT_TOKEN par le token de l'étape précédente
curl -X GET https://ompaye-api.onrender.com/api/compte \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 🎯 **Étape 5: Utiliser l'API**

### 5.1 Configuration Postman ou Application

Une fois connecté, vous pouvez :
- **Voir le solde** : `GET /api/compte`
- **Historique** : `GET /api/historique`
- **Dépôt** : `POST /api/transactions/depot`
- **Retrait** : `POST /api/transactions/retrait`
- **Transfert** : `POST /api/transactions/transfert`
- **Paiement marchand** : `POST /api/transactions/paiement`

### 5.2 Numéros de Test Disponibles
En mode simulation, ces numéros sont prêts :
- **781299999** (principal)
- **781111111** (secondaire)
- **782345678** (marchand)

### 5.3 Test avec un Marchand
```bash
# Créer un marchand (nécessite être admin ou client existant)
# 1. S'authentifier avec un numéro existant
# 2. Le convertir en marchand via l'API admin (si vous avez les droits)

# Générer un QR code marchand
curl -X POST https://ompaye-api.onrender.com/api/marchand/generate-qr \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"montant": 10000}'
```

## 🔧 **Dépannage Courant**

### Problème 1: Build échoue
**Solution** :
- Vérifier que `Dockerfile.prod` existe
- Vérifier les permissions des fichiers
- Consulter les logs de build

### Problème 2: Base de données non accessible
**Solution** :
- Vérifier `DATABASE_URL` dans les variables d'environnement
- Attendre que la DB Render soit complètement initialisée (2-3 minutes)
- Redémarrer le service

### Problème 3: Port d'erreur
**Solution** :
- S'assurer que le service utilise le port `80`
- Vérifier la commande CMD dans le Dockerfile

### Problème 4: SMS ne fonctionne pas
**Solution** :
- Le mode simulation affiche le code dans la réponse
- Vérifier `SMS_SIMULATION=true` dans les variables

## 🎉 **Succès !**

Si tous les tests passent, votre API OMPAYE est maintenant :
- ✅ **En ligne sur Internet**
- ✅ **Base de données PostgreSQL configurée**
- ✅ **Authentification SMS fonctionnelle**
- ✅ **Toutes les transactions disponibles**
- ✅ **Documentation API accessible**

**Votre URL** : `https://ompaye-api.onrender.com`
**Documentation** : `https://ompaye-api.onrender.com/api/documentation`

---

**🚀 Votre application OMPAYE est maintenant déployée et prête à être testée !**