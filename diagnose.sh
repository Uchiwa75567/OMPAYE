#!/bin/bash

# Script de diagnostic et redémarrage OMPAYE
echo "🔍 Diagnostic OMPAYE..."

# Vérifier si Docker fonctionne
echo "1. Vérification Docker..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé"
    exit 1
fi

if ! docker info &> /dev/null; then
    echo "❌ Docker daemon ne fonctionne pas. Redémarrez Docker."
    exit 1
fi
echo "✅ Docker fonctionne"

# Vérifier les ports utilisés
echo "2. Vérification des ports..."
if lsof -i :8081 &> /dev/null; then
    echo "⚠️  Port 8081 déjà utilisé. Arrêt des processus précédents..."
    docker-compose -f docker-compose.simple.yml down --remove-orphans
    kill $(lsof -t -i:8081) 2>/dev/null || true
fi

# Nettoyer les conteneurs existants
echo "3. Nettoyage des conteneurs..."
docker-compose -f docker-compose.simple.yml down --remove-orphans -v

# Redémarrer avec logs détaillés
echo "4. Démarrage avec logs..."
docker-compose -f docker-compose.simple.yml up -d

# Attendre le démarrage
echo "5. Attente du démarrage (30 secondes)..."
sleep 15

# Vérifier les logs
echo "6. Logs de l'application..."
docker-compose -f docker-compose.simple.yml logs app --tail=10

echo "7. Logs de PostgreSQL..."
docker-compose -f docker-compose.simple.yml logs postgres --tail=5

# Test de connectivité
echo "8. Test de connectivité..."
if curl -s http://localhost:8081/api/ping > /dev/null; then
    echo "✅ API accessible !"
    echo "🌐 URL: http://localhost:8081"
else
    echo "❌ API non accessible"
    echo "🔍 Vérifiez les logs ci-dessus"
fi