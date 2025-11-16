# 🚀 GUIDE RAPIDE - UTILISER OMPAYE

## ⚡ Démarrage Rapide

### 1️⃣ Démarrer les services
```bash
cd /home/bachir-uchiwa/OMPAYE/app_om_paye
./dev-start.sh
```

### 2️⃣ Accéder à l'application
```
🌐 API : http://localhost:8081
📚 Swagger UI : http://localhost:8081/api/documentation
🐘 PgAdmin : http://localhost:8082
```

---

## 🧪 Tester les Endpoints

### Méthode 1️⃣ : Swagger UI (Recommandé)
1. Ouvrir http://localhost:8081/api/documentation
2. Cliquer sur "🔓 Bearer Token" (vert)
3. Générer un token via `/api/test/verify-sms`
4. Copier le token et le paster dans Bearer Token
5. Tester les endpoints interactivement

### Méthode 2️⃣ : Script Shell
```bash
# Test complet de tous les endpoints
./test_endpoints.sh

# Test avec token
./test_with_token.sh
```

### Méthode 3️⃣ : cURL
```bash
# Endpoint public
curl http://localhost:8081/api/test/compte

# Avec authentification Bearer
TOKEN="your_token_here"
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8081/api/test/compte
```

---

## 🔐 Authentification

### Obtenir un Token (Mode Test)
```bash
curl -X POST http://localhost:8081/api/test/verify-sms \
  -H "Content-Type: application/json"

# Réponse:
{
  "access_token": "test-token-123...",
  "token_type": "Bearer",
  "user": { ... }
}
```

### Utiliser le Token
```bash
curl -H "Authorization: Bearer test-token-123..." \
  http://localhost:8081/api/test/compte
```

---

## 📚 API Endpoints Disponibles

### 🔓 Publics (Sans Token)
- `GET /` - Page racine
- `POST /api/test/login` - Login test
- `POST /api/test/verify-sms` - Vérification SMS (retourne token)
- `GET /api/test/compte` - Solde compte test

### 🔒 Protégés (Nécessitent Bearer Token)
- `GET /api/comptes/{id}/dashboard` - Dashboard compte
- `GET /api/historique` - Historique transactions
- `POST /api/transactions/depot` - Effectuer un dépôt
- `POST /api/transactions/retrait` - Effectuer un retrait
- `GET /api/admin/users` - Lister les utilisateurs (admin)

### 📖 Documentation
- `GET /api/documentation` - Swagger UI
- `GET /api-docs.json` - Spec OpenAPI JSON

---

## 🎯 Cas d'Usage Courants

### Test 1️⃣ : Obtenir un token et accéder à un endpoint protégé
```bash
#!/bin/bash
TOKEN=$(curl -s -X POST http://localhost:8081/api/test/verify-sms | jq -r '.access_token')
curl -H "Authorization: Bearer $TOKEN" http://localhost:8081/api/test/compte
```

### Test 2️⃣ : Tester tous les endpoints en boucle
```bash
for endpoint in login verify-sms compte; do
  echo "Testing: $endpoint"
  curl -s http://localhost:8081/api/test/$endpoint | jq .
done
```

### Test 3️⃣ : Vérifier les headers CORS
```bash
curl -i http://localhost:8081/api-docs.json | grep -i access-control
```

---

## 🛑 Arrêter l'application
```bash
./stop.sh
```

---

## 🐛 Diagnostic

### Vérifier les services
```bash
docker-compose ps
```

### Voir les logs
```bash
docker-compose logs app
docker-compose logs nginx
docker-compose logs postgres
```

### Redémarrer les services
```bash
docker-compose restart
```

---

## 📝 Notes Importantes

1. **Mode Test** : Les endpoints `/api/test/*` ne nécessitent PAS de base de données
2. **Tokens** : Les tokens de test expirent après une session
3. **CORS** : Tous les origins sont autorisés en développement
4. **PostgreSQL** : Optionnel pour les tests de base

---

## ✅ Vérification Rapide

```bash
# Tout fonctionne ?
curl http://localhost:8081/api/documentation | grep -q swagger-ui && echo "✅ OK" || echo "❌ ERREUR"
```

---

## 🆘 Aide

- **Erreur CORS** : Recharger la page (le middleware filtre les URLs)
- **Port occupé** : `./stop.sh && ./dev-start.sh`
- **Base de données** : Configurer `.env` et exécuter les migrations
- **Plus d'aide** : Voir `ARCHITECTURE_API_NOUVELLE.md`

---

**Pour plus de détails**, consulter :
- `TEST_COMPLET_OMPAYE_FINAL.md` - Rapport complet des tests
- `ARCHITECTURE_API_NOUVELLE.md` - Documentation architecture
- `GESTION_TOKENS_OMPAYE.md` - Gestion des tokens
