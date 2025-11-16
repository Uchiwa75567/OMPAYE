#!/bin/bash

BASE_URL="http://localhost:8081"
echo "🧪 TEST COMPLET DES ENDPOINTS OMPAYE"
echo "======================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Test route racine
echo -e "${YELLOW}1️⃣ Test route racine${NC}"
curl -s "${BASE_URL}/" | head -20
echo ""
echo ""

# 2. Test API docs JSON
echo -e "${YELLOW}2️⃣ Test API docs JSON${NC}"
DOCS=$(curl -s "${BASE_URL}/api-docs.json")
if echo "$DOCS" | grep -q "openapi"; then
    echo -e "${GREEN}✅ API docs accessible${NC}"
    echo "$DOCS" | jq '.info' 2>/dev/null || echo "$DOCS" | head -20
else
    echo -e "${RED}❌ API docs non accessible${NC}"
fi
echo ""
echo ""

# 3. Test Swagger UI
echo -e "${YELLOW}3️⃣ Test Swagger UI${NC}"
SWAGGER=$(curl -s "${BASE_URL}/api/documentation")
if echo "$SWAGGER" | grep -q "swagger-ui"; then
    echo -e "${GREEN}✅ Swagger UI accessible${NC}"
    echo "✓ Titre: $(echo "$SWAGGER" | grep -o '<title>[^<]*</title>' | head -1)"
    echo "✓ URL relative: $(echo "$SWAGGER" | grep 'url:' | grep -o '"/[^"]*"' | head -1)"
else
    echo -e "${RED}❌ Swagger UI non accessible${NC}"
fi
echo ""
echo ""

# 4. Test test endpoints
echo -e "${YELLOW}4️⃣ Test endpoints de test (sans DB)${NC}"

echo -e "${YELLOW}Test login (GET /api/test/login)${NC}"
curl -s -X POST "${BASE_URL}/api/test/login" \
  -H "Content-Type: application/json" | jq .
echo ""

echo -e "${YELLOW}Test verify-sms (POST /api/test/verify-sms)${NC}"
curl -s -X POST "${BASE_URL}/api/test/verify-sms" \
  -H "Content-Type: application/json" | jq .
echo ""

echo -e "${YELLOW}Test compte (GET /api/test/compte)${NC}"
curl -s -X GET "${BASE_URL}/api/test/compte" \
  -H "Content-Type: application/json" | jq .
echo ""
echo ""

# 5. Test endpoints protégés (vont échouer sans token, c'est normal)
echo -e "${YELLOW}5️⃣ Test endpoints protégés (doivent retourner 401 sans token)${NC}"

echo -e "${YELLOW}GET /api/comptes/test/dashboard${NC}"
curl -s -X GET "${BASE_URL}/api/comptes/test/dashboard" \
  -H "Content-Type: application/json" -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "❌ Erreur"
echo ""

echo -e "${YELLOW}GET /api/historique${NC}"
curl -s -X GET "${BASE_URL}/api/historique" \
  -H "Content-Type: application/json" -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "❌ Erreur"
echo ""
echo ""

# 6. Test CORS headers
echo -e "${YELLOW}6️⃣ Test headers CORS${NC}"
HEADERS=$(curl -s -i "${BASE_URL}/api-docs.json" 2>&1 | head -20)
echo "$HEADERS" | grep -i "access-control"
echo ""
echo ""

# 7. Test route non existante
echo -e "${YELLOW}7️⃣ Test route non existante (doit retourner 404)${NC}"
curl -s -X GET "${BASE_URL}/api/non-existant" \
  -w "\nStatus: %{http_code}\n" | jq . 2>/dev/null || echo "Réponse reçue"
echo ""

echo -e "${GREEN}✅ TEST COMPLET TERMINÉ${NC}"
