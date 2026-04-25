#!/usr/bin/env bash
# ─── ReviveGuard: manual deploy script ───────────────────────────────────────
# Run from VPS: bash /var/www/reviveguard/deploy/deploy.sh
# Requires: git, composer, npm, php, supervisor, pm2

set -euo pipefail

APP_DIR="/var/www/reviveguard"
DEPLOY_USER="www-data"

cd "$APP_DIR"

echo ""
echo "╔══════════════════════════════════════╗"
echo "║  ReviveGuard Deploy                  ║"
echo "╚══════════════════════════════════════╝"
echo ""

echo "[1/8] Pulling latest code from main..."
git pull origin main

echo "[2/8] Installing Composer dependencies..."
composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

echo "[3/8] Building frontend assets..."
npm ci
npm run build

echo "[4/8] Putting app into maintenance mode..."
php artisan down --retry=5

echo "[5/8] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[6/8] Running database migrations..."
php artisan migrate --force

echo "[7/8] Restarting Horizon + Puppeteer..."
php artisan horizon:terminate
sudo supervisorctl restart reviveguard-horizon
pm2 restart reviveguard-puppeteer

echo "[8/8] Bringing app back online..."
php artisan up

echo ""
echo "✓ Deploy complete at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
