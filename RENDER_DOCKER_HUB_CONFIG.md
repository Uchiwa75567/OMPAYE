# 🎯 Configuration Render avec Docker Hub

## 🐳 Image Docker Hub

**Image à utiliser sur Render** :
```
bachiruchiwa2001/ompaye:latest
```

## 🔧 Configuration Render Dashboard

### 1. Web Service Configuration

**Runtime** : `Docker` (pas Git)

**Image Path** : `bachiruchiwa2001/ompaye:latest`

**Plan** : Starter (gratuit) ou Paid

**Region** : Oregon (closest to database)

### 2. Variables d'Environnement Render

Copiez-collez ces variables dans Render Dashboard :

```env
# Application
APP_NAME=OM Paye
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Base de données PostgreSQL Render
DATABASE_URL=postgresql://ompaye_g679_user:m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf@dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com/ompaye_g679

# Cache et Session (fichiers pour starter plan)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# SMS Configuration (Mode simulation)
TWILIO_SIMULATION=true
MESSAGEBIRD_SIMULATION=true
AFRICAS_TALKING_SIMULATION=true

# Documentation API
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://your-app.onrender.com

# Performance
PHP_OPCACHE_ENABLE=1
```

## 🚀 Étapes de Configuration

### Étape 1: Créer Web Service
1. Dashboard Render → New + → Web Service
2. **Don't use a buildpack or repository yet**
3. **Upload a Docker image** → `bachiruchiwa2001/ompaye:latest`

### Étape 2: Configuration Service
1. **Name**: `ompaye-api` ou `ompaye-production`
2. **Region**: Oregon (US West)
3. **Plan**: Starter (free) ou Paid
4. **Environment**: Docker

### Étape 3: Variables d'Environnement
1. Section **Environment** → Add environment variables
2. Collez les variables ci-dessus une par une
3. Save all

### Étape 4: Network & Ports
1. **Port**: 80 (Laravel serveur sur port 80)
2. **Health Check**: `/health`

## ✅ Avantages Docker Hub vs Git Build

- ✅ **Plus rapide** : Pas de build à chaque déploiement
- ✅ **Plus stable** : Image pré-construite et testée
- ✅ **Moins de ressources** : Pas de compilation à chaque fois
- ✅ **Versioning** : tags `latest`, `v1.0.0`, etc.

## 🧪 Tests Post-Déploiement

Une fois déployé, testez ces endpoints :

```bash
# Health check
curl https://your-app.onrender.com/health

# API Documentation
curl https://your-app.onrender.com/api/documentation

# Test authentification SMS
curl -X POST https://your-app.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781299999"}'
```

## 🔄 Mises à jour

Pour mettre à jour :
1. **Recréez l'image** avec nouveaux changements
2. **Poussez vers Docker Hub** : `docker push bachiruchiwa2001/ompaye:latest`
3. **Redéploiement Render** : Refresh l'image ou restart service

## 🎯 Résultat Final

Avec cette configuration :
- ✅ **Build instantané** : Plus de build Docker sur Render
- ✅ **API OM Paye** : Fonctionnelle sur Render
- ✅ **Base PostgreSQL** : Connectée et opérationnelle
- ✅ **Documentation Swagger** : Accessible
- ✅ **Auth SMS** : Mode simulation actif

**Votre API OM Paye sera déployée et opérationnelle !** 🎉