# ReviveGuard — Complete Deployment Guide

> **Every single step** to go from zero to a live, production ReviveGuard instance on a Ubuntu 22.04 VPS.

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Initial Server Setup](#2-initial-server-setup)
3. [Install Core Dependencies](#3-install-core-dependencies)
4. [PostgreSQL Setup](#4-postgresql-setup)
5. [Redis Setup](#5-redis-setup)
6. [Clone & Configure the App](#6-clone--configure-the-app)
7. [Laravel App Setup](#7-laravel-app-setup)
8. [Nginx Configuration](#8-nginx-configuration)
9. [SSL Certificates (Let's Encrypt)](#9-ssl-certificates-lets-encrypt)
10. [Queue Worker (Supervisor)](#10-queue-worker-supervisor)
11. [Puppeteer PDF Service](#11-puppeteer-pdf-service)
12. [PM2 Process Manager](#12-pm2-process-manager)
13. [Uptime Kuma](#13-uptime-kuma)
14. [Cron Jobs (Scheduler)](#14-cron-jobs-scheduler)
15. [Third-Party Services Config](#15-third-party-services-config)
16. [Final .env Reference](#16-final-env-reference)
17. [First Run Checklist](#17-first-run-checklist)
18. [GitHub Actions CI/CD](#18-github-actions-cicd)
19. [WordPress Plugin Install](#19-wordpress-plugin-install)
20. [Troubleshooting](#20-troubleshooting)

---

## 1. Server Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB SSD | 40 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |
| PHP | 8.2+ | 8.3 |
| Node.js | 18+ | 20 LTS |
| PostgreSQL | 15+ | 16 |
| Redis | 7+ | 7 |

**Domain needed:** e.g. `app.reviveguard.com` and `portal.reviveguard.com`
(Can be same domain with path, or two separate subdomains — guide uses two subdomains)

---

## 2. Initial Server Setup

### Execution Context (Read Once)

- Login starts as `root` only for creating the deployment user.
- After `su - deploy`, **all remaining steps in this guide are run as `deploy` user**.
- Any system-level command is already written with `sudo` (example: `sudo apt install ...`).
- If you see shell prompt like `deploy@server:~$`, you are in the correct user.

### Path Map (Used Throughout Guide)

- Project root: `/var/www/reviveguard`
- Laravel app: `/var/www/reviveguard/app-code`
- Nginx site config: `/etc/nginx/sites-available/reviveguard`
- Public web root (Laravel): `/var/www/reviveguard/app-code/public`

```bash
# SSH in as root
ssh root@YOUR_SERVER_IP

# Create a deploy user (never run app as root)
adduser deploy
usermod -aG sudo deploy

# Copy SSH key to deploy user
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy

# Switch to deploy user for all remaining steps
su - deploy
```

### Firewall
```bash
sudo ufw allow OpenSSH
# Some servers do not have the "Nginx Full" UFW profile yet.
# Open web ports directly so setup never blocks here.
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

If you want to use the Nginx UFW profile later (optional), run this after Nginx is installed:
```bash
sudo ufw app list | grep -i nginx
sudo ufw allow 'Nginx Full'
```

---

## 3. Install Core Dependencies

> Run this entire section as `deploy` user (not root). Use `sudo` exactly as shown.

### System packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y \
  git curl unzip zip wget gnupg2 \
  build-essential software-properties-common \
  ca-certificates lsb-release
```

### PHP 8.3
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
  php8.3 php8.3-fpm php8.3-cli \
  php8.3-pgsql php8.3-redis php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip \
  php8.3-bcmath php8.3-intl php8.3-gd

# Verify
php -v
```

### Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Node.js 20 LTS
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v   # should show v20.x
npm -v
```

### Nginx
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## 4. PostgreSQL Setup

```bash
# Install PostgreSQL 16
sudo apt install -y postgresql-16 postgresql-client-16
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Create database and user
sudo -u postgres psql << 'SQL'
CREATE USER reviveguard WITH PASSWORD 'wayBack26@25dev';
CREATE DATABASE reviveguard OWNER reviveguard;
GRANT ALL PRIVILEGES ON DATABASE reviveguard TO reviveguard;
\q
SQL

# Test connection
psql -h 127.0.0.1 -U reviveguard -d reviveguard -c "SELECT version();"
```

> **Important:** Replace `wayBack26@25dev` with a real strong password. Store it in a password manager.
wayBack26@25dev
revivE26@25dev
---

## 5. Redis Setup

```bash
sudo apt install -y redis-server

# Set a password — edit config
sudo nano /etc/redis/redis.conf
# Find: # requirepass foobared
# Change to: requirepass CHANGE_THIS_REDIS_PASSWORD

# Enable and restart
sudo systemctl enable redis-server
sudo systemctl restart redis-server

# Test
redis-cli -a CHANGE_THIS_REDIS_PASSWORD ping
# Should output: PONG
```

---

## 6. Clone & Configure the App

```bash
# Create app directory
sudo mkdir -p /var/www/reviveguard
sudo chown deploy:deploy /var/www/reviveguard

# Clone the repo
cd /var/www
git clone https://github.com/YOUR_ORG/reviveguard.git reviveguard
cd reviveguard/app-code
```

---

## 7. Laravel App Setup

### Install PHP dependencies
```bash
composer install --no-dev --optimize-autoloader \
  --ignore-platform-req=ext-pcntl \
  --ignore-platform-req=ext-posix
```

### Create .env
```bash
cp .env.example .env
nano .env
# Fill in all values — see Section 16 for full reference
```

### Generate app key
```bash
php artisan key:generate
```

### Set storage permissions
```bash
sudo chown -R deploy:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Run migrations and seeders
```bash
# Migrations (creates all tables)
php artisan migrate --force

# Core seed data (tenant + plans)
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=PlanSeeder

# Admin user + test client (FIRST DEPLOY ONLY)
php artisan db:seed --class=QaSeeder
```

### Cache config for production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 8. Nginx Configuration

### Admin panel (`app.reviveguard.com`)
```bash
sudo nano /etc/nginx/sites-available/reviveguard-admin
```

Paste:
```nginx
server {
    listen 80;
    server_name app.reviveguard.com;
    root /var/www/reviveguard/app-code/public;

    index index.php;
    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
}
```

### Client portal (`portal.reviveguard.com`)
```bash
sudo nano /etc/nginx/sites-available/reviveguard-portal
```

Paste:
```nginx
server {
    listen 80;
    server_name portal.reviveguard.com;
    root /var/www/reviveguard/app-code/public;

    index index.php;
    charset utf-8;
    client_max_body_size 16M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
}
```

### Enable both sites
```bash
sudo ln -s /etc/nginx/sites-available/reviveguard-admin /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/reviveguard-portal /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Reload
sudo systemctl reload nginx
```

---

## 9. SSL Certificates (Let's Encrypt)

```bash
# Install certbot
sudo apt install -y certbot python3-certbot-nginx

# Get certificates (will auto-edit nginx configs)
sudo certbot --nginx -d app.reviveguard.com -d portal.reviveguard.com \
  --email your@email.com --agree-tos --non-interactive

# Test auto-renewal
sudo certbot renew --dry-run
```

> Certbot adds a cron job automatically to renew certs before expiry.

---

## 10. Queue Worker (Supervisor)

The queue processes jobs like heartbeat checks, notifications, and backups in the background.

```bash
sudo apt install -y supervisor

sudo nano /etc/supervisor/conf.d/reviveguard-worker.conf
```

Paste:
```ini
[program:reviveguard-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/reviveguard/app-code/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/reviveguard/app-code/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reviveguard-worker:*
sudo supervisorctl status
```

---

## 11. Puppeteer PDF Service

```bash
# Install Chromium dependencies
sudo apt install -y \
  libnss3 libatk1.0-0 libatk-bridge2.0-0 \
  libcups2 libxcomposite1 libxdamage1 libxrandr2 \
  libgbm1 libasound2

cd /var/www/reviveguard/puppeteer-service
npm install
```

---

## 12. PM2 Process Manager

PM2 keeps the Puppeteer service running and restarts it on crash.

```bash
sudo npm install -g pm2

cd /var/www/reviveguard/puppeteer-service
pm2 start server.js --name reviveguard-pdf --env production

# Save process list for auto-start on reboot
pm2 save

# Generate and enable startup script
pm2 startup systemd -u deploy --hp /home/deploy
# Copy and run the command it outputs
```

### Verify PDF service
```bash
curl http://127.0.0.1:3002/health
# Should return: {"status":"ok"}
```

---

## 13. Uptime Kuma

Uptime Kuma monitors your sites. Run it in Docker for easy management:

```bash
# Install Docker
curl -fsSL https://get.docker.com | bash
sudo usermod -aG docker deploy
newgrp docker

# Run Uptime Kuma
docker run -d \
  --restart=always \
  --name uptime-kuma \
  -p 127.0.0.1:3001:3001 \
  -v uptime-kuma:/app/data \
  louislam/uptime-kuma:1
```

Access at `http://YOUR_SERVER_IP:3001` (temporarily) to create admin account, then set:
- Username + password
- Get the API key from Settings → API Keys
- Store these in `.env` as `UPTIME_KUMA_USERNAME`, `UPTIME_KUMA_PASSWORD`

Add a Nginx proxy for Uptime Kuma if you want `status.reviveguard.com`.

---

## 14. Cron Jobs (Scheduler)

The Laravel scheduler runs heartbeat checks, missed-heartbeat detection, and cleanup jobs.

```bash
crontab -e
```

Add this single line:
```
* * * * * cd /var/www/reviveguard/app-code && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and Laravel decides which jobs to execute based on their schedule.

---

## 15. Third-Party Services Config

### Resend (Email)
1. Go to https://resend.com → Create API Key
2. Verify your sender domain (`notifications@reviveguard.com`)
3. Add `RESEND_API_KEY=re_xxxx` to `.env`
4. Set `MAIL_MAILER=resend` in production `.env`

### Whop (Billing)
1. Go to https://whop.com → Your App → Webhooks
2. Add webhook URL: `https://app.reviveguard.com/webhooks/whop`
3. Copy the webhook secret → `WHOP_WEBHOOK_SECRET=`
4. Get API key → `WHOP_API_KEY=`
5. Create 3 plans (Monitor / Guard / Shield) and copy their IDs:
   - `WHOP_PLAN_MONITOR_ID=`
   - `WHOP_PLAN_GUARD_ID=`
   - `WHOP_PLAN_SHIELD_ID=`

### Backblaze B2 (Backup Storage)
1. Go to https://www.backblaze.com/b2/cloud-storage.html
2. Create a bucket named `reviveguard-backups` (private)
3. Create an Application Key with read+write access to that bucket
4. Copy Key ID → `BACKBLAZE_KEY_ID=`
5. Copy Application Key → `BACKBLAZE_APP_KEY=`
6. Copy Bucket ID → `BACKBLAZE_BUCKET_ID=`

### Sentry (Error Tracking)
1. Go to https://sentry.io → New Project → Laravel
2. Copy the DSN → `SENTRY_LARAVEL_DSN=`

---

## 16. Final .env Reference

```dotenv
APP_NAME=ReviveGuard
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://app.reviveguard.com

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reviveguard
DB_USERNAME=reviveguard
DB_PASSWORD=YOUR_DB_PASSWORD

SESSION_DRIVER=redis
SESSION_LIFETIME=480
SESSION_ENCRYPT=true
SESSION_DOMAIN=.reviveguard.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis
CACHE_PREFIX=reviveguard_

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=6379

MAIL_MAILER=resend
MAIL_FROM_ADDRESS="notifications@reviveguard.com"
MAIL_FROM_NAME="ReviveGuard"

RESEND_API_KEY=re_xxxxxxxxxxxx

WHOP_API_KEY=
WHOP_WEBHOOK_SECRET=
WHOP_PLAN_MONITOR_ID=
WHOP_PLAN_GUARD_ID=
WHOP_PLAN_SHIELD_ID=

BACKBLAZE_KEY_ID=
BACKBLAZE_APP_KEY=
BACKBLAZE_BUCKET_ID=
BACKBLAZE_BUCKET_NAME=reviveguard-backups

UPTIME_KUMA_URL=http://127.0.0.1:3001
UPTIME_KUMA_USERNAME=
UPTIME_KUMA_PASSWORD=
UPTIME_KUMA_WEBHOOK_SECRET=

PUPPETEER_SERVICE_URL=http://127.0.0.1:3002

PORTAL_URL=https://portal.reviveguard.com

SENTRY_LARAVEL_DSN=
```

---

## 17. First Run Checklist

Run these in order after everything is set up:

```bash
cd /var/www/reviveguard/app-code

# 1. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 2. Run migrations
php artisan migrate --force

# 3. Seed base data
php artisan db:seed --class=TenantSeeder
php artisan db:seed --class=PlanSeeder

# 4. Create your admin account
php artisan db:seed --class=QaSeeder
# Then log in at https://app.reviveguard.com/admin with:
# Email:    admin@reviveguard.test
# Password: password
# IMPORTANT: Change password immediately after first login!

# 5. Rebuild production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Check all services running
sudo supervisorctl status          # queue worker
pm2 status                         # puppeteer PDF service
docker ps                          # uptime kuma
sudo systemctl status nginx        # web server
sudo systemctl status postgresql   # database
sudo systemctl status redis-server # cache/queue
```

### Smoke tests
```bash
# Admin panel loads
curl -s -o /dev/null -w "%{http_code}" https://app.reviveguard.com/admin
# Expected: 302 (redirect to login)

# Portal loads
curl -s -o /dev/null -w "%{http_code}" https://portal.reviveguard.com/portal/login
# Expected: 200

# Agent API responds
curl -s -o /dev/null -w "%{http_code}" https://app.reviveguard.com/api/v1/heartbeat \
  -H "Authorization: Bearer invalid" -X POST
# Expected: 401

# PDF service health
curl http://127.0.0.1:3002/health
# Expected: {"status":"ok"}
```

---

## 18. GitHub Actions CI/CD

File already exists at `.github/workflows/deploy.yml`. It:
1. Runs all 46 tests on every push to `main`
2. On passing tests, SSH deploys to the server

### Setup required secrets in GitHub:
Go to your repo → Settings → Secrets and variables → Actions

| Secret | Value |
|--------|-------|
| `SSH_PRIVATE_KEY` | Your deploy user's private SSH key |
| `SERVER_HOST` | Your server IP or domain |
| `SERVER_USER` | `deploy` |
| `SERVER_PATH` | `/var/www/reviveguard/app-code` |

### Deploy script runs on server (SSH):
```bash
cd /var/www/reviveguard
git pull origin main
cd app-code
composer install --no-dev --optimize-autoloader \
  --ignore-platform-req=ext-pcntl \
  --ignore-platform-req=ext-posix
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart reviveguard-worker:*
pm2 restart reviveguard-pdf
```

---

## 19. WordPress Plugin Install

### On each client WordPress site:

1. Go to WordPress Admin → Plugins → Add New → Upload Plugin
2. Upload `reviveguard-agent.zip` (from `wp-plugin/` folder)
3. Click **Install Now** → **Activate**
4. Go to **Settings → ReviveGuard**
5. In **Agent Token**: paste the token from your ReviveGuard admin dashboard
   - Admin → Sites → (site row) → copy the raw token shown
6. In **Platform URL**: enter `https://app.reviveguard.com`
7. Click **Save Settings**
   - You should see: ✓ **Token is set and saved.**
8. Click **Send Test Heartbeat**
   - Status should change from `PENDING` to `Connected`

### Get the agent token:
- ReviveGuard Admin → Sites → Create New Site for the client
- After creation, the raw token is shown once — copy it and give it to the client

### B2 Backup Settings (Optional):
Only fill in B2 settings if the client's plan includes agent-triggered backups. Click "Show/Hide" to expand the B2 section.

---

## 20. Troubleshooting

### Admin panel broken / 500 error
```bash
# Check logs
tail -f /var/www/reviveguard/app-code/storage/logs/laravel-$(date +%Y-%m-%d).log

# Clear all caches
cd /var/www/reviveguard/app-code
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear
```

### Portal login failing
```bash
# Re-seed credentials
cd /var/www/reviveguard/app-code
php artisan db:seed --class=QaSeeder
php artisan cache:clear
```

### Queue jobs not processing
```bash
sudo supervisorctl status
sudo supervisorctl restart reviveguard-worker:*
sudo supervisorctl tail reviveguard-worker:reviveguard-worker_00
```

### PDF reports not generating
```bash
pm2 status
pm2 logs reviveguard-pdf --lines 50
pm2 restart reviveguard-pdf
```

### Database connection refused
```bash
sudo systemctl status postgresql
# If stopped:
sudo systemctl start postgresql
```

### Redis connection refused
```bash
sudo systemctl status redis-server
sudo systemctl start redis-server
```

### WP plugin "Heartbeat failed"
1. Check **Platform URL** in plugin settings = `https://app.reviveguard.com`
2. Check the agent token is correct (matches what's in ReviveGuard admin → Sites)
3. Check the site exists in ReviveGuard admin and `is_active = true`
4. Check server firewall allows outbound HTTPS from WordPress server

### Permissions error on storage
```bash
sudo chown -R deploy:www-data /var/www/reviveguard/app-code/storage
sudo chmod -R 775 /var/www/reviveguard/app-code/storage
```

### Migrate failed
```bash
# Check DB connection
php artisan db:show

# Run specific migration
php artisan migrate --path=database/migrations/FILENAME.php

# Full reset (WARNING: destroys all data — dev only)
php artisan migrate:fresh --seed
```

---

## Quick Reference Card

| URL | Purpose |
|-----|---------|
| `https://app.reviveguard.com/admin` | Admin panel (Filament) |
| `https://portal.reviveguard.com/portal/login` | Client portal |
| `https://app.reviveguard.com/api/v1/heartbeat` | Agent API |
| `http://127.0.0.1:3001` | Uptime Kuma (internal) |
| `http://127.0.0.1:3002/health` | PDF service health |

| Credential | Default | Change? |
|-----------|---------|---------|
| Admin email | `admin@reviveguard.test` | Yes — use real email |
| Admin password | `password` | **Yes — immediately!** |
| DB password | `secret` (dev only) | Yes — strong password |
| Redis password | `secret` (dev only) | Yes — strong password |
