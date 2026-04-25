# ReviveGuard — Production Server Setup Guide
# Run these commands on a fresh Ubuntu 22.04 VPS (Hetzner CX21 recommended)
# Estimated time: ~45 minutes

# ─── 1. System packages ───────────────────────────────────────────────────────
apt update && apt upgrade -y
apt install -y git curl unzip nginx postgresql-16 redis-server supervisor certbot python3-certbot-nginx rclone

# ─── 2. PHP 8.3 ───────────────────────────────────────────────────────────────
add-apt-repository ppa:ondrej/php -y
apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
               php8.3-zip php8.3-pgsql php8.3-redis php8.3-bcmath php8.3-gd \
               php8.3-intl php8.3-tokenizer php8.3-dom

# ─── 3. Node.js 20 ───────────────────────────────────────────────────────────
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
npm install -g pm2

# ─── 4. Composer ─────────────────────────────────────────────────────────────
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# ─── 5. PostgreSQL database ───────────────────────────────────────────────────
sudo -u postgres psql <<'SQL'
CREATE USER reviveguard_user WITH PASSWORD 'CHANGE_ME_STRONG_PASSWORD';
CREATE DATABASE reviveguard OWNER reviveguard_user;
GRANT ALL PRIVILEGES ON DATABASE reviveguard TO reviveguard_user;
SQL

# ─── 6. Deploy Laravel app ───────────────────────────────────────────────────
mkdir -p /var/www
cd /var/www
git clone https://github.com/YOUR_ORG/reviveguard.git
cd reviveguard
cp .env.production.example .env
# Edit .env and fill in all values
nano .env

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data /var/www/reviveguard
chmod -R 755 /var/www/reviveguard/storage /var/www/reviveguard/bootstrap/cache

# ─── 7. Install Puppeteer service ────────────────────────────────────────────
cd /var/www/reviveguard/puppeteer-service
npm install
# Install Chromium dependencies (Puppeteer)
npx puppeteer browsers install chrome

# ─── 8. Install Uptime Kuma ──────────────────────────────────────────────────
mkdir -p /opt/uptime-kuma
cd /opt/uptime-kuma
git clone https://github.com/louislam/uptime-kuma.git .
npm install
npm run build

# ─── 9. PM2 (Puppeteer + Uptime Kuma) ────────────────────────────────────────
cd /var/www/reviveguard
pm2 start deploy/ecosystem.config.js
pm2 startup          # follow the output instructions to register with systemd
pm2 save

# ─── 10. Supervisor (Laravel Horizon + Scheduler) ────────────────────────────
cp /var/www/reviveguard/deploy/supervisor/reviveguard.conf /etc/supervisor/conf.d/
supervisorctl reread && supervisorctl update
supervisorctl start reviveguard-horizon reviveguard-scheduler

# ─── 11. Nginx ────────────────────────────────────────────────────────────────
cp /var/www/reviveguard/deploy/nginx/app.reviveguard.com.conf    /etc/nginx/sites-available/
cp /var/www/reviveguard/deploy/nginx/portal.reviveguard.com.conf /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/app.reviveguard.com.conf    /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/portal.reviveguard.com.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# ─── 12. SSL (Let's Encrypt) ──────────────────────────────────────────────────
# Point both DNS records to your VPS IP first, then:
certbot --nginx -d app.reviveguard.com -d portal.reviveguard.com
# Certbot auto-configures nginx SSL; verify auto-renewal:
certbot renew --dry-run

# ─── 13. Database backup cron ────────────────────────────────────────────────
chmod +x /var/www/reviveguard/deploy/backup-db.sh
# Configure rclone B2 remote:
rclone config
# Add cron (runs 02:00 UTC daily):
(crontab -l 2>/dev/null; echo "0 2 * * * /var/www/reviveguard/deploy/backup-db.sh >> /var/log/reviveguard-backup.log 2>&1") | crontab -

# ─── 14. Whop live webhook ────────────────────────────────────────────────────
# In Whop dashboard → Your Company → Developer → Webhooks
# Add: https://app.reviveguard.com/api/v1/webhooks/whop
# Events: membership.went_valid, membership.went_invalid, membership.was_banned
# Copy the webhook secret → update WHOP_WEBHOOK_SECRET in .env
# Then: php artisan config:cache

# ─── 15. VPS snapshot (Hetzner) ──────────────────────────────────────────────
# In Hetzner Cloud console:
# Server → Backups → Enable automatic backups (daily, 20% extra cost)

echo ""
echo "✓ ReviveGuard production setup complete"
echo ""
echo "Verify everything is running:"
echo "  pm2 list"
echo "  supervisorctl status"
echo "  systemctl status nginx php8.3-fpm"
echo "  curl https://app.reviveguard.com/up"
echo "  curl https://portal.reviveguard.com/up"
