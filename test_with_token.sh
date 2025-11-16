#!/bin/bash

BASE_URL="http://localhost:8081"
echo "🧪 TEST DES ENDPOINTS AVEC TOKEN"
echo "=================================="
echo ""

# 1. Obtenir un token de test
echo "1️⃣ Obtention d'un token de test..."
TOKEN_RESPONSE=$(curl -s -X POST "${BASE_URL}/api/test/verify-sms" \
  -H "Content-Type: application/json")

TOKEN=$(echo "$TOKEN_RESPONSE" | jq -r '.access_token')
USER_ID=$(echo "$TOKEN_RESPONSE" | jq -r '.user.id')

echo "✓ Token obtenu: ${TOKEN:0:20}..."
echo "✓ User ID: $USER_ID"
echo ""
echo ""

# 2. Test les endpoints protégés avec le token
echo "2️⃣ Test endpoint: GET /api/test/compte (protégé)"
curl -s -X GET "${BASE_URL}/api/test/compte" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" | jq .
echo ""
echo ""

# 3. Test admin endpoints
echo "3️⃣ Test admin endpoints (doit échouer - pas d'admin user)"
curl -s -X GET "${BASE_URL}/api/admin/users" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "Erreur (normal, pas admin)"
echo ""
echo ""

# 4. Test endpoint transactions
echo "4️⃣ Test transactions endpoint"
curl -s -X GET "${BASE_URL}/api/transactions" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "Erreur (peut être normal)"
echo ""
echo ""

# 5. Test avec mauvais token
echo "5️⃣ Test avec token invalide (doit échouer)"
curl -s -X GET "${BASE_URL}/api/test/compte" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid_token_12345" -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "Erreur (normal, token invalide)"
echo ""

echo "✅ TEST AVEC TOKEN TERMINÉ"
