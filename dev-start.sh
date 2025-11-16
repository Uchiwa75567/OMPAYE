#!/bin/bash

# Script de démarrage OMPAYE en développement local
echo "🚀 Démarrage OMPAYE en mode développement..."

# Vérifier si Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez l'installer d'abord."
    exit 1
fi

# Copier l'environnement de développement si nécessaire
if [ ! -f .env ]; then
    echo "📄 Copie du fichier .env.local vers .env..."
    cp .env.local .env
    echo "✅ Fichier .env créé"
fi

# Créer la clé Laravel si nécessaire
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Génération de la clé Laravel..."
    php artisan key:generate --env=local 2>/dev/null || echo "⚠️  Impossible de générer la clé. Exécutez 'php artisan key:generate' manuellement."
fi

# Créer les répertoires nécessaires
echo "📁 Création des répertoires..."
mkdir -p storage/logs
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache

# Démarrer les services Docker
echo "🐳 Démarrage des services Docker..."
docker-compose -f docker-compose.simple.yml up -d

echo "⏳ Attente du démarrage des services..."
sleep 5

# Installer les dépendances si vendor n'existe pas
if [ ! -d "vendor" ]; then
    echo "📦 Installation des dépendances Composer..."
    docker-compose -f docker-compose.simple.yml exec app composer install
fi

# Générer la documentation Swagger
echo "📖 Génération de la documentation Swagger..."
docker-compose -f docker-compose.simple.yml exec app php artisan l5-swagger:generate

# Migrer la base de données
echo "🗄️  Migration de la base de données..."
docker-compose -f docker-compose.simple.yml exec app php artisan migrate --force

echo "✅ OMPAYE démarré en développement !"
echo ""
echo "🌐 URLs d'accès :"
echo "   - API : http://localhost:8081"
echo "   - Documentation : http://localhost:8081/api/documentation"
echo "   - Admin DB : http://localhost:8082"
echo ""
echo "📋 Pour arrêter : ./stop.sh"