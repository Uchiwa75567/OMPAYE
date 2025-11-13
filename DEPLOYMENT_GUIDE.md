# 🚀 Guide de Déploiement OM Paye sur Render + Docker Hub

## 📋 Pré-requis

- ✅ Compte Docker Hub créé
- ✅ Compte Render créé 
- ✅ Repository GitHub avec le code OM Paye
- ✅ Base de données PostgreSQL Render configurée

## 🐳 Étape 1: Construire et publier l'image Docker

### 1.1 Connexion à Docker Hub

```bash
# Connexion à Docker Hub
docker login

# Entrez votre username et password
```

### 1.2 Construction de l'image de production

```bash
# Construire l'image optimisée
docker build -t ompaye/api:v1.0.0 -f Dockerfile.prod .

# Construire aussi la version latest
docker build -t ompaye/api:latest -f Dockerfile.prod .
```

### 1.3 Publication sur Docker Hub

```bash
# Taguer l'image avec votre nom Docker Hub
docker tag ompaye/api:latest yourusername/ompaye:latest
docker tag ompaye/api:v1.0.0 yourusername/ompaye:v1.0.0

# Pousser vers Docker Hub
docker push yourusername/ompaye:latest
docker push yourusername/ompaye:v1.0.0
```

## 🎯 Étape 2: Déploiement sur Render

### 2.1 Créer un Web Service

1. Connectez-vous à [Render Dashboard](https://dashboard.render.com)
2. Cliquez sur **"New +"** puis **"Web Service"**
3. Connectez votre repository GitHub
4. Sélectionnez votre repository OM Paye

### 2.2 Configuration du Web Service

**Nom du service:** `ompaye-api` ou `ompaye-production`

**Runtime:** `Docker`

**Dockerfile Path:** `Dockerfile.prod`

**Root Directory:** `/` (la racine du projet)

### 2.3 Variables d'environnement

Configurez ces variables dans Render:

```env
# Application
APP_NAME=OM Paye
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Database (votre base Render)
DATABASE_URL=postgresql://ompaye_g679_user:m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf@dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com/ompaye_g679

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Passport OAuth
PASSPORT_CLIENT_ID=1
PASSPORT_CLIENT_SECRET=n8z22zCwFndtKxhHxq3YYSvFZ7mnEKJLfm64VBEy

# SMS Configuration (Mode simulation pour les tests)
TWILIO_SIMULATION=true
MESSAGEBIRD_SIMULATION=true
AFRICAS_TALKING_SIMULATION=true

# API Documentation
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://your-app.onrender.com
```

### 2.4 Configuration avancée

**Auto-Deploy:** `Enabled`

**Plan:** Starter (gratuit) ou Paid pour production

**Region:** Oregon (closest to your database)

### 2.5 Déploiement

1. Cliquez sur **"Create Web Service"**
2. Render construira votre image Docker automatiquement
3. Attendez que le déploiement soit complet (5-10 minutes)
4. Votre API sera accessible sur: `https://your-app.onrender.com`

## 🧪 Étape 3: Tests de l'API déployée

### 3.1 Test de base

```bash
# Test de santé
curl https://your-app.onrender.com/health

# Test de la documentation
curl https://your-app.onrender.com/api/documentation
```

### 3.2 Test d'authentification

```bash
# Demande de code SMS
curl -X POST https://your-app.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781299999"}'

# Vérification SMS et token
curl -X POST https://your-app.onrender.com/api/auth/verify-sms \
  -H "Content-Type: application/json" \
  -d '{"code": "534806"}'
```

### 3.3 Test avec token JWT

```bash
# Récupérez le token de l'étape précédente puis:
curl -X GET https://your-app.onrender.com/api/compte \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 🔧 Étape 4: Configuration post-déploiement

### 4.1 Génération de l'APP_KEY (si nécessaire)

Si vous avez une erreur APP_KEY, ajoutez:

```env
APP_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));")
```

### 4.2 Migration de la base de données

Les migrations se lancent automatiquement via le script start.sh

### 4.3 Création du client Passport

Si nécessaire, exécutez dans le terminal Render:

```bash
# Accéder au container
bash -c "cd /var/www && php artisan passport:client --personal"
```

## 🌐 Étape 5: Configuration du domaine personnalisé (optionnel)

1. Dans Render Dashboard, allez dans votre Web Service
2. Section **"Settings"** > **"Domains"**
3. Ajoutez votre domaine personnalisé
4. Configurez les DNS selon les instructions Render

## 🔄 Mises à jour automatiques

### Auto-deploy depuis GitHub

1. Poussez vos changements vers GitHub
2. Render détecte automatiquement les changements
3. Redéploie l'application (compilation Docker inclus)

### Mise à jour de l'image Docker

1. Modifiez votre code et piquez sur GitHub
2. Render re-construit automatiquement l'image Docker
3. Votre nouvelle version est déployée

## 🛠️ Troubleshooting

### Logs de l'application

```bash
# Voir les logs en temps réel
render logs tail ompaye-api
```

### Connexion à la base de données

```bash
# Test de connexion depuis Render
PGPASSWORD=m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf psql -h dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com -U ompaye_g679_user ompaye_g679
```

### Réinitialisation du déploiement

1. Dashboard Render > Votre Web Service
2. Section **"Settings"** > **"Actions"** > **"Restart"**

### Problèmes courants

**Port binding error:**
- Assurez-vous que `EXPOSE 80` est dans votre Dockerfile.prod
- Utilisez `0.0.0.0:80` dans la configuration

**Database connection:**
- Vérifiez que `DATABASE_URL` est correcte
- Testez la connexion depuis l'environnement Render

**Build failures:**
- Vérifiez les logs de construction Docker
- Assurez-vous que `Dockerfile.prod` est bien à la racine

## 📊 Monitoring et Analytics

### Métriques Render

1. Dashboard > Votre Web Service
2. Section **"Metrics"**
3. Monitorez CPU, Memory, et réseau

### Health Check

L'application expose un endpoint `/health` pour le monitoring:

```bash
curl https://your-app.onrender.com/health
```

## 🔒 Sécurité production

### HTTPS

- Render fournit automatiquement HTTPS
- Configuration SSL automatique

### Variables d'environnement

- Ne jamais exposer les secrets dans le code
- Utiliser les variables d'environnement Render

### Firewall

- Port 80 ouvert pour HTTP
- Port 443 ouvert pour HTTPS
- Database sur port privé

## 🎉 Félicitations!

Votre API OM Paye est maintenant déployée sur Render avec Docker! 

**URLs importantes:**
- **API:** `https://your-app.onrender.com`
- **Documentation:** `https://your-app.onrender.com/api/documentation`
- **Health Check:** `https://your-app.onrender.com/health`

## 📞 Support

En cas de problème:
1. Vérifiez les logs Render
2. Consultez la [documentation Render](https://render.com/docs)
3. Vérifiez les [logs Docker](https://docs.docker.com/get-started/overview/)