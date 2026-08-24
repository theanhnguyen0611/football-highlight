#!/bin/bash
# ─────────────────────────────────────────────────────────────────────
# bolareel.com — cài + sửa mọi thứ pipeline video cần trên web server.
#
#   sudo bash scripts/install-deps.sh
#
# Chạy lại bao nhiêu lần cũng được: mỗi bước tự kiểm tra trước khi làm.
# Không đụng tới .env, database, hay code.
#
# Cài: ffmpeg, rsync, Node 20, Deno, yt-dlp, Chromium (Playwright)
# Sửa: cron scheduler dùng sai định dạng, Playwright browser sai chủ sở hữu
# ─────────────────────────────────────────────────────────────────────

set -uo pipefail

APP_DIR="${APP_DIR:-/var/www/bolareel}"
APP_USER="${APP_USER:-www-data}"
PW_PATH="/usr/local/share/ms-playwright"

RED=$'\033[0;31m'; GRN=$'\033[0;32m'; YLW=$'\033[0;33m'; BLU=$'\033[0;34m'; OFF=$'\033[0m'
step() { echo; echo "${BLU}==> $*${OFF}"; }
ok()   { echo "  ${GRN}✓${OFF} $*"; }
warn() { echo "  ${YLW}!${OFF} $*"; }
fail() { echo "  ${RED}✗${OFF} $*"; }

FAILED=0

[ "$(id -u)" -eq 0 ] || { fail "Phải chạy bằng root: sudo bash $0"; exit 1; }
[ -d "$APP_DIR" ]    || { fail "Không thấy $APP_DIR (đặt APP_DIR=... nếu khác)"; exit 1; }

