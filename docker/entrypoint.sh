#!/usr/bin/env sh
set -e

# Copy docker env template if .env does not exist
if [ ! -f "/var/www/.env" ] && [ -f "/var/www/.env.docker.example" ]; then
    echo "Creating .env from .env.docker.example..."
    cp /var/www/.env.docker.example /var/www/.env
fi

# Ensure permissions on writable storage and bootstrap cache
if [ -d "/var/www/storage" ]; then
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
fi

# Link storage directory if not already linked
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

# Generate APP_KEY if empty or placeholder
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:PLACEHOLDER_KEY" ]; then
    if ! grep -q "^APP_KEY=base64:" /var/www/.env 2>/dev/null; then
        echo "Generating application encryption key..."
        php artisan key:generate --force --no-interaction || true
    fi
fi

# Wait for MySQL database if host is specified
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Checking database readiness on $DB_HOST..."
    max_tries=30
    count=0
    until php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: 3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        count=$((count+1))
        if [ "$count" -ge "$max_tries" ]; then
            echo "Warning: Database connection timed out. Proceeding anyway."
            break
        fi
        sleep 1
    done
    echo "Database connection established!"
fi

# Run database migrations if RUN_MIGRATIONS is set to true
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations automatically..."
    php artisan migrate --force --no-interaction || true
    if [ ! -f "/var/www/storage/installed.lock" ]; then
        echo "Creating installation lock file..."
        echo '{"installed":true,"method":"docker_auto","installed_at":"'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'"}' > /var/www/storage/installed.lock
        chown www-data:www-data /var/www/storage/installed.lock 2>/dev/null || true
    fi
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
