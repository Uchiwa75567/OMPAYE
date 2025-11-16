# 💰 OM Paye - Application de Paiement Mobile

[![Laravel](https://img.shields.io/badge/Laravel-10.10-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> **OM Paye** est une application de paiement mobile complète inspirée d'Orange Money, développée pour le marché sénégalais. Elle reproduit toutes les fonctionnalités d'un système de paiement mobile moderne avec une architecture API-first robuste.

## 🎯 Vue d'ensemble

OM Paye permet aux utilisateurs de :
- ✅ **Gérer des comptes** de paiement mobile
- ✅ **Effectuer des transactions** (dépôt, retrait, transfert)
- ✅ **Payer via QR codes** chez les marchands
- ✅ **Recevoir des notifications SMS** multi-providers
- ✅ **Sécuriser leurs opérations** avec PIN à 4 chiffres

## 🏗️ Architecture Technique

### **Stack Technologique**
```
Frontend:     Swagger UI + Interface Web
Backend:      Laravel 10.10 + PHP 8.1+
Database:     MySQL/MariaDB avec migrations
API:          RESTful avec JWT Bearer Authentication
SMS:          Twilio + MessageBird + AfricasTalking (fallback)
Auth:         Laravel Passport + SMS OTP + PIN
Docs:         L5-Swagger (OpenAPI 3.0)
Container:    Docker + Docker Compose
```

### **Patterns Architecturaux**
- **API-First Design** : Interface backend optimisée pour applications mobiles
- **Microservices Ready** : Structure modulaire pour l'évolutivité
- **Event-Driven** : Logging structuré et audit trail
- **Security-First** : Authentification multi-niveaux et transactions atomiques

## 🎮 Fonctionnalités Métier

### **🔐 Système d'Authentification Multi-niveaux**

#### **1. Authentification SMS (Première Connexion)**
```http
POST /api/auth/login
Content-Type: application/json

{
  "telephone": "771234567"
}
```
- ✅ **Auto-inscription** : Création automatique pour nouveaux utilisateurs Orange
- ✅ **Validation stricte** : Numéros sénégalais uniquement (77xxxxxxx/78xxxxxxx)
- ✅ **Code SMS** : 6 chiffres, validité 5 minutes
- ✅ **Multi-providers** : Twilio (principal) + MessageBird + AfricasTalking (fallback)

#### **2. Authentification Mot de passe**
```http
POST /api/auth/login-password
Content-Type: application/json

{
  "telephone": "771234567",
  "password": "motdepasse123"
}
```
- ✅ **Alternative** : Pour utilisateurs ayant défini un mot de passe
- ✅ **Sécurité** : Hash Bcrypt, anti-brute force

#### **3. PIN de Sécurité (Transactions)**
```http
POST /api/auth/set-pin
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "pin": "1234"
}
```
- ✅ **PIN 4 chiffres** : Obligatoire pour transactions sensibles
- ✅ **Hash Bcrypt** : Stockage sécurisé en base

### **💰 Gestion Financière Complète**

#### **1. Consultation Solde**
```http
GET /api/compte
Authorization: Bearer {jwt_token}
```
**Réponse :**
```json
{
  "solde": 5000,
  "type": "client"
}
```

#### **2. Historique des Transactions**
```http
GET /api/historique
Authorization: Bearer {jwt_token}
```
- ✅ **Pagination** : 20 transactions par page
- ✅ **Filtrage** : Par type de transaction et dates

### **💳 Opérations Financières**

#### **1. Dépôt d'argent** 💸
```http
POST /api/transactions/depot
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "montant": 5000,
  "agent_id": "uuid-agent-distributeur"
}
```
- ✅ **Via agents distributeurs** : Validation du rôle `distributeur`
- ✅ **Instantané** : Crédit immédiat du solde
- ✅ **Frais** : 0 FCFA pour les dépôts

#### **2. Retrait d'argent** 🏧
```http
POST /api/transactions/retrait
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "montant": 2000,
  "agent_id": "uuid-agent-distributeur",
  "pin": "1234"
}
```
- ✅ **Validation PIN** : Obligatoire pour sécuriser
- ✅ **Solde insuffisant** : Vérification avant transaction
- ✅ **Frais** : 100 FCFA par retrait

#### **3. Transfert P2P** 🔄
```http
POST /api/transactions/transfert
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "telephone_dest": "771234567",
  "montant": 3000,
  "pin": "1234"
}
```
- ✅ **Numéro de téléphone** : Identification du destinataire
- ✅ **Frais** : 50 FCFA par transfert
- ✅ **Solde insuffisant** : Validation avant envoi

#### **4. Paiement QR Code** 📱
```http
POST /api/transactions/paiement
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "code_qr": "OM-ABC123DEF",
  "montant": 10000,
  "pin": "1234"
}
```
- ✅ **QR Marchand** : Paiement chez les commerçants
- ✅ **Montant fixe** : Pré-autorisé par le marchand
- ✅ **Frais** : 0 FCFA pour les paiements

### **🏪 Système Marchand**

#### **1. Génération QR Code** 📋
```http
POST /api/marchand/generate-qr
Authorization: Bearer {jwt_token}
Content-Type: application/json

{
  "montant": 15000
}
```
**Réponse :**
```json
{
  "code": "OM-ABC123DEF",
  "lien": "http://localhost:8083/api/qr/OM-ABC123DEF"
}
```
- ✅ **Expiration** : 30 minutes maximum
- ✅ **Statut unique** : Un QR code = une utilisation
- ✅ **Montant fixe** : Immuable après création

#### **2. Consultation QR Code** 🔍
```http
GET /api/qr/{code}
```
**Réponse :**
```json
{
  "marchand": "Jean Dupont",
  "montant": 15000,
  "code": "OM-ABC123DEF"
}
```
- ✅ **Public** : Accessible sans authentification
- ✅ **Validation** : Vérification expiration et statut

### **👑 Administration**

#### **1. Liste des Utilisateurs** 📊
```http
GET /api/admin/users
Authorization: Bearer {admin_jwt_token}
```
- ✅ **Pagination** : 20 utilisateurs par page
- ✅ **Relations** : Include les comptes utilisateur

#### **2. Toutes les Transactions** 📈
```http
GET /api/admin/transactions
Authorization: Bearer {admin_jwt_token}
```
- ✅ **Surveillance globale** : Toutes les transactions système
- ✅ **Relations complètes** : Utilisateur source/destination/marchand

#### **3. Création Marchand** 🏪
```http
POST /api/admin/create-marchand
Authorization: Bearer {admin_jwt_token}
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Jean",
  "telephone": "771234567",
  "sexe": "M",
  "password": "password123"
}
```
- ✅ **Compte automatique** : Création du compte marchand
- ✅ **Rôle marchand** : Permissions appropriées

## 🗄️ Modélisation de Données

### **Schema Base de Données**

```sql
-- Utilisateurs
users {
  id: UUID (PK)
  nom: VARCHAR
  prenom: VARCHAR  
  telephone: VARCHAR (UNIQUE) -- 9 chiffres sénégalais
  sexe: ENUM('M', 'F')
  password: VARCHAR (hashed)
  pin: VARCHAR (4 chiffres, hashed)
  role: ENUM('client', 'admin', 'marchand', 'distributeur')
  created_at: TIMESTAMP
  updated_at: TIMESTAMP
}

-- Comptes financiers
comptes {
  id: UUID (PK)
  user_id: UUID (FK -> users)
  solde: BIGINT -- Stockage en centimes
  type: ENUM('client', 'marchand')
  created_at: TIMESTAMP
  updated_at: TIMESTAMP
}

-- Transactions
transactions {
  id: UUID (PK)
  montant: BIGINT -- En centimes
  type: ENUM('depot', 'retrait', 'transfert', 'paiement')
  statut: ENUM('envoye', 'echec', 'annule', 'en_cours')
  compte_source_id: UUID (FK -> comptes)
  compte_dest_id: UUID (FK -> comptes, nullable)
  marchand_id: UUID (FK -> users, nullable)
  reference: VARCHAR (UNIQUE)
  frais: BIGINT -- En centimes
  created_at: TIMESTAMP
  updated_at: TIMESTAMP
}

-- QR Codes marchands
qr_codes {
  id: UUID (PK)
  marchand_id: UUID (FK -> users)
  montant: BIGINT -- En centimes
  code: VARCHAR (UNIQUE)
  statut: ENUM('active', 'used')
  expires_at: TIMESTAMP
  created_at: TIMESTAMP
  updated_at: TIMESTAMP
}

-- Vérifications SMS
sms_verifications {
  id: BIGINT (PK, AUTO_INCREMENT)
  telephone: VARCHAR
  code: VARCHAR (6 chiffres)
  expires_at: TIMESTAMP
  used: BOOLEAN (default false)
  created_at: TIMESTAMP
}
```

### **Relations Eloquent**

```php
// User
User::hasOne(Compte::class)
User::hasMany(Transaction::class, 'marchand_id') // Comme marchand
User::hasMany(QrCode::class, 'marchand_id')

// Compte  
Compte::belongsTo(User::class)
Compte::hasMany(Transaction::class, 'compte_source_id')
Compte::hasMany(Transaction::class, 'compte_dest_id')

// Transaction
Transaction::belongsTo(Compte::class, 'compte_source_id')
Transaction::belongsTo(Compte::class, 'compte_dest_id')
Transaction::belongsTo(User::class, 'marchand_id')

// QR Code
QrCode::belongsTo(User::class, 'marchand_id')
```

## 🔒 Sécurité

### **Mécanismes de Sécurité**
- ✅ **JWT Bearer Tokens** : Authentification stateless
- ✅ **Bcrypt Hashing** : Mots de passe et PIN
- ✅ **UUID Primary Keys** : Évite les ID prévisibles
- ✅ **Input Validation** : Validation stricte côté serveur
- ✅ **SQL Injection** : Protection via Eloquent ORM
- ✅ **XSS Protection** : Laravel CSRF tokens
- ✅ **Transactions Atomiques** : DB::transaction pour cohérence

### **Validation Métier**
- ✅ **Numéros sénégalais** : Regex `/^(78|77)\d{7}$/`
- ✅ **Montants positifs** : `montant > 0`
- ✅ **PIN 4 chiffres** : `^\d{4}$`
- ✅ **Agents valides** : Rôle `distributeur` obligatoire
- ✅ **Solde suffisant** : Vérification avant débit

## 📚 Documentation API

### **Interface Swagger UI**
🌐 **URL** : http://localhost:8083/api/documentation

### **Documentation JSON**
📄 **URL** : http://localhost:8083/api-docs.json

### **Endpoints Principaux**

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/auth/login` | Demande code SMS | ❌ |
| POST | `/api/auth/verify-sms` | Vérification + JWT token | ❌ |
| POST | `/api/auth/login-password` | Connexion mot de passe | ❌ |
| POST | `/api/auth/set-pin` | Définir PIN sécurité | ✅ |
| GET | `/api/compte` | Consultation solde | ✅ |
| GET | `/api/historique` | Historique transactions | ✅ |
| POST | `/api/transactions/depot` | Dépôt argent | ✅ |
| POST | `/api/transactions/retrait` | Retrait argent | ✅ |
| POST | `/api/transactions/transfert` | Transfert P2P | ✅ |
| POST | `/api/transactions/paiement` | Paiement QR | ✅ |
| POST | `/api/marchand/generate-qr` | Générer QR marchand | ✅ |
| GET | `/api/qr/{code}` | Consultation QR public | ❌ |

## 🚀 Installation et Déploiement

### **Prérequis**
- PHP 8.1+
- Composer
- MySQL/MariaDB
- Node.js (pour assets)
- Serveur web (Apache/Nginx)

### **Installation Locale**
```bash
# Cloner le projet
git clone <repository-url>
cd app_om_paye

# Installer les dépendances
composer install
npm install

# Configuration environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données
# Editer .env avec vos paramètres DB

# Exécuter les migrations
php artisan migrate

# Vider les caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Démarrer le serveur
php artisan serve
```

### **Docker (Recommandé)**
```bash
# Construction et démarrage
docker-compose up -d

# Ou version simplifiée
docker-compose -f docker-compose.simple.yml up -d
```

### **Configuration Base de Données**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ompaye_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

### **Configuration SMS**
```env
# Provider principal (twilio ou messagebird)
SMS_PROVIDER=twilio

# Twilio
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+221xxxxxxxxx
TWILIO_VERIFIED_NUMBER=+221xxxxxxxxx

# MessageBird (fallback)
MESSAGEBIRD_ACCESS_KEY=your_messagebird_key
MESSAGEBIRD_ORIGINATOR=OMPaye

# AfricasTalking (fallback)
AFRIKASTALKING_USERNAME=your_username
AFRIKASTALKING_API_KEY=your_api_key

# Mode simulation (pour tests)
SMS_SIMULATION=true
SMS_SIMULATION_PHONE=+221785052217
SMS_SIMULATION_NUMBERS=785052217,771234567
```

## 🧪 Tests et Développement

### **Mode Simulation SMS**
Pour les tests, activez le mode simulation :
```env
SMS_SIMULATION=true
SMS_SIMULATION_PHONE=+221785052217
```
Les codes SMS seront affichés dans les réponses API.

### **Utilisateurs de Test**

#### **Agent Distributeur**
```json
{
  "id": "a056e54a-4828-4160-a97c-9ab67a7e9116",
  "nom": "Diallo",
  "prenom": "Ali", 
  "telephone": "789876543",
  "role": "distributeur",
  "pin": "1234"
}
```

#### **Utilisateur Test**
```json
{
  "telephone": "785052217",
  "auto_enregistrement": true,
  "mode_simulation": true
}
```

### **Tests API avec cURL**

#### **1. Authentification**
```bash
# Login SMS
curl -X POST http://localhost:8083/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"telephone": "785052217"}'

# Vérification SMS (utiliser le code de simulation)
curl -X POST http://localhost:8083/api/auth/verify-sms \
  -H "Content-Type: application/json" \
  -d '{"code": "123456"}'
```

#### **2. Opérations Protégées**
```bash
# Consultation solde
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://localhost:8083/api/compte

# Dépôt
curl -X POST http://localhost:8083/api/transactions/depot \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"montant": 5000, "agent_id": "a056e54a-4828-4160-a97c-9ab67a7e9116"}'
```

## 📊 Métriques et Monitoring

### **Logs Structurés**
- ✅ **Transactions** : Chaque opération loggée avec référence
- ✅ **SMS** : Succès/échec de chaque envoi
- ✅ **Erreurs** : Stack traces détaillées
- ✅ **Sécurité** : Tentatives de connexion

### **Health Checks**
```bash
# Vérifier le statut de l'API
curl http://localhost:8083/api/health

# Vérifier la base de données
php artisan tinker
DB::connection()->getPdo();
```

### **Métriques Business**
- **Transactions par jour** : Volume d'activité
- **Taux de succès SMS** : Performance providers
- **Solde moyen** : Santé financière
- **Top marchands** : Performance commerciale

## 🔧 Configuration Avancée

### **Variables d'Environnement**

#### **Application**
```env
APP_NAME="OM Paye"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8083
APP_KEY=base64:generated_key
```

#### **Queue et Cache**
```env
QUEUE_CONNECTION=sync
CACHE_DRIVER=file
SESSION_DRIVER=file
```

#### **Mail (Optionnel)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```

### **Personnalisation**

#### **Messages SMS**
Modifier dans `AuthController.php` :
```php
private function sendSmsWithFallback($formattedNumber, $code)
{
    $message = "Votre code de vérification OM Paye: $code. Valable 5 minutes.";
    // Personnaliser le message
}
```

#### **Frais de Transaction**
Modifier dans `TransactionController.php` :
```php
'frais' => 100, // Retrait: 100 FCFA
'frais' => 50,  // Transfert: 50 FCFA  
'frais' => 0,   // Dépôt/Paiement: gratuit
```

#### **Expiration QR Code**
Modifier dans `MarchandController.php` :
```php
'expires_at' => Carbon::now()->addMinutes(30), // 30 minutes
```

## 🌟 Points Forts du Projet

### **✅ Architecture Solide**
- **Séparation des responsabilités** : Controllers, Models, Services distincts
- **Énumérations PHP 8.1** : Type safety pour les rôles et statuts
- **Relations Eloquent** : Optimisées et bien définies
- **API RESTful** : Structure cohérente avec documentation OpenAPI

### **✅ Sécurité de Production**
- **Hashage complet** : Bcrypt pour mots de passe et PIN
- **Validation stricte** : Numéros et montants validés côté serveur
- **Transactions atomiques** : DB::transaction pour cohérence
- **UUID Primary Keys** : Évite les ID prévisibles

### **✅ Expérience Utilisateur**
- **Auto-inscription** : Pas de friction pour nouveaux utilisateurs
- **Multiple auth** : SMS ou mot de passe selon préférence
- **QR codes** : Paiements rapides sans saisie montant
- **Mode simulation** : Tests sans SMS réel

### **✅ Flexibilité Technique**
- **SMS Fallback** : 3 providers avec basculement automatique
- **Configuration** : Environment-based avec logs détaillés
- **Documentation** : Swagger UI interactive
- **Docker** : Déploiement simplifié

## 🚀 Évolutions Possibles

### **Court Terme**
- [ ] **Rate Limiting** : Protection contre abuse API
- [ ] **Cache Redis** : Performance queries fréquentes  
- [ ] **Tests Unitaires** : Couverture code critique
- [ ] **API Versioning** : Compatibilité future

### **Moyen Terme**
- [ ] **Microservices** : Scalabilité horizontale
- [ ] **Queue System** : Traitement asynchrone
- [ ] **WebSocket** : Notifications temps réel
- [ ] **Mobile App** : Application native

### **Long Terme**
- [ ] **Compliance** : Audit trail réglementaire
- [ ] **Analytics** : Métriques business avancées
- [ ] **International** : Support multi-pays
- [ ] **Partenariats** : Intégration banques

## 📞 Support et Contribution

### **Contact**
- **Email** : support@ompaye.com
- **Documentation** : http://localhost:8083/api/documentation
- **Issues** : GitHub Issues

### **Contribution**
1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les modifications (`git commit -m 'Add AmazingFeature'`)
4. Push la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🙏 Remerciements

- **Laravel** : Framework PHP robuste
- **L5-Swagger** : Documentation API automatique
- **Orange Money** : Inspiration fonctionnelle
- **Communauté PHP** : Outils et packages

---

**OM Paye** - *Système de Paiement Mobile pour l'Afrique* 🇸🇳

*Développé avec ❤️ pour simplifier les paiements mobiles au Sénégal*

---

**Version :** 1.0.0  
**Dernière mise à jour :** 14 novembre 2025  
**Statut :** 🟢 Production Ready