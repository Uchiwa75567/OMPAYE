# OM Paye API - Nouvelle Architecture

## Vue d'ensemble

Cette refactorisation complète de l'API OM Paye implémente un système d'authentification moderne avec gestion des rôles et des transactions sécurisées.

## Nouveaux Endpoints API

### 🔐 Authentification

#### `/api/auth/register` (Admin uniquement)
- **Méthode**: POST
- **Description**: Créer un nouveau compte utilisateur
- **Corps**: `nom`, `prenom`, `cni`, `telephone`, `sexe`, `type` (marchand/utilisateur), `password`
- **Protection**: Token admin requis
- **Spécial**: Génère automatiquement un code marchand pour les marchands

#### `/api/auth/send-otp`
- **Méthode**: POST
- **Description**: Envoyer un code OTP par SMS
- **Corps**: `telephone`
- **Utilisateur**: Inscription utilisateur normale

#### `/api/auth/verify-otp`
- **Méthode**: POST
- **Description**: Vérifier le code OTP et obtenir un token
- **Corps**: `telephone`, `code`
- **Retour**: `access_token`, `user`, `expires_at`

#### `/api/auth/login`
- **Méthode**: POST
- **Description**: Connexion avec téléphone et mot de passe
- **Corps**: `telephone`, `password`
- **Retour**: `access_token`, `user`, `expires_at`

#### `/api/auth/me`
- **Méthode**: GET
- **Description**: Informations de l'utilisateur connecté
- **Protection**: Token requis

#### `/api/auth/logout`
- **Méthode**: POST
- **Description**: Déconnexion
- **Protection**: Token requis

### 💰 Opérations de Compte

#### `/api/comptes/{num}/dashboard`
- **Méthode**: GET
- **Description**: Tableau de bord du compte avec statistiques
- **Paramètre**: `num` (numéro de téléphone)
- **Protection**: Token requis
- **Retour**: `user`, `compte`, `transactions_recentes`, `statistiques`

#### `/api/comptes/{num}/solde`
- **Méthode**: GET
- **Description**: Obtenir le solde du compte
- **Paramètre**: `num` (numéro de téléphone)
- **Protection**: Token requis
- **Retour**: `solde`, `solde_formate`, `derniere_maj`

#### `/api/comptes/{num}/transactions`
- **Méthode**: GET
- **Description**: Historique des transactions
- **Paramètres**: `num`, `page`, `per_page`, `type` (filtre)
- **Protection**: Token requis
- **Retour**: Liste paginée des transactions

#### `/api/comptes/{num}/transfert`
- **Méthode**: POST
- **Description**: Effectuer un transfert
- **Corps**: `telephone_destinataire`, `montant`, `password`, `motif`
- **Protection**: Token requis
- **Validation**: Mot de passe + solde suffisant

#### `/api/comptes/{num}/paiement`
- **Méthode**: POST
- **Description**: Effectuer un paiement (téléphone ou code marchand)
- **Corps**: `type` (telephone/code_marchand), `identifiant_destinataire`, `montant`, `password`, `motif`
- **Protection**: Token requis
- **Spécial**: Paiement par téléphone OU code marchand

### 👨‍💼 Administration

#### `/api/admin/users`
- **Méthode**: GET
- **Description**: Liste des utilisateurs avec filtres
- **Paramètres**: `type`, `search`, `page`, `per_page`
- **Protection**: Token admin requis

#### `/api/admin/users/{id}`
- **Méthode**: GET
- **Description**: Détails d'un utilisateur spécifique
- **Protection**: Token admin requis

#### `/api/admin/transactions`
- **Méthode**: GET
- **Description**: Toutes les transactions avec filtres
- **Paramètres**: `type`, `statut`, `date_debut`, `date_fin`, `page`, `per_page`
- **Protection**: Token admin requis

#### `/api/admin/statistiques`
- **Méthode**: GET
- **Description**: Statistiques globales du système
- **Protection**: Token admin requis

