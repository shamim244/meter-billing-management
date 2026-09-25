#!/usr/bin/env sh
set -e

# Ensure permissions on writable storage and bootstrap cache
if [ -d "/var/www/storage" ]; then
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
fi

# Ensure SQLite file permissions if SQLite is used
if [ -f "/var/www/database/database.sqlite" ]; then
    chown www-data:www-data /var/www/database/database.sqlite 2>/dev/null || true
    chmod 664 /var/www/database/database.sqlite 2>/dev/null || true
fi

# Link storage directory if not already linked
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Run database migrations if RUN_MIGRATIONS is set to true
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || true
fi

# Cache configuration, routes, and views if APP_ENV is production and CACHE_ON_STARTUP is enabled
if [ "${APP_ENV:-production}" = "production" ] && [ "${CACHE_ON_STARTUP:-false}" = "true" ]; then
    echo "Optimizing framework caches..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Execute passed command (default: php-fpm)
exec "$@"