# ── 1. Gói apt ───────────────────────────────────────────────────────
step "[1/6] ffmpeg + rsync"
missing=()
for p in ffmpeg rsync; do command -v "$p" >/dev/null || missing+=("$p"); done
if [ ${#missing[@]} -eq 0 ]; then
    ok "đã có sẵn"
else
    apt-get update -qq
    apt-get install -y -qq "${missing[@]}" && ok "cài xong: ${missing[*]}" || fail "apt install lỗi"
fi

# ── 2. Node 20 ───────────────────────────────────────────────────────
step "[2/6] Node.js 20"
node_major=0
command -v node >/dev/null && node_major=$(node -v | sed 's/v\([0-9]*\).*/\1/')
if [ "$node_major" -ge 20 ] 2>/dev/null; then
    ok "đã có $(node -v)"
else
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - >/dev/null 2>&1
    apt-get install -y -qq nodejs && ok "cài xong $(node -v)" || fail "cài Node lỗi"
fi

# ── 3. Deno ──────────────────────────────────────────────────────────
# scripts/download-highlight.ts — nhánh tải HLS chính (chọn 720p).
# Thiếu Deno thì rơi về nhánh curl trong PHP: chậm hơn nhưng vẫn chạy.
step "[3/6] Deno"
if command -v deno >/dev/null; then
    ok "đã có $(deno --version | head -1)"
else
    curl -fsSL https://deno.land/install.sh | DENO_INSTALL=/usr/local sh -s -- -y >/dev/null 2>&1
    if command -v deno >/dev/null; then ok "cài xong $(deno --version | head -1)"; else fail "cài Deno lỗi"; fi
fi

# ── 4. yt-dlp ────────────────────────────────────────────────────────
# Bản trong apt luôn lạc hậu vì YouTube đổi API liên tục → lấy binary chính chủ.
step "[4/6] yt-dlp"
if [ -x /usr/local/bin/yt-dlp ]; then
    ok "đã có $(/usr/local/bin/yt-dlp --version 2>/dev/null) — chạy 'yt-dlp -U' để cập nhật"
else
    curl -fsSL https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp \
        -o /usr/local/bin/yt-dlp && chmod a+rx /usr/local/bin/yt-dlp \
        && ok "cài xong $(/usr/local/bin/yt-dlp --version 2>/dev/null)" || fail "tải yt-dlp lỗi"
fi

# ── 5. Playwright Chromium ───────────────────────────────────────────
# Mặc định Playwright tải về ~/.cache/ms-playwright. Script này chạy bằng root
# còn queue worker chạy bằng www-data → www-data không đọc được, và
# hoofoot-embed.js / dasfootball-embed.js im lặng trả null.
step "[5/6] Playwright Chromium → $PW_PATH"
mkdir -p "$PW_PATH"

if [ ! -d "$APP_DIR/node_modules/playwright" ]; then
    warn "chưa có node_modules/playwright — chạy npm ci (KHÔNG --omit=dev)"
    ( cd "$APP_DIR" && npm ci ) >/dev/null 2>&1 \
        && ok "npm ci xong" || fail "npm ci lỗi — chạy tay trong $APP_DIR"
fi

if compgen -G "$PW_PATH/chromium-*" >/dev/null; then
    ok "Chromium đã có"
else
    ( cd "$APP_DIR" && PLAYWRIGHT_BROWSERS_PATH="$PW_PATH" npx --yes playwright install --with-deps chromium ) \
        >/dev/null 2>&1 && ok "cài xong Chromium" || fail "cài Chromium lỗi"
fi
chmod -R a+rX "$PW_PATH" 2>/dev/null

# ── 6. Sửa cron + supervisor ─────────────────────────────────────────
step "[6/6] Cron scheduler + supervisor"

# Crontab của user KHÔNG có cột user. Dòng "* * * * * www-data cd ..." khiến
# cron coi 'www-data' là tên lệnh → scheduler chưa bao giờ chạy.
if crontab -l 2>/dev/null | grep -q "www-data.*artisan schedule:run"; then
    crontab -l 2>/dev/null | grep -v "artisan schedule:run" | crontab -
    warn "đã gỡ dòng crontab hỏng (có cột user trong user-crontab)"
fi

cat > /etc/cron.d/bolareel <<CRON
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
PLAYWRIGHT_BROWSERS_PATH=$PW_PATH
HOME=/tmp
* * * * * $APP_USER cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1
CRON
chmod 0644 /etc/cron.d/bolareel
ok "/etc/cron.d/bolareel đã ghi"

SUP_CONF=/etc/supervisor/conf.d/bolareel-worker.conf
if [ -f "$SUP_CONF" ]; then
    if grep -q "PLAYWRIGHT_BROWSERS_PATH" "$SUP_CONF"; then
        ok "supervisor đã có PLAYWRIGHT_BROWSERS_PATH"
    else
        cp "$SUP_CONF" "$SUP_CONF.bak.$(date +%s)"
        sed -i "/^command=/a environment=PLAYWRIGHT_BROWSERS_PATH=\"$PW_PATH\",HOME=\"/tmp\"" "$SUP_CONF"
        ok "đã thêm environment= vào supervisor (backup .bak.*)"
    fi
    supervisorctl reread >/dev/null 2>&1
    supervisorctl update >/dev/null 2>&1
    supervisorctl restart bolareel-worker:* >/dev/null 2>&1 \
        && ok "worker restart xong" || warn "không restart được worker — kiểm tra supervisorctl status"
else
    warn "không thấy $SUP_CONF — queue worker chưa được cấu hình?"
fi

# ── Kiểm tra cuối: chạy bằng chính user của worker ───────────────────
step "Kiểm tra (chạy bằng $APP_USER)"
check() {
    local label="$1"; shift
    if sudo -u "$APP_USER" env HOME=/tmp PLAYWRIGHT_BROWSERS_PATH="$PW_PATH" "$@" >/dev/null 2>&1; then
        ok "$label"
    else
        fail "$label"; FAILED=1
    fi
}
check "ffmpeg"  ffmpeg -version
check "rsync"   rsync --version

# thumbnails:download chuyển ảnh sang WebP bằng GD — php-gd của Ubuntu có sẵn
# WebP, nhưng bản build khác thì chưa chắc.
if php -r 'exit(function_exists("imagewebp") ? 0 : 1);'; then
    ok "php-gd có WebP"
else
    fail "php-gd THIẾU WebP — apt install php8.4-gd"; FAILED=1
fi

check "node"    node -v
check "deno"    deno --version
check "yt-dlp"  /usr/local/bin/yt-dlp --version

if sudo -u "$APP_USER" test -r "$(compgen -G "$PW_PATH/chromium-*/chrome-linux/chrome" | head -1)" 2>/dev/null; then
    ok "chromium ($APP_USER đọc được)"
else
    fail "chromium — $APP_USER không đọc được $PW_PATH"; FAILED=1
fi

# SSH tới SX65 phải không hỏi mật khẩu, nếu không syncToStorage sẽ treo
if sudo -u "$APP_USER" ssh -o BatchMode=yes -o ConnectTimeout=8 \
       -o StrictHostKeyChecking=accept-new "$(grep -oP '(?<=^SX65_SSH=).*' "$APP_DIR/.env" 2>/dev/null | tr -d '"'"'"'')" true 2>/dev/null; then
    ok "SSH tới SX65 (không cần mật khẩu)"
else
    warn "SSH tới SX65 lỗi — syncToStorage sẽ không đẩy được video lên storage"
    warn "  cần: ssh-keygen cho $APP_USER + ssh-copy-id tới SX65"
fi

echo
if [ "$FAILED" -eq 0 ]; then
    echo "${GRN}=== Xong. Tất cả runtime sẵn sàng. ===${OFF}"
else
    echo "${RED}=== Xong, nhưng có mục lỗi ở trên — xử lý rồi chạy lại script. ===${OFF}"
fi
echo
echo "Bước tiếp:"
echo "  cd $APP_DIR && git pull && npm ci && npm run build"
echo "  php artisan migrate --force && php artisan config:cache && php artisan route:cache"
echo "  php artisan crawl:matches --days=3      # chạy tay lượt đầu"
echo
echo "Theo dõi:  tail -f $APP_DIR/storage/logs/laravel.log"
exit "$FAILED"
