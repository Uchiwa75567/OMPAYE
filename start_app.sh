#!/bin/bash

echo "🚀 Démarrage d'OMPAYE..."

# Vérifier si les serveurs sont déjà en cours d'exécution
if ! curl -s http://localhost:8083 > /dev/null 2>&1; then
    echo "🌐 Démarrage du serveur Laravel sur port 8083..."
    cd app_om_paye && php -S 127.0.0.1:8083 public/index.php > /dev/null 2>&1 &
    sleep 2
fi

if ! curl -s http://localhost:3000 > /dev/null 2>&1; then
    echo "🔄 Démarrage du proxy sur port 3000..."
    cd app_om_paye && python3 proxy_server.py > /dev/null 2>&1 &
    sleep 2
fi

echo ""
echo "✅ OMPAYE est maintenant accessible :"
echo "   📋 API Laravel: http://localhost:8083"
echo "   🌐 Proxy API:  http://localhost:3000"
echo "   📖 Documentation: http://localhost:3000/api/documentation"
echo ""
echo "📡 Endpoints disponibles:"
echo "   - GET  /api/admin/users"
echo "   - GET  /api/admin/transactions"
echo "   - POST /api/admin/create-marchand"
echo "   - POST /api/auth/login"
echo "   - POST /api/transactions/depot"
echo "   - POST /api/transactions/paiement"
echo "   - POST /api/transactions/retrait"
echo "   - POST /api/transactions/transfert"
echo ""
echo "⏹️  Pour arrêter les serveurs, utilisez Ctrl+C dans les terminaux actifs"
echo ""