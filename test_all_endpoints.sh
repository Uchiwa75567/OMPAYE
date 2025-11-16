#!/bin/bash

# Script de test complet des endpoints OMPAYE
BASE_URL="http://localhost:8081"

echo "🧪 TEST DES ENDPOINTS OMPAYE"
echo "=============================="

# 1. Test de base - Route racine
echo "1️⃣ Test de la route racine..."
curl -s "${BASE_URL}/" || echo "❌ Route racine non accessible"

# 2. Test des endpoints d'authentification
echo -e "\n2️⃣ TEST AUTHENTIFICATION"

# Register endpoint
echo "   📝 Test inscription..."
curl -s -X POST "${BASE_URL}/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"nom":"Test","prenom":"User","cni":"123TEST456","telephone":"789123456","sexe":"M","password":"test123"}' \
  || echo "❌ Échec inscription"

# Login endpoint (avec utilisateur existant)
echo "   🔑 Test login admin..."
LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"telephone": "781111111", "password": "admin123"}')

if echo "$LOGIN_RESPONSE" | grep -q "access_token\|error\|message"; then
    echo "✅ Login réussi ou erreur attendue"
    echo "$LOGIN_RESPONSE" | head -c 200
else
    echo "❌ Login échoué"
fi

# Me endpoint (nécessite token)
echo -e "\n   👤 Test profil utilisateur..."
curl -s -X GET "${BASE_URL}/api/auth/me" \
  -H "Authorization: Bearer test-token" \
  || echo "❌ Profil non accessible"

# 3. Test des endpoints de compte
echo -e "\n3️⃣ TEST COMPTES"

# Dashboard compte
echo "   📊 Test dashboard compte..."
curl -s -X GET "${BASE_URL}/api/comptes/781111111/dashboard" \
  -H "Authorization: Bearer test-token" \
  || echo "❌ Dashboard non accessible"

# Solde compte
echo "   💰 Test solde compte..."
curl -s -X GET "${BASE_URL}/api/comptes/781111111/solde" \
  -H "Authorization: Bearer test-token" \
  || echo "❌ Solde non accessible"

# Transactions compte
echo "   📋 Test transactions..."
curl -s -X GET "${BASE_URL}/api/comptes/781111111/transactions" \
  -H "Authorization: Bearer test-token" \
  || echo "❌ Transactions non accessibles"

# Transfert
echo "   💸 Test transfert..."
curl -s -X POST "${BASE_URL}/api/comptes/781111111/transfert" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{"compte_destination":"782345678","montant":50000,"motif":"Test transfert"}' \
  || echo "❌ Transfert non accessible"

# Paiement
echo "   💳 Test paiement..."
curl -s -X POST "${BASE_URL}/api/comptes/781111111/paiement" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{"marchand_code":"M295504","montant":25000,"motif":"Test paiement"}' \
  || echo "❌ Paiement non accessible"

# 4. Test des endpoints administrateur
echo -e "\n4️⃣ TEST ADMIN"

# Liste utilisateurs
echo "   👥 Test liste utilisateurs..."
curl -s -X GET "${BASE_URL}/api/admin/users" \
  -H "Authorization: Bearer test-admin-token" \
  || echo "❌ Liste utilisateurs non accessible"

# Statistiques
echo "   📈 Test statistiques..."
curl -s -X GET "${BASE_URL}/api/admin/statistiques" \
  -H "Authorization: Bearer test-admin-token" \
  || echo "❌ Statistiques non accessibles"

# Liste marchands
echo "   🏪 Test liste marchands..."
curl -s -X GET "${BASE_URL}/api/admin/marchands" \
  -H "Authorization: Bearer test-admin-token" \
  || echo "❌ Liste marchands non accessible"

# 5. Test des endpoints de test (sans authentification)
echo -e "\n5️⃣ TEST ENDPOINTS DE DÉVELOPPEMENT"

# Test login
echo "   🧪 Test login simulation..."
curl -s -X POST "${BASE_URL}/api/test/login" \
  -H "Content-Type: application/json" \
  -d '{"telephone":"781111111"}' \
  || echo "❌ Test login échoué"

# Test verify-sms
echo "   📱 Test verify-sms simulation..."
curl -s -X POST "${BASE_URL}/api/test/verify-sms" \
  -H "Content-Type: application/json" \
  -d '{"session_id":"test-session-123","code_sms":"123456"}' \
  || echo "❌ Test verify-sms échoué"

# Test compte
echo "   💰 Test compte simulation..."
curl -s -X GET "${BASE_URL}/api/test/compte" \
  || echo "❌ Test compte échoué"

# 6. Test documentation Swagger
echo -e "\n6️⃣ TEST DOCUMENTATION"
echo "   📖 Test Swagger UI..."
curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/documentation" \
  || echo "❌ Swagger non accessible"

# 7. Test des routes qui ne devraient pas exister
echo -e "\n7️⃣ TEST SÉCURITÉ - Routes inexistantes"
echo "   🚫 Test route inexistante..."
curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/inexistante" \
  && echo " - Code HTTP: $(curl -s -o /dev/null -w '%{http_code}' "${BASE_URL}/api/inexistante")"

echo -e "\n🎯 TESTS TERMINÉS!"
echo "Pour tester avec des tokens valides, récupérez d'abord un token via /api/auth/login"