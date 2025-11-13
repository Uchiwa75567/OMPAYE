# 🐳 Commandes Docker Hub pour OM Paye

## 📋 Commandes Prêtes à Exécuter

### 1. Construction et Publication

```bash
# 1. Se connecter à Docker Hub
docker login

# 2. Construire l'image de production
docker build -t ompaye/api:v1.0.0 -f Dockerfile.prod .

# 3. Construire aussi la version latest
docker build -t ompaye/api:latest -f Dockerfile.prod .

# 4. Taguer pour votre compte Docker Hub
# Remplacez YOUR_USERNAME par votre nom d'utilisateur Docker Hub
docker tag ompaye/api:latest YOUR_USERNAME/ompaye:latest
docker tag ompaye/api:v1.0.0 YOUR_USERNAME/ompaye:v1.0.0

# 5. Pousser vers Docker Hub
docker push YOUR_USERNAME/ompaye:latest
docker push YOUR_USERNAME/ompaye:v1.0.0

# 6. Vérifier l'image sur Docker Hub
docker images | grep ompaye
```

### 2. Test Local de l'Image

```bash
# Tester l'image localement avant de pousser
docker run -d --name ompaye-test -p 8080:80 ompaye/api:latest

# Vérifier que le conteneur fonctionne
docker ps

# Tester l'API locale
curl http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone":"781299999"}'

# Nettoyer
docker stop ompaye-test
docker rm ompaye-test
```

## 🎯 Pour Render

### Configuration Render

1. **Web Service Name**: `ompaye-api`
2. **Runtime**: `Docker`
3. **Dockerfile Path**: `Dockerfile.prod`
4. **Build Command**: (laissé vide - Render détecte automatiquement)
5. **Start Command**: (laissé vide - défini dans Dockerfile)

### Variables d'Environnement Render

```env
# Variables à configurer dans Render Dashboard
APP_NAME=OM Paye
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Base de données
DATABASE_URL=postgresql://ompaye_g679_user:m3Ie0pKlygYqN9lCEeW5d0UmIDfI0Xbf@dpg-d4b4m2fpm1nc739jvbg0-a.oregon-postgres.render.com/ompaye_g679

# Cache et sessions (fichiers pour starter plan)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# SMS Simulation (mode test)
TWILIO_SIMULATION=true
MESSAGEBIRD_SIMULATION=true
AFRICAS_TALKING_SIMULATION=true

# Documentation API
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://your-app.onrender.com

# Auto-deploy
AUTO_DEPLOY=true
```

## 🧪 Tests Post-Déploiement

### Test d'API

```bash
# Health Check
curl https://your-app.onrender.com/health

# Test d'authentification SMS
curl -X POST https://your-app.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781299999"}'

# Test de vérification SMS
curl -X POST https://your-app.onrender.com/api/auth/verify-sms \
  -H "Content-Type: application/json" \
  -d '{"code": "534806"}'

# Documentation Swagger
curl https://your-app.onrender.com/api/documentation
```

### URLs Importantes

- **API principale**: `https://your-app.onrender.com`
- **Documentation**: `https://your-app.onrender.com/api/documentation`
- **Health Check**: `https://your-app.onrender.com/health`

## 🔄 Mises à Jour

### Auto-Deploy depuis GitHub

```bash
# Mettez à jour votre code
git add .
git commit -m "Update OM Paye API"
git push origin main

# Render détecte automatiquement et redéploie
```

### Mise à jour manuelle de l'image

```bash
# Modifier le code
# Reconstruire l'image
docker build -t ompaye/api:v1.0.1 -f Dockerfile.prod .
docker tag ompaye/api:v1.0.1 YOUR_USERNAME/ompaye:latest
docker push YOUR_USERNAME/ompaye:latest

# Render fait le redéploiement automatique
```

## 🛠️ Troubleshooting

### Logs

```bash
# Voir les logs du déploiement
render logs tail ompaye-api

# Logs Docker local
docker logs ompaye-test
```

### Problèmes courants

1. **Build échoue**:
   - Vérifier `Dockerfile.prod` à la racine
   - Vérifier les variables d'environnement

2. **Base de données**:
   - Vérifier `DATABASE_URL` correcte
   - Tester la connexion PostgreSQL

3. **Migrations**:
   - Se lancent automatiquement via start.sh
   - Vérifier les permissions de la base

## 📊 Monitoring

### Métriques importantes

- **CPU/Memory usage** dans Render Dashboard
- **Response times** des endpoints
- **Database connections**
- **Error rates**

### Health Check

L'application expose un endpoint `/health` pour les moniteurs:

```bash
curl https://your-app.onrender.com/health
```

## 🎉 Résultat Final

Après exécution de ces commandes:

✅ **Image Docker Hub publiée**: `YOUR_USERNAME/ompaye:latest`  
✅ **API déployée sur Render**: `https://your-app.onrender.com`  
✅ **Documentation accessible**: `/api/documentation`  
✅ **Base de données connectée**: PostgreSQL Render  
✅ **Auto-deploy configuré**: GitHub → Render  
✅ **HTTPS/SSL automatique**: Render Managed  

## 📞 Support

En cas de problème:
1. Vérifier les logs Render
2. Consulter `DEPLOYMENT_GUIDE.md`
3. Suivre `DEPLOYMENT_CHECKLIST.md`