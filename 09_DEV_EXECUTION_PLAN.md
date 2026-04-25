# ReviveGuard — Senior Architect's Execution Plan

> **Role context:** Acting as Senior Solution Architect + Software Engineer for WaybackRevive LLC  
> **Business:** waybackrevive.com — website restoration agency, now building ReviveGuard (prevention SaaS)  
> **Evaluation date:** April 25, 2026  
> **Plan covers:** Full build from Phase 0 (infrastructure) through Phase 1 (paying clients)

---

## Part 1: Plan Evaluation (Existing Docs Review)

### 1.1 What the Existing Docs Get Right ✅

| Strength | Why it matters |
|---|---|
| KISS architecture (single VPS monolith) | Right call for 0→50 clients. No Kubernetes, no regrets. |
| Clear IN/OUT scope in feature spec (05) | The #1 thing that prevents scope creep. Never violate it. |
| Dual monitoring (Uptime Kuma + heartbeat) | Belt and suspenders. Neither alone is enough. |
| Command polling pattern (no websockets) | Agent polls heartbeat → receives commands. Simple, reliable, restartable. |
| Backups go client→B2 (not via your server) | Your server never handles large file transfers. Smart bandwidth design. |
| Filament v3 for admin panel | 90% of admin UI without writing a single frontend component. |
| Existing customer base as primary GTM | The warm email strategy will convert. They already trust WaybackRevive. |
| Business model: $19/$49/$99 tiers | Right price points. Guard plan ($49) will be the volume driver. |
| stancl/tenancy in single-DB mode | Correct choice for Phase 1. Future-proofs for resellers without over-engineering now. |

### 1.2 Gaps / Technical Risks Identified ⚠️

These are issues NOT covered in the current docs. Must address during build.

