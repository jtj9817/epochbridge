#!/bin/sh
set -e

echo "Starting EpochBridge container..."

# Run Laravel optimizations at runtime (after env vars are available)
echo "Caching Laravel configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations if AUTO_MIGRATE is set
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Fix permissions for runtime-generated files
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
