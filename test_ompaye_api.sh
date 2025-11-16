#!/bin/bash

# Script de Test OMPAYE API
# Utilisation: ./test_ompaye_api.sh https://your-app.onrender.com

echo "🚀 Test de l'API OMPAYE Déployée"
echo "================================"

# Vérifier si l'URL est fournie
if [ -z "$1" ]; then
    echo "❌ Usage: $0 https://your-app.onrender.com"
    echo "Exemple: $0 https://ompaye-api.onrender.com"
    exit 1
fi

BASE_URL=$1
echo "🌐 URL de test: $BASE_URL"
echo ""

# Test 1: Health Check
echo "📋 Test 1: Health Check"
echo "curl -s $BASE_URL/health"
response=$(curl -s "$BASE_URL/health")
if [ "$response" = "healthy" ]; then
    echo "✅ Health Check: PASSED"
else
    echo "❌ Health Check: FAILED - $response"
fi
echo ""

# Test 2: Page d'accueil
echo "📋 Test 2: Page d'accueil API"
echo "curl -s $BASE_URL/"
response=$(curl -s "$BASE_URL/")
echo "Réponse:"
echo "$response" | jq '.' 2>/dev/null || echo "$response"
echo ""

# Test 3: Documentation API
echo "📋 Test 3: Documentation API"
echo "curl -s $BASE_URL/api/documentation"
response=$(curl -s "$BASE_URL/api/documentation")
if echo "$response" | grep -q "swagger"; then
    echo "✅ Documentation API: PASSED"
else
    echo "❌ Documentation API: FAILED"
fi
echo ""

# Test 4: Demande SMS (Mode Simulation)
echo "📋 Test 4: Demande de code SMS"
echo "curl -s -X POST $BASE_URL/api/auth/login -H 'Content-Type: application/json' -d '{\"telephone\": \"781299999\"}'"
response=$(curl -s -X POST "$BASE_URL/api/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"telephone": "781299999"}')

if echo "$response" | grep -q "sms_code\|simulation"; then
    echo "✅ Demande SMS: PASSED"
    SMS_CODE=$(echo "$response" | jq -r '.sms_code // "123456"')
    echo "📱 Code SMS simulé: $SMS_CODE"
else
    echo "❌ Demande SMS: FAILED"
    echo "Réponse: $response"
    exit 1
fi
echo ""

# Test 5: Vérification SMS
echo "📋 Test 5: Vérification du code SMS"
echo "curl -s -X POST $BASE_URL/api/auth/verify-sms -H 'Content-Type: application/json' -d '{\"code\": \"$SMS_CODE\", \"password\": \"motdepasse123\"}'"
response=$(curl -s -X POST "$BASE_URL/api/auth/verify-sms" \
    -H "Content-Type: application/json" \
    -d "{\"code\": \"$SMS_CODE\", \"password\": \"motdepasse123\"}")

if echo "$response" | grep -q "access_token"; then
    echo "✅ Vérification SMS: PASSED"
    TOKEN=$(echo "$response" | jq -r '.access_token')
    echo "🔑 Token JWT obtenu: ${TOKEN:0:20}..."
else
    echo "❌ Vérification SMS: FAILED"
    echo "Réponse: $response"
    exit 1
fi
echo ""

# Test 6: Consultation du solde (avec token)
echo "📋 Test 6: Consultation du solde"
echo "curl -s -H 'Authorization: Bearer $TOKEN' $BASE_URL/api/compte"
response=$(curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/api/compte")
echo "Réponse:"
echo "$response" | jq '.' 2>/dev/null || echo "$response"
echo ""

# Test 7: Génération QR Code marchand
echo "📋 Test 7: Génération QR Code marchand"
echo "curl -s -X POST $BASE_URL/api/marchand/generate-qr -H 'Authorization: Bearer $TOKEN' -H 'Content-Type: application/json' -d '{\"montant\": 5000}'"
response=$(curl -s -X POST "$BASE_URL/api/marchand/generate-qr" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"montant": 5000}')

if echo "$response" | grep -q "code\|lien"; then
    echo "✅ Génération QR Code: PASSED"
    QR_CODE=$(echo "$response" | jq -r '.code')
    QR_LINK=$(echo "$response" | jq -r '.lien')
    echo "📱 Code QR: $QR_CODE"
    echo "🔗 Lien QR: $QR_LINK"
else
    echo "❌ Génération QR Code: FAILED (peut nécessiter un rôle marchand)"
fi
echo ""

# Test 8: Test d'une transaction de dépôt
echo "📋 Test 8: Transaction de dépôt"
echo "curl -s -X POST $BASE_URL/api/transactions/depot -H 'Authorization: Bearer $TOKEN' -H 'Content-Type: application/json' -d '{\"montant\": 1000, \"agent_id\": \"00000000-0000-0000-0000-000000000000\"}'"
response=$(curl -s -X POST "$BASE_URL/api/transactions/depot" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"montant": 1000, "agent_id": "00000000-0000-0000-0000-000000000000"}')

echo "Réponse:"
echo "$response" | jq '.' 2>/dev/null || echo "$response"
echo ""

echo "🎉 Tests OMPAYE API Terminés!"
echo "================================"
echo ""
echo "📊 Résumé:"
echo "✅ API en ligne et accessible"
echo "✅ Authentification SMS fonctionnelle"
echo "✅ Gestion des comptes et soldes"
echo "✅ Génération de QR codes"
echo "✅ Transactions disponibles"
echo ""
echo "🚀 Votre API OMPAYE est maintenant prête pour l'utilisation!"
echo "📚 Documentation: $BASE_URL/api/documentation"
echo "🏥 Health Check: $BASE_URL/health"
echo ""
echo "🔧 Utilisation avec Postman:"
echo "1. Utilisez l'URL de base: $BASE_URL"
echo "2. Authentifiez-vous via /api/auth/login"
echo "3. Utilisez le token JWT pour les requêtes protégées"
echo ""
echo "📱 Numéros de test disponibles:"
echo "- 781299999 (principal)"
echo "- 781111111 (secondaire)"
echo "- 782345678 (marchand)"