#!/bin/bash
# ─────────────────────────────────────────────────────────────────────
# bolareel.com — deploy code mới nhất từ git lên production.
#
#   cd /var/www/bolareel && sudo bash scripts/deploy.sh
#
# Làm: git pull, composer install, npm build, migrate, rebuild cache,
# restart queue worker. An toàn chạy lại nhiều lần.
# ─────────────────────────────────────────────────────────────────────

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/bolareel}"
APP_USER="${APP_USER:-www-data}"

RED=$'\033[0;31m'; GRN=$'\033[0;32m'; BLU=$'\033[0;34m'; OFF=$'\033[0m'
step() { echo; echo "${BLU}==> $*${OFF}"; }
ok()   { echo "  ${GRN}✓${OFF} $*"; }
fail() { echo "  ${RED}✗${OFF} $*"; }

cd "$APP_DIR"

step "[1/7] Git pull"
git pull origin main
ok "$(git log -1 --oneline)"

step "[2/7] Composer install"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
ok "composer xong"

step "[3/7] NPM build (Vite)"
npm ci
npm run build
ok "frontend build xong"

step "[4/7] Migrate DB"
php artisan migrate --force
ok "migrate xong"

step "[5/7] Rebuild cache"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
ok "cache rebuild xong"

step "[6/7] Restart queue worker"
php artisan queue:restart
ok "worker sẽ tự khởi động lại sau job hiện tại (supervisor autorestart)"

step "[7/7] Quyền sở hữu file"
chown -R "$APP_USER":"$APP_USER" "$APP_DIR"
ok "chown xong"

echo
echo "${GRN}=== Deploy xong. ===${OFF}"
echo "Kiểm tra: tail -f $APP_DIR/storage/logs/laravel.log"
