#!/bin/bash

# Serveur de développement simple sans Docker (pour diagnostic)
echo "🚀 OMPAYE Serveur Simple - Diagnostic Mode"

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Installez PHP 8.3+ d'abord."
    echo "Sur Ubuntu/Debian: sudo apt install php8.3 php8.3-cli php8.3-fpm"
    exit 1
fi

echo "✅ PHP détecté: $(php -v | head -1)"

# Copier l'environnement si nécessaire
if [ ! -f .env ]; then
    echo "📄 Configuration .env..."
    cp .env.local .env 2>/dev/null || echo "⚠️  Créez un fichier .env manuellement"
fi

# Installer composer si vendor n'existe pas
if [ ! -d "vendor" ]; then
    echo "📦 Installation des dépendances..."
    composer install
fi

# Optimisations Laravel
echo "🔧 Optimisations Laravel..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Générer clé si nécessaire
if ! grep -q "APP_KEY=" .env 2>/dev/null; then
    echo "🔑 Génération de la clé Laravel..."
    php artisan key:generate --force
fi

# Générer documentation Swagger
echo "📖 Génération documentation..."
php artisan l5-swagger:generate 2>/dev/null || echo "⚠️  Swagger: exécution manuelle requise"

echo ""
echo "🌐 Démarrage du serveur sur port 8081..."
echo "📋 URLs disponibles:"
echo "   - API: http://localhost:8081/api/ping"
echo "   - Documentation: http://localhost:8081/api/documentation"
echo ""
echo "⏹️  Ctrl+C pour arrêter"
echo "==============================================="

# Démarrer le serveur
php artisan serve --host=0.0.0.0 --port=8081