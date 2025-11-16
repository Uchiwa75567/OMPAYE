#!/usr/bin/env sh
# Entrypoint script for Render / Docker
# Clears caches (so runtime env vars are used), then starts the app on $PORT (default 9000)

set -e

echo "Starting OMPAYE API..."

# Use Render's PORT env if available, otherwise 9000
PORT_VAL="${PORT:-9000}"

# Clear caches so runtime environment variables are picked up
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optionally create optimized caches (uncomment if you want to enable caching at runtime)
# php artisan config:cache || true
# php artisan route:cache || true

echo "Listening on 0.0.0.0:${PORT_VAL}"
exec php artisan serve --host=0.0.0.0 --port=${PORT_VAL}