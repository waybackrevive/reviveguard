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
| PHP | 8.4 | 8.4 |
| Node.js | 18+ | 20 LTS |
| PostgreSQL | 15+ | 16 |
| Redis | 7+ | 7 |

**Domain architecture (recommended for this project):**
- `reviveguard.com` (and `www`) → static marketing site on Hostinger
- `app.reviveguard.com` → Laravel app on VPS (Admin + Client Portal on same host)
- Portal path on same host: `https://app.reviveguard.com/portal/login`

### DNS Records

Set these records at your DNS provider:

| Host | Type | Target |
|---|---|---|
| `@` | A | Hostinger static site IP (or Hostinger default target) |
| `www` | CNAME | `reviveguard.com` |
| `app` | A | Your VPS public IP |

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

### PHP 8.4
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
  php8.4 php8.4-fpm php8.4-cli \
  php8.4-pgsql php8.4-redis php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip \
  php8.4-bcmath php8.4-intl php8.4-gd

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
CREATE USER reviveguard WITH PASSWORD 'CHANGE_THIS_STRONG_PASSWORD';
CREATE DATABASE reviveguard OWNER reviveguard;
GRANT ALL PRIVILEGES ON DATABASE reviveguard TO reviveguard;
\q
SQL

# Test connection
psql -h 127.0.0.1 -U reviveguard -d reviveguard -c "SELECT version();"
```

> **Important:** Replace `CHANGE_THIS_STRONG_PASSWORD` with a real strong password. Store it in a password manager.

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
# Use the production template from repo root (recommended for server)
cp /var/www/reviveguard/.env.production.example /var/www/reviveguard/app-code/.env

# Edit production env values
nano .env

# Generate APP_KEY after saving
php artisan key:generate
```

Production `.env` rules (important):
- Yes, you can use values from `.env.production.example` as base.
- Replace every `<CHANGE_ME>` placeholder with real production secrets.
- Keep these defaults unless you have a reason to change:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `QUEUE_CONNECTION=redis`
  - `UPTIME_KUMA_URL=http://127.0.0.1:3001`
  - `PUPPETEER_SERVICE_URL=http://127.0.0.1:3002`
- Do NOT copy local/dev values from `app-code/.env.example` to production.