#### `/api/admin/marchands`
- **Méthode**: GET
- **Description**: Liste des marchands avec leurs codes
- **Paramètres**: `actif`, `page`, `per_page`
- **Protection**: Token admin requis

#### `/api/admin/marchands/{id}/toggle-status`
- **Méthode**: PUT
- **Description**: Activer/désactiver un code marchand
- **Corps**: `actif` (boolean)
- **Protection**: Token admin requis

#### `/api/admin/users/{id}`
- **Méthode**: DELETE
- **Description**: Supprimer un utilisateur
- **Protection**: Token admin requis

## Modèles et Structure de Base de Données

### Nouveaux Champs User
- `nom`, `prenom`, `cni` (nouveaux champs requis)
- `type` (remplace `role`: admin, marchand, utilisateur)
- `telephone` (unique, pour l'identification)

### Nouveau Modèle MarchandCode
- `user_id` (référence vers User)
- `code_marchand` (unique, généré automatiquement)
- `actif` (boolean pour activer/désactiver)

### Relations
- User → Compte (1:1)
- User → MarchandCode (1:0..1, pour les marchands)
- Compte → Transactions (1:N, source et destination)

## Sécurité et Validation

### Authentification
- **JWT Tokens**: Via Laravel Passport
- **Durée**: 30 jours
- **Portabilité**: Cross-platform

### Validation
- **Téléphones**: Format sénégalais (77xxxxxxx, 78xxxxxxx)
- **Montants**: 100 FCFA à 500,000 FCFA
- **Mot de passe**: Minimum 6 caractères
- **OTP**: 6 chiffres, expire après 5 minutes

### Permissions
- **Admin**: Accès à tous les endpoints
- **Marchand**: Accès aux opérations de compte
- **Utilisateur**: Accès aux opérations de compte (son propre compte)

## Codes Marchands

### Génération Automatique
- Format: `M` + 6 chiffres (ex: M123456)
- Uniqueness garantis
- Généré automatiquement lors de la création d'un marchand

### Utilisation
- Paiement via code marchand (alternative au numéro de téléphone)
- Activation/désactivation par l'admin

## Données de Test (Seeder)

### Admin par défaut
- **Téléphone**: 781111111
- **Mot de passe**: admin123
- **CNl**: ADMIN001

### Utilisateurs de test
- **Jean Dupont**: 782345678 (5000 FCFA)
- **Marie Martin**: 783456789 (2500 FCFA)
- **Amadou Dia**: 784567890 (7500 FCFA)

### Marchands de test
- **Youssou Boutique**: 785678901 (10000 FCFA)
- **Fatou Restaurant**: 786789012 (20000 FCFA)

## Flux d'Utilisation

### 1. Inscription Admin
```
POST /api/auth/register
Body: {nom, prenom, cni, telephone, type: "admin", password}
```

### 2. Authentification
```
POST /api/auth/login
Body: {telephone, password}
```

### 3. Création d'utilisateurs
```
POST /api/auth/register
Body: {nom, prenom, cni, telephone, type: "utilisateur", password}
```

### 4. Opérations utilisateur
```
POST /api/auth/send-otp
POST /api/auth/verify-otp
GET  /api/comptes/782345678/solde
POST /api/comptes/782345678/transfert
```

## Améliorations Futures

1. **SMS Integration**: Configuration des providers SMS (Twilio, MessageBird, AfricasTalking)
2. **Notifications**: Push notifications pour transactions
3. **Multi-devises**: Support d'autres devises que FCFA
4. **Rapports**: Génération de rapports PDF
5. **Audit**: Logs détaillés des transactions
6. **API Rate Limiting**: Limitation des appels API

## Notes de Développement

- **Migration**: Exécuter `php artisan migrate` pour la nouvelle structure
- **Seeder**: Exécuter `php artisan db:seed --class=AdminUserSeeder`
- **Documentation**: Accessible via `/api/documentation`
- **Tests**: Endpoints de test disponibles sous `/api/test/*`