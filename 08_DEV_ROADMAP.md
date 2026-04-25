# ReviveGuard — Development Roadmap

---

## Guiding Principle

**Ship to 3 real clients before optimizing anything.**

The roadmap is divided into phases. Phase 1 is the only one with a fixed scope. Everything else shifts based on what real clients actually ask for.

---

## Resource Assumption

- 1 developer (you or one hired developer)
- Part-time: 20-25 hrs/week
- Full-time: 40+ hrs/week

Time estimates below assume **part-time (20 hrs/week)** effort. Divide roughly in half for full-time.

---

## Phase 0 — Infrastructure Setup
**Duration:** 1 week
**Goal:** Working server, all dependencies running, deployable Laravel app

---

### Week 1 Tasks

#### Day 1-2: VPS + Base Stack
```
[ ] Provision Hetzner CX31 VPS (Ubuntu 22.04 LTS)
[ ] Configure UFW firewall: allow 22 (SSH), 80 (HTTP), 443 (HTTPS) only
[ ] Install Nginx, PHP 8.3-FPM, PostgreSQL 16, Redis, Node.js 20
[ ] Configure PHP-FPM pool for Laravel
[ ] Install Composer, WP-CLI globally
[ ] Create deploy user (non-root) with sudo access
[ ] Configure SSH key auth, disable password auth
[ ] Set up automatic security updates (unattended-upgrades)
```

#### Day 3: Laravel App Skeleton
```
[ ] Create new Laravel 11 project
[ ] Configure PostgreSQL connection in .env
[ ] Install core packages:
      - filament/filament v3
      - laravel/cashier-stripe
      - stancl/tenancy
      - laravel/horizon
      - laravel/breeze (Livewire stack)
      - iodev/whois (domain expiry checking)
      - resend/resend-php
[ ] Configure Laravel Horizon
[ ] Set up GitHub repository (private)
[ ] Configure GitHub Actions for deploy (git pull + artisan commands on push to main)
[ ] Set up .env file with all required variables
```

#### Day 4: Uptime Kuma + Puppeteer
```
[ ] Install Uptime Kuma via Docker or Node.js directly (port 3001, internal only)
[ ] Configure Uptime Kuma admin credentials
[ ] Test Uptime Kuma REST API via curl
[ ] Create Puppeteer microservice:
      - Express.js app (30 lines)
      - POST /render endpoint: receives HTML string, returns PDF
      - Install Chromium dependencies
      - Run on port 3002 (internal only)
[ ] Manage both with PM2
[ ] Configure PM2 to start on server restart
```

#### Day 5: Backblaze B2 + SSL
```
[ ] Create Backblaze B2 account
[ ] Create bucket: reviveguard-backups
[ ] Create application key with write access
[ ] Configure rclone on VPS with B2 credentials
[ ] Test rclone upload/download
[ ] Install Certbot
[ ] Point domain DNS to VPS
[ ] Issue Let's Encrypt certificates for:
      app.reviveguard.com
      portal.reviveguard.com
[ ] Configure Nginx virtual hosts for both
[ ] Test HTTPS on both domains
```

#### Day 6: Stripe + Resend
```
[ ] Create Stripe account, get API keys (test mode first)
[ ] Create 3 products in Stripe: Monitor ($19), Guard ($49), Shield ($99)
[ ] Create monthly Price for each product
[ ] Create Payment Links for each plan (Phase 1 checkout method)
[ ] Configure Stripe webhook → app.reviveguard.com/api/v1/webhooks/stripe
[ ] Create Resend account, verify sending domain
[ ] Test email send via Resend API
[ ] Configure WhatsApp Cloud API (Meta for Developers account)
```

**Phase 0 Deliverable:** HTTPS server running, all services alive, empty Laravel app accessible at app.reviveguard.com

---

## Phase 1 — Core MVP Build
**Duration:** 8 weeks
**Goal:** 3-5 paying clients fully served

