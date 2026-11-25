# ============================================
# Shared PHP Base
# ============================================
FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache freetype icu-libs libjpeg-turbo libpng libzip \
    && apk add --no-cache --virtual .php-extension-build-deps \
        freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        calendar \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && apk del .php-extension-build-deps

# ============================================
# Stage 1: Composer Dependencies
# ============================================
FROM php-base AS composer-builder

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN apk add --no-cache unzip

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# ============================================
# Stage 2: Frontend Assets Build
# ============================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci --include=optional

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.js ./

# Build frontend assets
RUN npm run build

# ============================================
# Stage 3: Production Image
# ============================================
FROM php-base

RUN apk add --no-cache nginx supervisor curl zip unzip

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Interim mitigation for the 2026-10-21 PHP security release.
# session.upload_progress.enabled is INI_PERDIR, so ini_set() cannot change it;
# it must ship as a conf.d drop-in loaded after php.ini.
COPY docker/php/conf.d/zz-upload-progress.ini /usr/local/etc/php/conf.d/zz-upload-progress.ini

# PHP-FPM configuration
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

# OPcache configuration for production
#COPY <<EOF /usr/local/etc/php/conf.d/opcache.ini
#opcache.enable=1
#opcache.memory_consumption=256
#opcache.interned_strings_buffer=16
#opcache.max_accelerated_files=20000
#opcache.validate_timestamps=0
#opcache.save_comments=1
#opcache.fast_shutdown=1
#EOF

# PHP configuration
#COPY <<EOF /usr/local/etc/php/conf.d/custom.ini
# memory_limit=256M
#upload_max_filesize=64M
#post_max_size=64M
#max_execution_time=60
#expose_php=Off
#EOF

# Set working directory
WORKDIR /var/www/html

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy Supervisord configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application from composer stage
COPY --from=composer-builder /app/vendor ./vendor

# Copy built frontend assets from frontend stage
COPY --from=frontend-builder /app/public/build ./public/build

# Copy application source
COPY . .

# Remove development files (keep docker configs already copied)
RUN rm -rf \
    tests \
    .env.example \
    .editorconfig \
    .gitattributes \
    .gitignore \
    phpunit.xml \
    .github \
    node_modules \
    *.md \
    .aider* \
    .claude \
    docker

# Create necessary directories and set permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /tmp/nginx \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE 8081

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8081/health || exit 1

# Use entrypoint for runtime initialization
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
