# 📋 Checklist de Déploiement OM Paye sur Render

## ✅ Phase 1: Préparation (30 minutes)

### GitHub Repository
- [ ] ✅ **Code OM Paye pushé sur GitHub**
- [ ] ✅ **Repository public ou privé accessible**
- [ ] ✅ **Branche main active**

### Docker Hub
- [ ] **Créer un compte Docker Hub**
- [ ] **Créer un repository**: `ompaye/api`
- [ ] **Notez votre username Docker Hub**

### Base de données Render
- [ ] ✅ **Base PostgreSQL Render configurée**
- [ ] ✅ **Host**: dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com
- [ ] ✅ **Database**: ompaye_g679
- [ ] ✅ **User**: ompaye_g679_user
- [ ] ✅ **Password**: m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf

## ✅ Phase 2: Construction Docker (15 minutes)

### Construction locale
```bash
# 1. Construire l'image de production
cd app_om_paye
docker build -t ompaye/api:v1.0.0 -f Dockerfile.prod .

# 2. Taguer pour Docker Hub
docker tag ompaye/api:v1.0.0 YOUR_USERNAME/ompaye:v1.0.0
docker tag ompaye/api:latest YOUR_USERNAME/ompaye:latest

# 3. Pousser vers Docker Hub
docker push YOUR_USERNAME/ompaye:latest
docker push YOUR_USERNAME/ompaye:v1.0.0
```

## ✅ Phase 3: Déploiement Render (20 minutes)

### Création du Web Service
- [ ] **Se connecter à Render Dashboard**
- [ ] **New + > Web Service**
- [ ] **Connecter GitHub repository**
- [ ] **Sélectionner repository OM Paye**

### Configuration Render
- [ ] **Nom**: ompaye-api
- [ ] **Runtime**: Docker
- [ ] **Dockerfile Path**: Dockerfile.prod
- [ ] **Root Directory**: /
- [ ] **Plan**: Starter (gratuit) ou Paid

### Variables d'environnement Render
```env
APP_NAME=OM Paye
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Base de données
DATABASE_URL=postgresql://ompaye_g679_user:m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf@dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com/ompaye_g679

# Configuration simplifiée
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# SMS Simulation
TWILIO_SIMULATION=true
MESSAGEBIRD_SIMULATION=true
AFRICAS_TALKING_SIMULATION=true

# Documentation
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://your-app.onrender.com
```

## ✅ Phase 4: Tests post-déploiement (10 minutes)

### Tests de base
- [ ] **Health Check**: https://your-app.onrender.com/health
- [ ] **API Documentation**: https://your-app.onrender.com/api/documentation
- [ ] **Swagger UI**: https://your-app.onrender.com/api/documentation

### Test d'authentification
```bash
# Test de connexion SMS
curl -X POST https://your-app.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781299999"}'

# Test de vérification SMS
curl -X POST https://your-app.onrender.com/api/auth/verify-sms \
  -H "Content-Type: application/json" \
  -d '{"code": "534806"}'
```

### Test avec JWT Token
- [ ] **Obtenir un token JWT**
- [ ] **Tester endpoint protected**: `/api/compte`
- [ ] **Vérifier solde utilisateur**

## ✅ Phase 5: Configuration avancée (15 minutes)

### APP_KEY Generation
```bash
# Si erreur APP_KEY, ajouter cette variable:
APP_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));")
```

### Passport Client (si nécessaire)
```bash
# Dans le terminal Render:
bash -c "cd /var/www && php artisan passport:client --personal"
```

### Clientèle production
- [ ] **Configurer domaines personnalisés**
- [ ] **Ajouter SSL/HTTPS**
- [ ] **Configurer variables SMS réelles**
- [ ] **Activer monitoring et logs**

## ✅ Phase 6: CI/CD Automatique (5 minutes)

### Auto-deployment
- [ ] **Activer Auto-Deploy dans Render**
- [ ] **Tester mise à jour** (push sur GitHub)
- [ ] **Vérifier redéploiement automatique**

## 🎯 URLs de test importantes

### Endpoints de test
- **Health**: `https://your-app.onrender.com/health`
- **API Docs**: `https://your-app.onrender.com/api/documentation`
- **Auth Login**: `POST /api/auth/login`
- **Auth Verify**: `POST /api/auth/verify-sms`
- **Account**: `GET /api/compte` (avec Bearer token)

### Données de test
- **Téléphone Orange**: 781299999
- **Code SMS**: 534806 (mode simulation)
- **Numéro client**: Omni exposés dans la documentation

## ⚠️ Troubleshooting rapide

### Problème de build
- Vérifier `Dockerfile.prod` à la racine
- Vérifier variables d'environnement
- Consulter logs de construction Render

### Problème de base de données
- Vérifier `DATABASE_URL` correcte
- Tester connexion PostgreSQL
- Vérifier permissions de la base

### Problème de migrations
- Migrations se lancent automatiquement
- Si erreur, relancer manuellement via terminal Render
- Vérifier structure de base de données

## 📊 Monitoring post-déploiement

### Métriques à surveiller
- **CPU/Memory usage** dans Render Dashboard
- **Response times** des endpoints
- **Database connections**
- **Error rates** dans les logs

### Alerts à configurer
- **Application downtime**
- **High memory/CPU usage**
- **Database connection failures**
- **High error rates**

## 🎉 Résultats attendus

À la fin de cette checklist, vous aurez:
- ✅ **API OM Paye fonctionnelle sur Render**
- ✅ **Documentation Swagger accessible**
- ✅ **Authentification SMS opérationnelle**
- ✅ **Base de données PostgreSQL connectée**
- ✅ **Auto-deployment depuis GitHub configuré**
- ✅ **SSL/HTTPS automatique activé**

**URL finale**: `https://your-app.onrender.com`