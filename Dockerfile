# ==========================================
# Stage 1: Build Frontend Assets (Vite with Bun)
# ==========================================
FROM oven/bun:1-alpine AS frontend
WORKDIR /app

# Copy dependency definition and lockfile for fast, reproducible caching
COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

# Copy source and build production assets
COPY . .
RUN bun run build

# ==========================================
# Stage 2: PHP Application Container
# ==========================================
FROM php:8.4-fpm-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    gosu \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install official PHP extension helper
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install required PHP extensions
# Including brotli and zstd for the Adaptive Hybrid Compression engine
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    mbstring \
    xml \
    curl \
    zip \
    intl \
    gd \
    exif \
    pcntl \
    opcache \
    redis \
    brotli \
    zstd \
    sockets

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy custom PHP configuration
COPY docker/php/custom.ini $PHP_INI_DIR/conf.d/99-custom.ini

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy application files
COPY . .

# Copy compiled frontend assets from Bun frontend stage
COPY --from=frontend /app/public/build ./public/build

# Install PHP dependencies without dev packages for production
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Create necessary storage directories and set permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# PHP production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 9000

CMD ["php-fpm"]