#### Risk 1: rclone Dependency on Client Servers (HIGH)
- **Issue:** Every client's WordPress server needs `rclone` installed and configured. This is real operational friction.
- **Plan:** During plugin onboarding, check if rclone is present. If not: fall back to direct PHP-based B2 upload using the official [Backblaze B2 PHP SDK](https://github.com/cwhite92/b2-sdk-php) or native `curl` multipart upload. Add rclone installation to onboarding instructions but don't hard-require it.
- **Agent skill handles this:** See `SKILLS/05_WP_PLUGIN.md`

#### Risk 2: WP-CLI Not Available on Client Servers (MEDIUM)
- **Issue:** `run_wp_updates` command requires WP-CLI. Many shared hosts don't have it.
- **Plan:** UpdateHandler checks for WP-CLI first. If missing: use WordPress native auto-update API (`wp_update_plugins()` etc.) as fallback. Report which method was used in command result.
- **Agent skill handles this:** See `SKILLS/05_WP_PLUGIN.md`

#### Risk 3: WP Cron Reliability on Low-Traffic Sites (MEDIUM)
- **Issue:** WP Cron only fires on page requests. Low-traffic sites may miss heartbeat windows.
- **Plan:** Onboarding checklist includes adding a server-side cron: `*/5 * * * * curl -s https://clientsite.com/?doing_wp_cron > /dev/null`. Platform detects missed heartbeats (>15 min) and flags the site.
- **Scheduler handles this:** See `SKILLS/06_MONITORING.md`

#### Risk 4: Agent API Rate Limiting (HIGH - Security)
- **Issue:** No rate limiting mentioned on `/api/v1/agent/*` endpoints. Must protect against token stuffing / DDoS.
- **Plan:** Apply Laravel rate limiting: 60 requests/min per token on heartbeat. Use `RateLimiter` middleware, not third-party. Heartbeat every 5 min = at most 12 requests/hour normally. 60/min gives headroom for retries.
- **API skill handles this:** See `SKILLS/04_AGENT_API.md`

#### Risk 5: stancl/tenancy Migration Order (MEDIUM)
- **Issue:** stancl/tenancy requires central tables (tenants, users) to be in one migration batch, tenant-aware tables in another. Wrong order breaks the package.
- **Plan:** Create migrations in correct order: `0001_create_central_tables` → `0002_create_tenant_aware_tables`. Use the package's `migration:fresh` artisan command pattern.
- **Foundation skill handles this:** See `SKILLS/02_LARAVEL_FOUNDATION.md`

#### Risk 6: Horizon Worker Restart on Deploy (MEDIUM)
- **Issue:** Deploy scripts must terminate and restart Horizon, otherwise workers run old code.
- **Plan:** Deploy script includes `php artisan horizon:terminate`. Supervisor/PM2 auto-restarts it.
- **Infra skill handles this:** See `SKILLS/01_PHASE0_INFRA.md`

#### Risk 7: B2 Backup Retention by Plan (LOW-MEDIUM)
- **Issue:** Monitor=30d, Guard=90d, Shield=180d retention. This needs B2 bucket lifecycle rules set per-prefix, not per bucket.
- **Plan:** B2 bucket prefix structure: `{tenant_id}/{client_id}/{site_id}/`. Apply lifecycle rules per prefix using B2 API during site creation.
- **Backup system handles this:** See `SKILLS/10_REPORTS_BACKUP.md`

#### Risk 8: Portal CSRF + Agent API Token Isolation (HIGH - Security)
- **Issue:** Two auth systems on one Laravel app (session/CSRF for portal, Bearer token for agent). Must not cross-contaminate.
- **Plan:** Use separate middleware groups. Portal routes: `web` middleware (session + CSRF). Agent routes: `api` middleware (stateless, no CSRF). Admin routes: `filament` guard.
- **Foundation skill handles this:** See `SKILLS/02_LARAVEL_FOUNDATION.md`

#### Risk 9: PDF Report Signed URL Expiry (LOW)
- **Issue:** Reports spec says "signed URL, 1-hour expiry." Backblaze B2 pre-signed URLs have minimum 1-second and custom expiry — this is correct, just needs implementation.
- **Plan:** On PDF download request, generate fresh B2 pre-signed URL (1 hour TTL) and redirect. Never store permanent URLs in the database.

#### Risk 10: WhatsApp Cloud API Phone Format (LOW)
- **Issue:** Meta requires E.164 format (`+14155551234`). Must validate on save.
- **Plan:** Validate WhatsApp number on client create/edit: strip non-digits, ensure E.164 format. Store normalized.

---

## Part 2: Architecture Decisions (Confirmed + Locked)

These decisions are made. Do not revisit during Phase 1.

| Decision | Locked Choice | Do Not Use |
|---|---|---|
| Backend | Laravel 11 | Anything else |
| Admin UI | Filament v3 | Custom Blade admin, Nova, Backpack |
| Portal UI | Livewire 3 | React, Vue, Inertia |
| Database | PostgreSQL 16 | MySQL, SQLite |
| Queue | Laravel Horizon + Redis | Database queue, SQS |
| Monitoring | Uptime Kuma (headless, API only) | Custom ping system, Better Uptime |
| Reports | Puppeteer (Node.js microservice) | wkhtmltopdf, TCPDF, dompdf |
| Multi-tenancy | stancl/tenancy v3 (single-DB) | Custom tenant middleware |
| Billing | Stripe + Laravel Cashier | Paddle, Lemon Squeezy (Phase 2 option) |
| Email | Resend | Mailgun, SendGrid, SMTP |
| Alerts | WhatsApp Cloud API (Meta) | Twilio SMS, Telegram |
| Backups | Backblaze B2 + rclone/direct upload | S3, Cloudflare R2 (Phase 2 option) |
| Infrastructure | Single Hetzner CX31 VPS | Multi-server, Docker Swarm, K8s |
| Process manager | PM2 (Node) + Supervisor (PHP workers) | Systemd only, Docker |
| Agent (WP) | Custom PHP Plugin (zero Composer deps) | Any third-party plugin wrapper |
| Agent (HTML) | Bash script + cron | Node agent, Python agent |

---

## Part 3: Ordered Build Sequence

This is the sequence I follow. Each phase builds on the previous. No skipping, no parallelizing features.

```
PHASE 0 — Infrastructure (Week 1)
├── [P0-1]  VPS provisioning + security hardening
├── [P0-2]  Nginx + PHP-FPM + PostgreSQL + Redis + Node.js
├── [P0-3]  Laravel app skeleton + packages installed
├── [P0-4]  Uptime Kuma (headless) + PM2 management
├── [P0-5]  Puppeteer microservice (Express + Chromium)
├── [P0-6]  Backblaze B2 account + bucket + rclone config
├── [P0-7]  SSL (Let's Encrypt) + Nginx virtual hosts
└── [P0-8]  Stripe + Resend + WhatsApp API accounts created

PHASE 1A — Foundation (Week 2)
├── [P1-1]  Database migrations (all tables from 03_DATABASE_SCHEMA.md)
├── [P1-2]  stancl/tenancy configuration (single-DB mode)
├── [P1-3]  Seed: WaybackRevive tenant + 3 plans + admin user
├── [P1-4]  Auth: Filament admin guard + Breeze portal guard (separate)
└── [P1-5]  CI: GitHub Actions deploy script

PHASE 1B — Admin Panel (Week 3)
├── [P1-6]  Filament: Clients resource (CRUD)
├── [P1-7]  Filament: Sites resource (CRUD + agent token generation)
├── [P1-8]  Filament: Uptime Kuma API integration on site create
├── [P1-9]  Filament: Dashboard widgets (site health overview)
└── [P1-10] Filament: Events resource (read-only, paginated)

PHASE 1C — Agent API (Week 4)
├── [P1-11] Middleware: AgentTokenAuth (validate Bearer, inject Site model)
├── [P1-12] Middleware: Rate limiting on agent endpoints
├── [P1-13] POST /api/v1/agent/heartbeat (with command response)
├── [P1-14] POST /api/v1/agent/command-result
├── [P1-15] POST /api/v1/agent/plugin-list
├── [P1-16] POST /api/v1/agent/event
├── [P1-17] Jobs: ProcessHeartbeat, CheckMissedHeartbeats (scheduler)
└── [P1-18] AlertService: down/recovered detection + dispatch

PHASE 1D — WordPress Plugin (Week 5)
├── [P1-19] Plugin scaffold: file structure, headers, init
├── [P1-20] class-api-client.php (HTTP client, retry logic)
├── [P1-21] class-site-info.php (collect WP/PHP/plugin metadata)
├── [P1-22] class-heartbeat.php (WP Cron, send + receive commands)
├── [P1-23] class-command-runner.php (parse + dispatch commands)
├── [P1-24] class-backup-handler.php (tar + B2 upload + checksum)
├── [P1-25] class-update-handler.php (WP-CLI + fallback)
├── [P1-26] class-plugin-inventory.php
├── [P1-27] Admin settings page
└── [P1-28] Package as .zip + test on real WP site

PHASE 1E — Monitoring (Week 6)
├── [P1-29] Uptime Kuma webhook: POST /api/v1/webhooks/uptime-kuma
├── [P1-30] Scheduler: CheckSslExpiry (daily)
├── [P1-31] Scheduler: CheckDomainExpiry (daily, iodev/whois)
└── [P1-32] Scheduler: UpdateUptimeStats (every 6 hrs from Kuma API)

PHASE 1F — Client Portal (Week 7)
├── [P1-33] Portal auth (Breeze, client guard, session)
├── [P1-34] Dashboard Livewire component (60s polling)
├── [P1-35] Events screen (paginated, filter)
├── [P1-36] Reports screen (PDF signed URL download)
├── [P1-37] Backups screen (last 10 entries)
├── [P1-38] Support tickets (form + list)
└── [P1-39] Account settings + Stripe portal link

PHASE 1G — Billing + Notifications + Reports (Week 8)
├── [P1-40] Stripe webhooks: subscription lifecycle
├── [P1-41] BillingService: activate/pause/cancel
├── [P1-42] OnboardClientJob (trigger on subscription.created)
├── [P1-43] Notification emails: all types via Resend
├── [P1-44] WhatsApp alerts: site down + recovered
├── [P1-45] Puppeteer: report HTML template (branded)
├── [P1-46] Scheduler: GenerateMonthlyReports (1st of month)
└── [P1-47] Admin: manual report generate + resend actions

PHASE 1H — Testing + Launch (Weeks 9-10)
├── [P1-48] Full Phase 1 testing checklist (see 08_DEV_ROADMAP.md)
├── [P1-49] Switch Stripe to live mode
├── [P1-50] Launch: warm email to WaybackRevive restored clients
└── [P1-51] Monitor first 3 paying clients manually for 1 week
```

---

## Part 4: File & Folder Structure (Laravel App)

```
reviveguard-app/
├── app/
│   ├── Console/
│   │   └── Commands/            — Any custom artisan commands
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── ClientResource.php
│   │   │   ├── SiteResource.php
│   │   │   ├── EventResource.php
│   │   │   ├── BackupResource.php
│   │   │   └── TicketResource.php
│   │   └── Widgets/
│   │       └── SiteHealthOverview.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Agent/           — Agent API controllers
│   │   │   │   ├── HeartbeatController.php
│   │   │   │   ├── CommandResultController.php
│   │   │   │   ├── PluginListController.php
│   │   │   │   └── EventController.php
│   │   │   ├── Portal/          — Portal Livewire-backing controllers
│   │   │   └── Webhook/
│   │   │       ├── UptimeKumaController.php
│   │   │       └── StripeController.php (managed by Cashier)
│   │   └── Middleware/
│   │       ├── AgentTokenAuth.php
│   │       └── VerifyUptimeKumaWebhook.php
│   ├── Jobs/
│   │   ├── ProcessHeartbeat.php
│   │   ├── CheckMissedHeartbeats.php
│   │   ├── RunBackup.php
│   │   ├── CheckSslExpiry.php
│   │   ├── CheckDomainExpiry.php
│   │   ├── UpdateUptimeStats.php
│   │   ├── GenerateMonthlyReport.php
│   │   ├── SendAlert.php
│   │   └── OnboardClient.php
│   ├── Livewire/
│   │   ├── Portal/
│   │   │   ├── Dashboard.php
│   │   │   ├── EventsList.php
│   │   │   ├── ReportsList.php
│   │   │   ├── BackupsList.php
│   │   │   ├── TicketsList.php
│   │   │   └── AccountSettings.php
│   │   └── Components/
│   │       └── EventDetailModal.php
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php (admin users)
│   │   ├── Client.php
│   │   ├── Plan.php
│   │   ├── Site.php
│   │   ├── Subscription.php
│   │   ├── SiteCommand.php
│   │   ├── PluginSnapshot.php
│   │   ├── Event.php
│   │   ├── Backup.php
│   │   ├── Report.php
│   │   └── Ticket.php
│   └── Services/
│       ├── HeartbeatService.php
│       ├── AlertService.php
│       ├── BackupService.php
│       ├── ReportService.php
│       ├── UpdateService.php
│       ├── BillingService.php
│       ├── UptimeKumaService.php
│       └── NotificationService.php
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_tenants_table.php
│   │   ├── 0001_01_01_000001_create_users_table.php
│   │   ├── 0001_01_01_000002_create_plans_table.php
│   │   ├── 0001_01_01_000003_create_clients_table.php
│   │   ├── 0001_01_01_000004_create_subscriptions_table.php
│   │   ├── 0001_01_01_000005_create_sites_table.php
│   │   ├── 0001_01_01_000006_create_site_commands_table.php
│   │   ├── 0001_01_01_000007_create_plugin_snapshots_table.php
│   │   ├── 0001_01_01_000008_create_events_table.php
│   │   ├── 0001_01_01_000009_create_backups_table.php
│   │   ├── 0001_01_01_000010_create_reports_table.php
│   │   └── 0001_01_01_000011_create_tickets_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── TenantSeeder.php
│       └── PlanSeeder.php
├── resources/
│   ├── views/
│   │   ├── portal/              — Livewire portal views
│   │   ├── emails/              — Blade email templates
│   │   └── reports/
│   │       └── monthly.blade.php — HTML template for PDF
│   └── css/
│       └── portal.css
└── routes/
    ├── api.php                  — Agent API routes (/api/v1/agent/*)
    ├── web.php                  — Portal + webhook routes
    └── webhooks.php             — Stripe/Uptime Kuma webhooks
```

---

## Part 5: Environment Variables Required

All must be configured in `.env` before any component works:

```env
# App
APP_NAME="ReviveGuard"
APP_URL=https://app.reviveguard.com
APP_KEY=                    # php artisan key:generate

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=reviveguard
DB_USERNAME=reviveguard
DB_PASSWORD=                # strong random password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
HORIZON_MASTER_SUPERVISOR_NAME=master

# Stripe
STRIPE_KEY=                 # pk_test_... / pk_live_...
STRIPE_SECRET=              # sk_test_... / sk_live_...
STRIPE_WEBHOOK_SECRET=      # whsec_...

# Resend
RESEND_API_KEY=             # re_...
MAIL_FROM_ADDRESS=notifications@reviveguard.com
MAIL_FROM_NAME=ReviveGuard

# WhatsApp Cloud API (Meta)
WHATSAPP_TOKEN=             # Bearer token from Meta
WHATSAPP_PHONE_NUMBER_ID=   # Meta phone number ID
WHATSAPP_FROM_NUMBER=       # Your WhatsApp business number

# Backblaze B2
B2_KEY_ID=                  # Application key ID
B2_APPLICATION_KEY=         # Application key
B2_BUCKET_NAME=reviveguard-backups
B2_BUCKET_ID=               # Bucket ID from B2

# Uptime Kuma (internal, same VPS)
UPTIME_KUMA_URL=http://127.0.0.1:3001
UPTIME_KUMA_USERNAME=admin
UPTIME_KUMA_PASSWORD=       # Set during Kuma setup
UPTIME_KUMA_WEBHOOK_SECRET= # Random secret for webhook validation

# Puppeteer microservice (internal)
PUPPETEER_URL=http://127.0.0.1:3002

# Portal URL
PORTAL_URL=https://portal.reviveguard.com

# Plans (Stripe Price IDs — set after creating in Stripe dashboard)
PLAN_MONITOR_PRICE_ID=      # price_...
PLAN_GUARD_PRICE_ID=        # price_...
PLAN_SHIELD_PRICE_ID=       # price_...
```

---

## Part 6: Security Checklist (Applied to Every Build Step)

These apply to every component. Check each before marking a step done.

- [ ] **Input validation** on every API endpoint (use Laravel Form Requests)
- [ ] **No raw SQL** — use Eloquent. If raw query needed, use parameterized bindings only
- [ ] **Agent token hashing** — `bcrypt` or `hash('sha256', ...)` — never store plaintext
- [ ] **HMAC signature validation** on backup/update command requests
- [ ] **Rate limiting** on all public-facing API routes
- [ ] **CSRF** on all portal POST routes (included in `web` middleware)
- [ ] **Authorization checks** — clients can only see their own data (tenant scoping via stancl/tenancy)
- [ ] **Signed URLs** for PDF downloads — never permanent public URLs
- [ ] **Sensitive data in .env only** — no hardcoded secrets in code
- [ ] **XSS prevention** — all user-facing output escaped via Blade `{{ }}` (not `{!! !!}`)
- [ ] **Log sanitization** — never log full agent tokens or API keys
- [ ] **Webhook signature validation** — Stripe and Uptime Kuma webhooks verified before processing

---

## Part 7: Definition of Done (Per Component)

Before marking any build step as complete:

1. **Feature works** — the happy path functions correctly
2. **Error paths handled** — failures return meaningful errors, not 500s
3. **Security check passed** — all items from Part 6 verified
4. **Scope respected** — nothing in the OUT list was built
5. **No debug code** — no `dd()`, `dump()`, `console.log()` left in
6. **No regressions** — previously working features still work

---

## Part 8: How to Use the SKILLS Files

When starting any build component, load the corresponding skill file first:

| Component | Load this skill file |
|---|---|
| VPS setup + dependencies | `SKILLS/01_PHASE0_INFRA.md` |
| Laravel app skeleton, DB, migrations | `SKILLS/02_LARAVEL_FOUNDATION.md` |
| Filament admin panel | `SKILLS/03_ADMIN_PANEL.md` |
| Agent API endpoints | `SKILLS/04_AGENT_API.md` |
| WordPress plugin | `SKILLS/05_WP_PLUGIN.md` |
| Monitoring (SSL/domain/uptime) | `SKILLS/06_MONITORING.md` |
| Client portal (Livewire) | `SKILLS/07_CLIENT_PORTAL.md` |
| Stripe billing | `SKILLS/08_BILLING.md` |
| Notifications (email + WhatsApp) | `SKILLS/09_NOTIFICATIONS.md` |
| Reports + backup system | `SKILLS/10_REPORTS_BACKUP.md` |

Each skill file contains: exact scope, file structure, naming conventions, security requirements, and definition of done for that component. **Always read the skill before touching that component's code.**

---

## Part 9: Global Coding Conventions (Non-Negotiable)

### PHP / Laravel
- PHP 8.3+ features allowed: enums, readonly properties, match expressions
- No `array()` syntax — always `[]`
- Type declarations on all method signatures
- `final` classes for Services (prevents accidental extension)
- Constants for status strings: never hardcode `'up'`, `'down'` inline — use `SiteStatus::UP`
- Models use UUIDs (`$incrementing = false`, `$keyType = 'string'`)
- Enums for status fields: `SiteStatus`, `EventSeverity`, `CommandType`, `BackupStatus`

### Database
- All migrations are reversible (have `down()` methods)
- All foreign keys have explicit cascade rules
- All text user inputs use `VARCHAR` with appropriate limits, not `TEXT` for searchable fields
- All timestamps use timezone-aware `TIMESTAMP WITH TIME ZONE`
- UUID primary keys everywhere except `subscriptions` (Cashier uses bigint)

### Jobs / Queue
- All jobs implement `ShouldBeUnique` where duplicate runs would cause problems
- All jobs have `$tries = 3` and `$backoff = [60, 300, 900]` (1min, 5min, 15min)
- Critical jobs (alerts) go on `critical` queue; everything else on `default`
- Jobs never contain business logic — they call Service classes

### Naming
- Controllers: `{Action}{Entity}Controller` or standard resource controllers
- Jobs: verb + noun — `ProcessHeartbeat`, `CheckSslExpiry`, `GenerateMonthlyReport`
- Services: noun + Service — `HeartbeatService`, `AlertService`
- Middleware: descriptive — `AgentTokenAuth`, `VerifyUptimeKumaWebhook`
- Events (DB table): use slug format — `'site_down'`, `'backup_success'`, `'ssl_expiry_warning'`