---

### Week 2: Database + Tenancy + Auth

```
[ ] Create all migrations (see DATABASE_SCHEMA.md)
[ ] Configure stancl/tenancy in single-database mode
[ ] Run seed: create WaybackRevive tenant + 3 plans
[ ] Set up Laravel Breeze with Livewire stack
[ ] Configure admin panel auth (Filament) — separate guard from portal
[ ] Enable 2FA for admin (Laravel Fortify)
[ ] Basic admin panel shell: Filament panels configured, custom logo/colors
[ ] Test: login to admin, login to portal (separate sessions)
```

### Week 3: Admin Panel — Clients + Sites

```
[ ] Filament Resource: Clients
      - List view: name, email, plan, sites count, status
      - Create: name, email, phone, whatsapp, timezone, source
      - Edit: all fields
      - View: with related sites list
[ ] Filament Resource: Sites
      - List view: site name, URL, client, status, last seen, plan
      - Create site: name, URL, type (wp/html), assign to client
      - On create: auto-generate agent token (show ONCE, copy prompt)
      - On create: call Uptime Kuma API to add HTTP monitor
      - Edit site
      - View site: status, last heartbeat, SSL, domain, recent events
[ ] Agent token generation service (HMAC secret, show once)
[ ] Filament Resource: Plans (read-only view, managed via seed)
```

### Week 4: Agent API + Heartbeat Processing

```
[ ] Middleware: AgentTokenAuth (validates Bearer token, injects Site model)
[ ] Route: POST /api/v1/agent/heartbeat
[ ] HeartbeatService: update last_seen_at, site status, metadata
[ ] Job: ProcessHeartbeat (async)
[ ] Scheduler: CheckMissedHeartbeats (every 5 min)
[ ] AlertService: detect down/recovered, dispatch alerts
[ ] Route: POST /api/v1/agent/command-result
[ ] CommandResultService: update backup/update records from result
[ ] Route: POST /api/v1/agent/plugin-list
[ ] Route: POST /api/v1/agent/event
[ ] Test with curl: simulate heartbeat, confirm DB updates
[ ] Test: stop sending heartbeat, confirm status goes to 'down' after 6 min
```

### Week 5: WordPress Agent Plugin

```
[ ] Create plugin file structure (see AGENT_PLUGIN_SPEC.md)
[ ] class-api-client.php: HTTP client using wp_remote_post
[ ] class-heartbeat.php: collect site info, call API, process command list
[ ] class-site-info.php: gather WP/PHP version, plugin count, disk usage
[ ] Register WP Cron event: reviveguard_heartbeat every 5 minutes
[ ] class-command-runner.php: parse commands, dispatch to handlers
[ ] class-backup-handler.php: tar + rclone to B2
[ ] class-update-handler.php: WP-CLI core + plugin updates
[ ] class-plugin-inventory.php: get installed plugins, detect changes
[ ] admin/class-admin-page.php + settings-page.php
[ ] Test: install on real WP site, confirm heartbeat received in platform
[ ] Test: trigger backup command from admin, confirm result received
[ ] Test: trigger update command, confirm plugins updated
[ ] Package as .zip for distribution
```

### Week 6: Monitoring + SSL + Domain

```
[ ] Uptime Kuma API integration service
      - Add monitor when site created
      - Remove monitor when site deleted
      - Get uptime % for site (last 30 days)
[ ] Route: POST /api/v1/webhooks/uptime-kuma
[ ] Scheduler: CheckSslExpiry (daily at 06:00 UTC)
      - Use PHP openssl_x509_parse via stream_context
      - Update sites.ssl_expires_at
      - Log event if threshold crossed (60/30/7 days)
      - Dispatch alert if threshold is 30 or 7 days
[ ] Scheduler: CheckDomainExpiry (daily at 07:00 UTC)
      - Use iodev/whois package
      - Update sites.domain_expires_at
      - Log event + alert at thresholds
[ ] Scheduler: UpdateUptimeStats (every 6 hours)
      - Pull uptime % from Uptime Kuma API for all sites
      - Update sites.uptime_30d, sites.uptime_7d
```

