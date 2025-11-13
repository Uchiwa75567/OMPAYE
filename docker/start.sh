#!/bin/bash
set -e

echo "🚀 Starting OM Paye Application..."

# Wait for database
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database..."
    python3 -c "import urllib.request; import sys; urllib.request.urlopen('$DATABASE_URL').read()" 2>/dev/null || {
        while ! nc -z ${DB_HOST:-localhost} ${DB_PORT:-5432}; do
            echo "Waiting for database..."
            sleep 1
        done
        echo "Database is up!"
    }
fi

# Clear and cache
echo "🔧 Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache for production
if [ "${APP_ENV}" = "production" ]; then
    echo "📦 Caching for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database if needed
if [ "${SEED_DATABASE}" = "true" ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed --force
fi

# Generate Passport keys if they don't exist
if [ ! -f storage/oauth-private.key ]; then
    echo "🔐 Generating Passport keys..."
    php artisan passport:keys
fi

# Set permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/storage
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Start services
echo "🔄 Starting services..."
supervisord -c /etc/supervisord.conf &

echo "✅ OM Paye application started successfully!"