Minimum values you must set before first run:
- `APP_URL`, `PORTAL_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `REDIS_PASSWORD` (if Redis is password-protected)
- `RESEND_API_KEY`
- `WHOP_API_KEY`, `WHOP_WEBHOOK_SECRET`
- `WHOP_PLAN_MONITOR_ID`, `WHOP_PLAN_GUARD_ID`, `WHOP_PLAN_SHIELD_ID`
- `UPTIME_KUMA_API_KEY`, `UPTIME_KUMA_WEBHOOK_SECRET`
- `B2_KEY_ID`, `B2_APP_KEY`, `B2_BUCKET_ID`, `B2_BUCKET_NAME`
- `SENTRY_LARAVEL_DSN` (recommended)

If any of the keys above are missing in your copied `.env`, add them manually.

Quick note for clarity:
- You do NOT need to change every line in `.env`.
- Keep defaults as-is, only fill required keys above.
- We are keeping Resend flow as-is (`MAIL_MAILER=log` + `RESEND_API_KEY`).

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

Use one Nginx site for both Admin and Portal on the same domain.

### App + Portal (`app.reviveguard.com`)
```bash
sudo nano /etc/nginx/sites-available/reviveguard-admin
```

Paste:
```nginx
server {
    listen 80;
  listen [::]:80;
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

    # ACME challenge path for Let's Encrypt (must be publicly reachable)
    location ^~ /.well-known/acme-challenge/ {
      root /var/www/reviveguard/app-code/public;
      default_type "text/plain";
      try_files $uri =404;
    }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
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

### Enable site
```bash
sudo ln -s /etc/nginx/sites-available/reviveguard-admin /etc/nginx/sites-enabled/

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

# Ensure ACME directory exists
mkdir -p /var/www/reviveguard/app-code/public/.well-known/acme-challenge

# Quick reachability test (must return the same token on both)
echo acme-test > /var/www/reviveguard/app-code/public/.well-known/acme-challenge/ping
curl -4 http://app.reviveguard.com/.well-known/acme-challenge/ping
curl -6 http://app.reviveguard.com/.well-known/acme-challenge/ping

# Get certificates (will auto-edit nginx configs)
sudo certbot --nginx -d app.reviveguard.com \
  --email your@email.com --agree-tos --non-interactive

# Test auto-renewal
sudo certbot renew --dry-run
```

> Certbot adds a cron job automatically to renew certs before expiry.
>
> If `curl -6` fails or shows unexpected response, your `AAAA` DNS for `app` is not aligned with this VPS.
> Either fix IPv6 routing on this VPS or remove the `AAAA` record for `app` and keep only `A`.

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
- API Key (Settings → API Keys)
- Get the API key from Settings → API Keys
- Store these in `.env` as `UPTIME_KUMA_API_KEY` (and keep `UPTIME_KUMA_WEBHOOK_SECRET` set)

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
4. Keep `MAIL_MAILER=log` (or configure SMTP if you want password-reset emails via Hostinger).
  App alert/report/ticket emails are sent by `NotificationService` through Resend API using `RESEND_API_KEY`.

### Whop (Billing)
1. Go to https://whop.com → Your App → Webhooks
2. Add webhook URL: `https://app.reviveguard.com/api/v1/webhooks/whop`
3. Select events:
  - `membership.went_valid`
  - `membership.went_invalid`
  - `membership.was_banned`
4. Copy the webhook secret → `WHOP_WEBHOOK_SECRET=`
5. Get API key → `WHOP_API_KEY=`
6. Create 3 plans (Monitor / Guard / Shield) and copy their IDs:
   - `WHOP_PLAN_MONITOR_ID=`
   - `WHOP_PLAN_GUARD_ID=`
   - `WHOP_PLAN_SHIELD_ID=`

### Backblaze B2 (Backup Storage)
1. Go to https://www.backblaze.com/b2/cloud-storage.html
2. Create a bucket named `reviveguard-backups` (private)
3. Create an Application Key with read+write access to that bucket
4. Copy Key ID → `B2_KEY_ID=`
5. Copy Application Key → `B2_APP_KEY=`
6. Copy Bucket ID → `B2_BUCKET_ID=`
7. Set bucket name → `B2_BUCKET_NAME=reviveguard-backups`

### Sentry (Error Tracking)
1. Go to https://sentry.io → New Project → Laravel
2. Copy the DSN → `SENTRY_LARAVEL_DSN=`

---

## 16. Final .env Reference

```dotenv
APP_NAME="ReviveGuard"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://app.reviveguard.com
PORTAL_URL=https://app.reviveguard.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reviveguard
DB_USERNAME=reviveguard_user
DB_PASSWORD=<CHANGE_ME>

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.reviveguard.com

CACHE_STORE=redis
CACHE_PREFIX=rg_

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@reviveguard.com
MAIL_FROM_NAME="ReviveGuard"

RESEND_API_KEY=re_<CHANGE_ME>

WHOP_WEBHOOK_SECRET=<CHANGE_ME>
WHOP_API_KEY=<CHANGE_ME>
WHOP_PLAN_MONITOR_ID=<CHANGE_ME>
WHOP_PLAN_GUARD_ID=<CHANGE_ME>
WHOP_PLAN_SHIELD_ID=<CHANGE_ME>

UPTIME_KUMA_URL=http://127.0.0.1:3001
UPTIME_KUMA_API_KEY=<CHANGE_ME>
UPTIME_KUMA_WEBHOOK_SECRET=<CHANGE_ME>

B2_KEY_ID=<CHANGE_ME>
B2_APP_KEY=<CHANGE_ME>
B2_BUCKET_ID=<CHANGE_ME>
B2_BUCKET_NAME=reviveguard-backups

PUPPETEER_SERVICE_URL=http://127.0.0.1:3002

SENTRY_LARAVEL_DSN=https://<key>@o<org>.ingest.sentry.io/<project>
SENTRY_TRACES_SAMPLE_RATE=0.1

VITE_APP_NAME="${APP_NAME}"
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
curl -s -o /dev/null -w "%{http_code}" https://app.reviveguard.com/portal/login
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
1. Runs tests on every push to `master`
2. On passing tests, SSH deploys to the server automatically

### Step 1 — Generate SSH key on server (one time only)

GitHub Actions connects to your server using an SSH key — **not** your root password. You must generate a key for the `deploy` user:

```bash
# SSH into server as root, then switch to deploy user
su - deploy

# Generate a new ED25519 key (press Enter 3 times — no passphrase)
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_actions

# Authorize this key for deploy user login
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Print the PRIVATE key — copy ALL of this output including the header/footer lines
cat ~/.ssh/github_actions
```

Copy the entire output (from `-----BEGIN OPENSSH PRIVATE KEY-----` to `-----END OPENSSH PRIVATE KEY-----`).

### Step 2 — Add secrets in GitHub

Go to your repo → **Settings → Secrets and variables → Actions → New repository secret**

| Secret name | Value to paste |
|---|---|
| `SSH_PRIVATE_KEY` | The private key you just copied (entire contents of `~/.ssh/github_actions`) |
| `SERVER_HOST` | Your VPS IP address (e.g. `167.x.x.x`) |
| `SERVER_USER` | `deploy` |

> You already added `SERVER_HOST` and `SERVER_USER` — just add `SSH_PRIVATE_KEY` now.

### Step 3 — Allow deploy user to restart supervisor without password

The deploy script runs `sudo supervisorctl restart ...`. Add a sudoers rule:

```bash
# As root on server:
echo 'deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl' | sudo tee /etc/sudoers.d/deploy-supervisor
sudo chmod 440 /etc/sudoers.d/deploy-supervisor
```

### What the deploy does on each push to `master`:
1. Pulls latest code from `master`
2. Runs `composer install` (no dev deps)
3. Runs `npm ci && npm run build`
4. `config:cache`, `route:cache`, `view:cache`, `event:cache`
5. `php artisan migrate --force`
6. Restarts queue workers (`supervisorctl restart reviveguard-worker:*`)
7. Restarts PDF service (`pm2 restart reviveguard-pdf`)

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

---

## Live Log Commands (How to See What's Happening on Server)

SSH in first, then use these:

### Laravel app log — most useful. Shows PHP errors, exceptions, Livewire, Whop, etc.
```bash
# Stream live (Ctrl+C to stop)
tail -f /var/www/reviveguard/app-code/storage/logs/laravel.log

# Today's dated log (if using daily channel)
tail -f /var/www/reviveguard/app-code/storage/logs/laravel-$(date +%Y-%m-%d).log

# Last 100 lines
tail -100 /var/www/reviveguard/app-code/storage/logs/laravel.log

# Grep for specific keyword (e.g. Whop errors)
grep -i "error\|exception\|whop\|checkout" /var/www/reviveguard/app-code/storage/logs/laravel.log | tail -50
```

### Nginx logs
```bash
sudo tail -f /var/log/nginx/access.log   # all HTTP requests
sudo tail -f /var/log/nginx/error.log    # PHP-FPM / proxy errors
```

### PHP-FPM errors (if you see 502 Bad Gateway)
```bash
sudo tail -f /var/log/php8.4-fpm.log
```

### Queue worker log
```bash
tail -f /var/www/reviveguard/app-code/storage/logs/worker.log
```

### Whop checkout debugging
```bash
cd /var/www/reviveguard/app-code

# Verify env vars are loaded into config
php artisan tinker --execute="echo config('services.whop.plan_guard_id');"
php artisan tinker --execute="echo config('services.whop.plan_monitor_id');"
php artisan tinker --execute="echo config('services.whop.plan_shield_id');"

# Check DB plans have whop_plan_id
php artisan tinker --execute="\App\Models\Plan::all(['slug','whop_plan_id'])->each(fn(\$p)=>dump(\$p->toArray()));"
```

> **Most common cause of "Proceed to checkout" button doing nothing:**
> `WHOP_PLAN_GUARD_ID` / `WHOP_PLAN_MONITOR_ID` / `WHOP_PLAN_SHIELD_ID` not set in `.env`.
> Run the tinker commands above. If output is empty, add the IDs to `.env` then:
> ```bash
> php artisan config:cache
> php artisan view:clear
> ```

---

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

### Composer install fails with "requires php >=8.4"
If you see errors like `symfony/* requires php >=8.4`, your server is on PHP 8.3 while the lock file expects PHP 8.4.

```bash
# Install PHP 8.4 packages
sudo apt update
sudo apt install -y \
  php8.4 php8.4-fpm php8.4-cli \
  php8.4-pgsql php8.4-redis php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip \
  php8.4-bcmath php8.4-intl php8.4-gd

# Restart FPM and nginx
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# Verify active PHP version
php -v

# Retry install
cd /var/www/reviveguard/app-code
composer install --no-dev --optimize-autoloader \
  --ignore-platform-req=ext-pcntl \
  --ignore-platform-req=ext-posix
```

### Certbot fails with `unauthorized` or ACME `404`
If Let's Encrypt shows `Invalid response ... /.well-known/acme-challenge/...: 404`, run:

```bash
# 1) Confirm nginx is serving the app site config
sudo nginx -t
sudo systemctl reload nginx

# 2) Verify challenge path from internet on both IP stacks
echo acme-test > /var/www/reviveguard/app-code/public/.well-known/acme-challenge/ping
curl -4 http://app.reviveguard.com/.well-known/acme-challenge/ping
curl -6 http://app.reviveguard.com/.well-known/acme-challenge/ping

# 3) If IPv6 (curl -6) is wrong/failing, remove AAAA record for host `app` in DNS
#    and keep only A record to VPS IPv4.

# 4) Retry certbot
sudo certbot --nginx -d app.reviveguard.com
```

---

## Quick Reference Card

| URL | Purpose |
|-----|---------|
| `https://app.reviveguard.com/admin` | Admin panel (Filament) |
| `https://app.reviveguard.com/portal/login` | Client portal |
| `https://app.reviveguard.com/api/v1/heartbeat` | Agent API |
| `http://127.0.0.1:3001` | Uptime Kuma (internal) |
| `http://127.0.0.1:3002/health` | PDF service health |

| Credential | Default | Change? |
|-----------|---------|---------|
| Admin email | `admin@reviveguard.test` | Yes — use real email |
| Admin password | `password` | **Yes — immediately!** |
| DB password | `secret` (dev only) | Yes — strong password |
| Redis password | `secret` (dev only) | Yes — strong password |