### Week 7: Client Portal

```
[ ] Portal auth: Breeze login/forgot-password, client guard
[ ] Dashboard screen (Livewire component):
      - Status card, uptime %, last backup, SSL days, domain days
      - Recent events (last 5)
      - Livewire polling every 60s
[ ] Events screen: paginated list, filter by type/severity
[ ] Event detail modal (click to expand)
[ ] Reports screen: list with PDF download (generate signed B2 URL on click)
[ ] Backups screen: list last 10 backups
[ ] Support tickets screen: form + list
[ ] Account settings screen: edit name, email, WhatsApp, change password
[ ] Link to Stripe Customer Portal for billing
[ ] Plan limit enforcement on tickets
[ ] All jargon translated to plain English (review every user-facing string)
[ ] Mobile responsive review
```

### Week 8: Billing + Notifications + Reports

```
[ ] Stripe webhooks: customer.subscription.created/updated/deleted
[ ] Invoice paid/failed webhooks
[ ] BillingService: activate/pause/cancel client on webhook events
[ ] OnboardClientJob: runs on subscription created
      - Creates site record
      - Adds Uptime Kuma monitor
      - Sends welcome email with agent installation instructions
[ ] Notification emails (Resend) — all types in MVP spec
[ ] WhatsApp alert: site down + site recovered (critical only)
[ ] Puppeteer microservice: Express + Puppeteer, /render endpoint
[ ] Report Blade template (HTML → PDF):
      - Branded header (logo, month, client name, site URL)
      - Uptime summary
      - Updates applied table
      - Backups confirmed table
      - SSL + domain expiry status
      - Footer with your contact details
[ ] Scheduler: GenerateMonthlyReports (1st of month, 09:00 UTC)
[ ] Admin: manual "Generate report" action for any site
[ ] Admin: manual "Resend report email" action
[ ] Test full report flow end-to-end
```

---

## Phase 1 Testing Checklist (Before First Paying Client)

Complete these manual tests before charging anyone:

```
AGENT TESTS
[ ] Install WP plugin on a test site — heartbeat received in 5 min
[ ] Manually trigger backup from admin — backup appears in B2
[ ] Manually trigger WP update from admin — result received
[ ] Disconnect plugin (deactivate) — site marked 'down' within 6 min
[ ] Reconnect — site marked 'recovered', alert email sent

MONITORING TESTS
[ ] Add site to platform — Uptime Kuma monitor created automatically
[ ] Take test site offline — Uptime Kuma webhook fires, admin alert sent
[ ] Bring test site back — recovery alert sent
[ ] SSL expiry check — correct days shown in portal

BILLING TESTS (Stripe test mode)
[ ] Use Stripe test card to subscribe to Guard plan
[ ] Subscription webhook fires — client activated in system
[ ] Cancel subscription via Stripe — client paused in system
[ ] Payment failure test card — failure email sent to client

PORTAL TESTS
[ ] Log in as client — see only your own site
[ ] Dashboard shows correct uptime %, last backup, SSL days
[ ] Click event — detail modal shows
[ ] Download report PDF — PDF opens correctly
[ ] Submit support ticket — ticket appears in admin Filament panel
[ ] Update WhatsApp number — saved correctly

NOTIFICATION TESTS
[ ] Site down alert — email received, WhatsApp received
[ ] Site recovered — email received
[ ] SSL warning (manually set ssl_expires_at to 28 days from now) — email sent
[ ] Monthly report email — PDF attached, correct data
```

---

## Phase 1 Launch Checklist

