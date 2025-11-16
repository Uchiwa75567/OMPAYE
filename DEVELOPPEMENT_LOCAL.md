# Configuration pour le développement local OMPAYE

## 🎯 Configuration Locale

### Étapes pour démarrer en développement :

1. **Copier le fichier d'environnement :**
   ```bash
   cp .env.example .env
   ```

2. **Générer la clé Laravel :**
   ```bash
   php artisan key:generate
   ```

3. **Démarrer en développement :**
   ```bash
   ./dev-start.sh
   ```

4. **Installer les dépendances (si pas déjà fait) :**
   ```bash
   composer install
   npm install
   ```

### 🔧 Scripts disponibles :

- **`./dev-start.sh`** - Démarrage simple en développement
- **`./dev-full.sh`** - Démarrage avec base de données
- **`./stop.sh`** - Arrêter tous les services

### 🌐 URLs d'accès :

- **API :** http://localhost:8081
- **Documentation Swagger :** http://localhost:8081/documentation  
- **Database Admin :** http://localhost:8082 (admin@ompaye.com / admin123)

### 📋 Base de données locale :

- **Host :** localhost
- **Port :** 5434
- **Database :** ompaye
- **Username :** laravel  
- **Password :** secret

### 🚀 Route vers l'API :

Tous les endpoints sont sous `/api/` :
- `GET /api/documentation` - Documentation Swagger
- `GET /api/ping` - Test de connexion
- `POST /api/auth/login` - Connexion utilisateur