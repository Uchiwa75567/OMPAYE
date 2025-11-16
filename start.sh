#!/bin/bash

# Start script pour Render - Solution immédiate
echo "Starting OMPAYE API on Render..."

# S'assurer que le PORT est défini
export PORT=${PORT:-8080}

# Optimisations Laravel pour production
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true

# Démarrer le serveur Laravel
exec php artisan serve --host=0.0.0.0 --port=$PORT