```
[ ] Switch Stripe from test mode to live mode
[ ] Update Payment Link URLs to live Stripe links
[ ] Configure Stripe live webhook with production URL
[ ] SSL confirmed valid on app.reviveguard.com + portal.reviveguard.com
[ ] VPS daily snapshot enabled (Hetzner)
[ ] pg_dump backup cron running (daily to B2)
[ ] PM2 configured to restart on reboot (pm2 startup)
[ ] Horizon configured to restart on deploy
[ ] Error monitoring: add Sentry (Laravel package, free tier) 
      — not required for launch but recommended
[ ] Send warm outreach email to first target clients (WaybackRevive restored clients)
```

---

## Phase 2 — Growth Features
**Starts:** After first 5 paying clients and first month of real operation
**Duration:** 6-8 weeks (concurrent with serving Phase 1 clients)

**Features to add:**
- Annual billing option
- Rollback after failed WP update
- Selective plugin updates (exclude specific plugins)
- Shell script: receive commands (not just heartbeat) — add Python-based command polling
- Client subdomain portal (`client.yourdomain.com` per client)
- Malware scanning via WP-CLI + Wordfence CLI
- SEO health snapshot (quarterly, Screaming Frog CLI or custom PHP crawler)
- Better uptime chart (7/30/90 day sparkline in portal)
- Email preference center (client can set which alerts they receive)
- Resend report manually button (client-accessible)
- Referral tracking (simple: track referrer_client_id, manual discount in Stripe)

---

## Phase 3 — Scale
**Starts:** 50+ paying clients
**Hire:** Part-time support person to handle Shield client content edits and ticket responses

**Features to add:**
- Reseller/agency accounts (tenant hierarchy — already in DB schema)
- Agency dashboard: manage multiple client accounts from one login
- White-label portal per agency (custom logo, colors, domain)
- API access for agencies (read-only client data)
- Automated malware cleanup (not just detection)
- Performance monitoring (Core Web Vitals integration)
- Staging environment management
- Slack/Telegram notification option
- Bulk site operations in admin

---

## Dependency Stack — Install Order

When setting up from scratch, install in this exact order to avoid conflicts:

```
1. System packages (apt): nginx, php8.3-fpm, php8.3-cli, php8.3-mbstring,
   php8.3-xml, php8.3-curl, php8.3-zip, php8.3-pgsql, php8.3-redis,
   postgresql-16, redis-server
2. Node.js 20 (via nvm or NodeSource)
3. Composer (PHP package manager)
4. PM2 (npm install -g pm2)
5. Laravel 11 project (composer create-project)
6. Laravel packages (composer require)
7. npm packages in Laravel project (npm install + npm run build)
8. Uptime Kuma (Node.js, managed by PM2)
9. Puppeteer service (Node.js, managed by PM2)
10. rclone (for backup uploads)
11. certbot (SSL)
12. Laravel Horizon (managed by Supervisor)
13. Laravel Scheduler (system cron)
```

---

## Key Environment Variables

Document these in your `.env` — never commit to git:

```env
# App
APP_NAME="ReviveGuard"
APP_ENV=production
APP_KEY=
APP_URL=https://app.reviveguard.com
PORTAL_URL=https://portal.reviveguard.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reviveguard
DB_USERNAME=reviveguard_user
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=hello@reviveguard.com
MAIL_FROM_NAME="ReviveGuard"

# WhatsApp Cloud API
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=

# Backblaze B2
B2_ACCOUNT_ID=
B2_APPLICATION_KEY=
B2_BUCKET=reviveguard-backups
B2_REGION=us-west-004

# Internal services
UPTIME_KUMA_URL=http://127.0.0.1:3001
UPTIME_KUMA_USERNAME=
UPTIME_KUMA_PASSWORD=
PUPPETEER_URL=http://127.0.0.1:3002

# Uptime Kuma webhook secret
UPTIME_KUMA_WEBHOOK_SECRET=

# Tenancy
TENANCY_DEFAULT_TENANT_ID=
```
