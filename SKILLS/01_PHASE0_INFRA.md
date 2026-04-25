# SKILL: Phase 0 — Infrastructure Setup

> Load this skill before doing ANY server setup work.
> References: `02_SYSTEM_ARCHITECTURE.md`, `09_DEV_EXECUTION_PLAN.md`

---

## What This Skill Covers
Provisioning and hardening the Hetzner CX31 VPS, installing all runtime dependencies, configuring Nginx as a reverse proxy, setting up all sidecar services (Uptime Kuma, Puppeteer), and getting a deployable Laravel skeleton running over HTTPS.

---

## Server Specification
| Item | Value |
|---|---|
| Provider | Hetzner Cloud |
| Plan | CX31 (4 vCPU, 8GB RAM, 80GB SSD) |
| OS | Ubuntu 22.04 LTS |
| Location | Ashburn, VA (US) or Falkenstein, DE (EU) |
| Monthly cost | ~€12 |

---

## Day 1-2: VPS + Security Hardening

### Step 1: Initial server access
```bash
ssh root@<server-ip>
apt update && apt upgrade -y
```

### Step 2: Create deploy user (never run app as root)
```bash
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

### Step 3: SSH hardening (`/etc/ssh/sshd_config`)
```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
MaxAuthTries 3
```
```bash
systemctl restart sshd
```

### Step 4: UFW firewall (only open what's needed)
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP (redirect to HTTPS)
ufw allow 443/tcp   # HTTPS
ufw enable
```
**NEVER open port 5432 (PostgreSQL), 6379 (Redis), 3001 (Uptime Kuma), or 3002 (Puppeteer) to the public.**

### Step 5: Automatic security updates
```bash
apt install unattended-upgrades -y
dpkg-reconfigure --priority=low unattended-upgrades
# Select "Yes" to automatic upgrades
```

### Step 6: Fail2Ban (brute force protection)
```bash
apt install fail2ban -y
systemctl enable fail2ban
systemctl start fail2ban
```

---

## Day 2: Runtime Stack Installation

### PHP 8.3-FPM
```bash
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
  php8.3-intl php8.3-gd php8.3-opcache
```

### PHP-FPM pool config (`/etc/php/8.3/fpm/pool.d/reviveguard.conf`)
```ini
[reviveguard]
user = deploy
group = deploy
listen = /run/php/php8.3-fpm-reviveguard.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 8
```

### PostgreSQL 16
```bash
apt install -y postgresql-16
sudo -u postgres psql -c "CREATE USER reviveguard WITH PASSWORD 'STRONG_RANDOM_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE reviveguard OWNER reviveguard;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE reviveguard TO reviveguard;"
```
**Bind PostgreSQL to 127.0.0.1 only** (it does by default — verify in `/etc/postgresql/16/main/postgresql.conf`): `listen_addresses = 'localhost'`

### Redis
```bash
apt install -y redis-server
# Edit /etc/redis/redis.conf:
# bind 127.0.0.1 ::1  (already default, verify)
# requirepass STRONG_REDIS_PASSWORD
systemctl enable redis-server
```

### Node.js 20
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt install -y nodejs
npm install -g pm2
```

### Composer
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### WP-CLI (for admin-side operations)
```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
```

### Nginx
```bash
apt install -y nginx
systemctl enable nginx
```

### rclone (for this server's own cron jobs)
```bash
curl https://rclone.org/install.sh | sudo bash
rclone config  # configure B2 remote: name "b2", type Backblaze B2
```

---

## Day 3: Uptime Kuma Setup

Run headless — no public port, only accessible internally.

```bash
# As deploy user
cd /home/deploy
git clone https://github.com/louislam/uptime-kuma.git
cd uptime-kuma
npm install --production
# Test it works:
node server/server.js
# Ctrl+C once confirmed working
```

**PM2 management:**
```bash
pm2 start server/server.js --name uptime-kuma -- --port 3001
pm2 save
pm2 startup  # run the output command as root to enable on reboot
```

**Uptime Kuma initial setup:**
- Visit `http://127.0.0.1:3001` via SSH tunnel to set admin credentials
- SSH tunnel from local machine: `ssh -L 3001:127.0.0.1:3001 deploy@<server-ip>`
- Set admin username + password, store in `.env` as `UPTIME_KUMA_USERNAME` and `UPTIME_KUMA_PASSWORD`

**Verify Uptime Kuma API:**
```bash
curl -X POST http://127.0.0.1:3001/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YOUR_PASS"}'
```

---

## Day 4: Puppeteer Microservice

Create at `/home/deploy/puppeteer-service/`:

### `package.json`
```json
{
  "name": "reviveguard-pdf",
  "version": "1.0.0",
  "main": "index.js",
  "dependencies": {
    "express": "^4.18.0",
    "puppeteer": "^22.0.0"
  }
}
```

