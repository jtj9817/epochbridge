#!/bin/sh
set -e

echo "Starting EpochBridge container..."

printf 'listen %s;\n' "$PORT" > /etc/nginx/container-port.conf

request_scheme='$scheme'
if [ -n "${K_SERVICE:-}" ]; then
    request_scheme='$http_x_forwarded_proto'
fi
printf 'map %s $fastcgi_request_https { default $https; https on; }\n' "$request_scheme" \
    > /etc/nginx/request-https.conf

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
