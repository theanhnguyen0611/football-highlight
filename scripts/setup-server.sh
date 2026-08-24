#!/bin/bash
# Setup script for bolareel.com on Hetzner CX23 (Ubuntu 24.04)
# Run as root: bash setup-server.sh

set -e

DOMAIN="bolareel.com"
APP_DIR="/var/www/bolareel"
DB_NAME="bolareel"
DB_USER="bolareel"
DB_PASS="$(cat /root/bolareel-db-pass.txt 2>/dev/null || openssl rand -base64 24 | tee /root/bolareel-db-pass.txt)"
REPO="https://github.com/theanhnguyen0611/football-highlight.git"

echo "==> [1/9] System update"
apt-get update -qq && apt-get upgrade -y -qq

echo "==> [2/9] Install Nginx + PHP 8.3 + MySQL + tools"
apt-get install -y -qq software-properties-common ca-certificates lsb-release curl gnupg
curl -sSLo /usr/share/keyrings/sury-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
    > /etc/apt/sources.list.d/sury-php.list
apt-get update -qq
apt-get install -y -qq \
    nginx certbot python3-certbot-nginx \
    php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring \
    php8.4-xml php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath \
    php8.4-dom php8.4-opcache \
    mysql-server \
    git curl unzip supervisor \
    ffmpeg rsync

echo "==> [2b/9] Runtime cho pipeline video"
# ffmpeg  — tải HLS + convert MP4 -> HLS (mọi nhánh download)
# rsync   — đẩy segment lên SX65
# node    — build frontend + scripts/*-embed.js (Playwright)
# yt-dlp  — tải YouTube qua UK proxy

# Node 20 (build Vite + chạy Playwright scripts)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y -qq nodejs

# yt-dlp (bản apt quá cũ, YouTube đổi API liên tục -> lấy binary chính chủ)
curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp \
    -o /usr/local/bin/yt-dlp
chmod a+rx /usr/local/bin/yt-dlp

# Chromium cho scripts/hoofoot-embed.js + dasfootball-embed.js.
# Package `playwright` đã nằm trong devDependencies (npm ci cài sẵn),
# nhưng browser binary phải tải riêng — chạy ở bước [5/9] sau npm ci.
#
# Mặc định Playwright tải về ~/.cache/ms-playwright. Script này chạy bằng
# root còn queue worker chạy bằng www-data → www-data không đọc được.
# Ép về thư mục dùng chung, và export cho cả worker lẫn cron.
export PLAYWRIGHT_BROWSERS_PATH=/usr/local/share/ms-playwright
mkdir -p "${PLAYWRIGHT_BROWSERS_PATH}"

echo "==> [3/9] Install Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> [4/9] MySQL: create database + user"
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
echo "DB password: ${DB_PASS}"
echo "(password saved to /root/bolareel-db-pass.txt)"

echo "==> [5/9] Clone repo + install dependencies"
if [ -d "${APP_DIR}/.git" ]; then
    git -C "${APP_DIR}" pull
else
    rm -rf "${APP_DIR}"
    git clone "${REPO}" "${APP_DIR}"
fi
cd "${APP_DIR}"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# npm ci (không --omit=dev): scripts/*-embed.js cần playwright,
# vốn nằm trong devDependencies. Rồi build frontend.
npm ci
PLAYWRIGHT_BROWSERS_PATH=/usr/local/share/ms-playwright \
    npx --yes playwright install --with-deps chromium
chmod -R a+rX /usr/local/share/ms-playwright
npm run build

echo "==> [6/9] Configure .env"
cp .env.example .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
php artisan key:generate

echo ""
echo ">>> EDIT /var/www/bolareel/.env and fill in:"
echo "    UK_PROXY, CDN_URL, SX65_SSH, SX65_PATH"
echo "    HIGHLIGHTLY_API_KEY (if any)"
echo "Press Enter to continue after editing..."
read -r

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "==> [7/9] Nginx config"
cat > /etc/nginx/sites-available/bolareel <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${APP_DIR}/public;
    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* { deny all; }

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
    gzip_min_length 1024;

    client_max_body_size 50M;
}
NGINX

ln -sf /etc/nginx/sites-available/bolareel /etc/nginx/sites-enabled/bolareel
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> [8/9] SSL (Let's Encrypt)"
certbot --nginx -d "${DOMAIN}" -d "www.${DOMAIN}" --non-interactive --agree-tos -m theanhnguyen0611@gmail.com

echo "==> [9/9] Supervisor (queue worker) + cron"
cat > /etc/supervisor/conf.d/bolareel-worker.conf <<SUP
[program:bolareel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
environment=PLAYWRIGHT_BROWSERS_PATH="/usr/local/share/ms-playwright",HOME="/tmp"
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUP

supervisorctl reread && supervisorctl update && supervisorctl start bolareel-worker:*

# Cron: Laravel scheduler.
# Phải dùng /etc/cron.d — crontab của user KHÔNG có cột user, nên
# "* * * * * www-data cd ..." sẽ bị cron hiểu www-data là tên lệnh.
cat > /etc/cron.d/bolareel <<CRON
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
PLAYWRIGHT_BROWSERS_PATH=/usr/local/share/ms-playwright
HOME=/tmp
* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON
chmod 0644 /etc/cron.d/bolareel

echo ""
echo "=============================="
echo " Setup DONE — bolareel.com"
echo "=============================="
echo " App dir : ${APP_DIR}"
echo " DB pass : $(cat /root/bolareel-db-pass.txt)"
echo " Next    : point DNS A record for ${DOMAIN} -> $(curl -s ifconfig.me)"
echo "=============================="
