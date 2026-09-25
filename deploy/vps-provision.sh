#!/usr/bin/env bash
# ==============================================================================
# NBPDCL Meter Billing & Management System
# Type 3 Native VPS Bare-Metal Provisioner
# Target OS: Ubuntu 22.04 LTS / Ubuntu 24.04 LTS (x86_64 / arm64)
# ==============================================================================

set -euo pipefail

# Color Codes
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

APP_DIR="/var/www/meter-billing"
PHP_VERSION="8.4"
DB_NAME="meter_billing"
DB_USER="meter_user"
DB_PASS="$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)"

echo -e "${CYAN}================================================================${NC}"
echo -e "${CYAN}  NBPDCL SaaS - Type 3 Native VPS Auto-Provisioner (PHP ${PHP_VERSION})  ${NC}"
echo -e "${CYAN}================================================================${NC}"

# Check for root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}[ERROR] Please run this script as root or via sudo.${NC}"
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo -e "\n${YELLOW}>>> Step 1: Updating System Repositories...${NC}"
apt-get update -y
apt-get upgrade -y
apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    zip \
    software-properties-common \
    ca-certificates \
    lsb-release \
    apt-transport-https \
    ufw \
    openssl \
    supervisor \
    cron \
    fail2ban

echo -e "\n${YELLOW}>>> Step 2: Configuring Ondřej Surý PHP ${PHP_VERSION} PPA...${NC}"
add-apt-repository -y ppa:ondrej/php
apt-get update -y

echo -e "\n${YELLOW}>>> Step 3: Installing PHP ${PHP_VERSION} and Extensions...${NC}"
apt-get install -y --no-install-recommends \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-opcache \
    php${PHP_VERSION}-readline \
    php${PHP_VERSION}-dev \
    php-pear

# Tune PHP-FPM Configuration
echo -e "\n${YELLOW}>>> Configuring PHP ${PHP_VERSION} FPM Settings...${NC}"
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 128M/' "$PHP_INI"
    sed -i 's/post_max_size = .*/post_max_size = 128M/' "$PHP_INI"
    sed -i 's/memory_limit = .*/memory_limit = 512M/' "$PHP_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    sed -i 's/;date.timezone =/date.timezone = Asia\/Kolkata/' "$PHP_INI"
fi
systemctl restart php${PHP_VERSION}-fpm
systemctl enable php${PHP_VERSION}-fpm

echo -e "\n${YELLOW}>>> Step 4: Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo -e "\n${YELLOW}>>> Step 5: Installing Redis 7 & MySQL Server...${NC}"
apt-get install -y redis-server mysql-server

systemctl enable redis-server
systemctl restart redis-server

systemctl enable mysql
systemctl start mysql

# Setup MySQL Database and User if not already present
echo -e "\n${YELLOW}>>> Configuring MySQL Database [${DB_NAME}]...${NC}"
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo -e "\n${YELLOW}>>> Step 6: Installing & Configuring Nginx...${NC}"
apt-get install -y nginx

# Deploy Nginx Site Configuration
cat > /etc/nginx/sites-available/meter-billing <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/meter-billing/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;
    client_max_body_size 128M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml application/json application/javascript application/rss+xml application/atom+xml image/svg+xml;
}
EOF

ln -sf /etc/nginx/sites-available/meter-billing /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo -e "\n${YELLOW}>>> Step 7: Configuring Systemd Queue Worker & Scheduler...${NC}"
cat > /etc/systemd/system/meter-billing-queue.service <<EOF
[Unit]
Description=NBPDCL Laravel Queue Worker
After=network.target mysql.service redis.service

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable meter-billing-queue.service

# Setup Cron for Laravel Scheduler
cat > /etc/cron.d/meter-billing <<EOF
* * * * * www-data /usr/bin/php ${APP_DIR}/artisan schedule:run >> /dev/null 2>&1
EOF
chmod 0644 /etc/cron.d/meter-billing

echo -e "\n${YELLOW}>>> Step 8: Configuring Application Directory...${NC}"
mkdir -p "${APP_DIR}"
chown -R www-data:www-data "${APP_DIR}"

echo -e "\n${YELLOW}>>> Step 9: Configuring UFW Firewall...${NC}"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN}  ✓ Native VPS Provisioning Complete!                           ${NC}"
echo -e "${GREEN}================================================================${NC}"
echo -e "Web Root:       ${APP_DIR}"
echo -e "PHP Version:    ${PHP_VERSION} (FPM socket: /run/php/php8.4-fpm.sock)"
echo -e "MySQL Database: ${DB_NAME}"
echo -e "MySQL Username: ${DB_USER}"
echo -e "MySQL Password: ${DB_PASS}"
echo -e ""
echo -e "${CYAN}Next Step - Deploy or Restore Migration Bundle:${NC}"
echo -e "1. Clone application or copy code into: ${APP_DIR}"
echo -e "2. Copy migration package to: ${APP_DIR}/storage/app/migrations/migration-xxxx.zip"
echo -e "3. Run restore command:"
echo -e "   cd ${APP_DIR} && sudo -u www-data php artisan app:migration-unpack /path/to/migration-xxxx.zip"
echo -e "4. Start queue worker: sudo systemctl start meter-billing-queue.service"
echo -e "${GREEN}================================================================${NC}"