### `index.js`
```javascript
const express = require('express');
const puppeteer = require('puppeteer');
const app = express();

app.use(express.json({ limit: '5mb' }));

// Only accept requests from localhost
app.use((req, res, next) => {
  const ip = req.ip || req.connection.remoteAddress;
  if (ip !== '127.0.0.1' && ip !== '::1' && ip !== '::ffff:127.0.0.1') {
    return res.status(403).json({ error: 'Forbidden' });
  }
  next();
});

app.post('/render', async (req, res) => {
  const { html } = req.body;
  if (!html || typeof html !== 'string') {
    return res.status(400).json({ error: 'html string required' });
  }

  let browser;
  try {
    browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    const page = await browser.newPage();
    await page.setContent(html, { waitUntil: 'networkidle0' });
    const pdf = await page.pdf({
      format: 'A4',
      printBackground: true,
      margin: { top: '20mm', right: '15mm', bottom: '20mm', left: '15mm' },
    });
    res.set('Content-Type', 'application/pdf');
    res.send(pdf);
  } catch (err) {
    console.error('PDF render error:', err);
    res.status(500).json({ error: 'Render failed' });
  } finally {
    if (browser) await browser.close();
  }
});

app.listen(3002, '127.0.0.1', () => {
  console.log('Puppeteer PDF service running on 127.0.0.1:3002');
});
```

```bash
cd /home/deploy/puppeteer-service
npm install
# Install Chromium dependencies:
npx puppeteer browsers install chrome
pm2 start index.js --name puppeteer-pdf
pm2 save
```

**Test:**
```bash
curl -X POST http://127.0.0.1:3002/render \
  -H "Content-Type: application/json" \
  -d '{"html":"<h1>Test PDF</h1>"}' \
  --output test.pdf
```

---

## Day 5: Backblaze B2

1. Create account at backblaze.com
2. Create bucket: `reviveguard-backups` (private, US West region)
3. Create Application Key: `reviveguard-app-key` with read+write to the bucket
4. Store Key ID and Application Key in `.env`
5. **Configure lifecycle rules** (do this now, not later):
   - This is done programmatically via B2 API when a site is created
   - The API sets `daysFromHidingToDeleting` per prefix based on the plan
   - See `SKILLS/10_REPORTS_BACKUP.md` for implementation

---

## Day 5: SSL + Nginx Virtual Hosts

### DNS (configure before this step)
Point these DNS A records to your VPS IP:
- `app.reviveguard.com` → VPS IP
- `portal.reviveguard.com` → VPS IP

### Install Certbot
```bash
apt install -y certbot python3-certbot-nginx
```

### Nginx site configs

**`/etc/nginx/sites-available/app.reviveguard.com`:**
```nginx
server {
    listen 80;
    server_name app.reviveguard.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.reviveguard.com;

    ssl_certificate /etc/letsencrypt/live/app.reviveguard.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.reviveguard.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;

    root /home/deploy/reviveguard/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm-reviveguard.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Prevent access to sensitive files
    location ~* \.(env|log|git)$ {
        deny all;
    }

    client_max_body_size 100M;
}
```

**Same pattern for `portal.reviveguard.com`** — identical config, same document root.

```bash
ln -s /etc/nginx/sites-available/app.reviveguard.com /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/portal.reviveguard.com /etc/nginx/sites-enabled/
certbot --nginx -d app.reviveguard.com -d portal.reviveguard.com
nginx -t
systemctl reload nginx
```

---

## Day 6: Laravel App Skeleton Deployment

### GitHub repo + deploy setup
```bash
# On VPS as deploy user:
cd /home/deploy
git clone git@github.com:youraccount/reviveguard.git reviveguard
cd reviveguard
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# Fill in all .env values (see 09_DEV_EXECUTION_PLAN.md Part 5)
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### GitHub Actions deploy script (`.github/workflows/deploy.yml`):
```yaml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to VPS
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.VPS_HOST }}
          username: deploy
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd /home/deploy/reviveguard
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan horizon:terminate
            sudo supervisorctl restart reviveguard-horizon:*
```

### Supervisor for Horizon (`/etc/supervisor/conf.d/reviveguard-horizon.conf`):
```ini
[program:reviveguard-horizon]
process_name=%(program_name)s
command=php /home/deploy/reviveguard/artisan horizon
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/home/deploy/reviveguard/storage/logs/horizon.log
stopwaitsecs=3600
```
```bash
supervisorctl reread
supervisorctl update
supervisorctl start reviveguard-horizon:*
```

### Laravel Scheduler (cron)
```bash
# crontab -e as deploy user
* * * * * cd /home/deploy/reviveguard && php artisan schedule:run >> /dev/null 2>&1
```

---

## Phase 0 Definition of Done

Before moving to Phase 1, verify ALL of these:

```
[ ] SSH works as deploy user, root login disabled
[ ] UFW active — only ports 22, 80, 443 open
[ ] https://app.reviveguard.com — SSL valid, Laravel 404 page shows (no errors)
[ ] https://portal.reviveguard.com — SSL valid, same
[ ] PostgreSQL accessible from localhost only (test: psql -h 127.0.0.1 -U reviveguard)
[ ] Redis accessible from localhost only
[ ] Uptime Kuma responding on 127.0.0.1:3001 (via SSH tunnel)
[ ] Puppeteer service generates PDF via curl test
[ ] Backblaze B2 bucket accessible (rclone lsd b2:reviveguard-backups)
[ ] GitHub Actions deploy runs successfully on push to main
[ ] Horizon starts via Supervisor and appears in ps aux
[ ] Laravel Scheduler runs (check storage/logs/laravel.log after 1 minute)
[ ] PM2 status shows uptime-kuma and puppeteer-pdf as online
```
