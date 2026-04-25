# ReviveGuard — System Architecture

---

## 1. Architecture Philosophy

**KISS rule applied here:** One VPS. One Laravel app. One database. Everything else plugs into it via API or runs as a sidecar process on the same machine. No Kubernetes, no microservices mesh, no distributed tracing. That complexity is for companies with 10,000 clients. You have 10.

The system grows with you — the architecture below scales to 500+ clients without structural change, just a bigger VPS or split of services.

---

## 2. High-Level Component Map

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT SITES                              │
│                                                                  │
│  ┌──────────────────┐          ┌──────────────────────────────┐ │
│  │  WordPress Site  │          │  HTML / Static Site          │ │
│  │  ReviveGuard     │          │  reviveguard-agent.sh        │ │
│  │  Plugin (PHP)    │          │  (bash + cron)               │ │
│  └────────┬─────────┘          └──────────────┬───────────────┘ │
│           │ HTTPS POST (signed)                │ HTTPS POST      │
└───────────┼───────────────────────────────────┼─────────────────┘
            │                                   │
            ▼                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                    YOUR VPS (Hetzner CX31)                       │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              NGINX (reverse proxy + SSL)                 │    │
│  │   app.reviveguard.com  /  portal.reviveguard.com        │    │
│  └──────────┬──────────────────────────────────────────────┘    │
│             │                                                    │
│  ┌──────────▼──────────────────────────────────────────────┐    │
│  │              LARAVEL 11 APPLICATION                      │    │
│  │                                                          │    │
│  │  ┌──────────────┐  ┌──────────────┐  ┌───────────────┐ │    │
│  │  │  Admin Panel  │  │  REST API    │  │ Client Portal │ │    │
│  │  │  (Filament)  │  │  (Agent +    │  │ (Livewire)    │ │    │
│  │  │              │  │   Portal)    │  │               │ │    │
│  │  └──────────────┘  └──────┬───────┘  └───────────────┘ │    │
│  │                           │                             │    │
│  │  ┌────────────────────────▼────────────────────────┐   │    │
│  │  │              CORE SERVICES (PHP classes)         │   │    │
│  │  │  HeartbeatService  │  AlertService               │   │    │
│  │  │  BackupService     │  ReportService              │   │    │
│  │  │  UpdateService     │  BillingService             │   │    │
│  │  └────────────────────────┬────────────────────────┘   │    │
│  │                           │                             │    │
│  │  ┌────────────────────────▼────────────────────────┐   │    │
│  │  │              JOB QUEUE (Laravel Horizon)         │   │    │
│  │  │  ProcessHeartbeat  │  RunBackup                  │   │    │
│  │  │  CheckSsl          │  GenerateReport             │   │    │
│  │  │  CheckDomain       │  SendAlert                  │   │    │
│  │  └─────────────────────────────────────────────────┘   │    │
│  └──────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │  PostgreSQL  │  │  Redis       │  │  Uptime Kuma         │  │
│  │  (main DB)   │  │  (queue +    │  │  (headless, API-only)│  │
│  │              │  │   cache)     │  │  :3001               │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Puppeteer microservice (Node.js)  :3002                 │   │
│  │  Receives: HTML template + data                          │   │
│  │  Returns: PDF binary                                     │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
            │                    │                    │
            ▼                    ▼                    ▼
    ┌──────────────┐    ┌──────────────┐    ┌──────────────────┐
    │  Backblaze   │    │  Resend      │    │  WhatsApp Cloud  │
    │  B2 (backups)│    │  (email)     │    │  API (alerts)    │
    └──────────────┘    └──────────────┘    └──────────────────┘
            │
            ▼
    ┌──────────────┐
    │  Stripe      │
    │  (billing)   │
    └──────────────┘
```

---

## 3. Component Descriptions

### 3.1 Laravel Application (Core)

The monolith. Everything lives here in Phase 1. Split only when pain forces you to.

**Sub-components:**

**Admin Panel (Filament v3)**
- Internal use only — you and your team
- Manage all clients, sites, plans, alerts, backups
- Override any automation manually
- View all events across all sites

**REST API**
- Receives heartbeats and data from agents
- Serves data to client portal (Livewire uses server-side rendering but API pattern for agents)
- Handles Stripe webhooks

**Client Portal (Livewire)**
- What clients log into
- Shows their specific sites only
- Real-time feel via Livewire polling
- No separate Vue/React app — same Laravel app, different routes

**Core Services**
- `HeartbeatService` — processes incoming agent pings, updates `last_seen_at`, marks site up/down
- `BackupService` — triggers agent backup, receives backup file URL, logs to DB
- `AlertService` — decides who to notify, when, via what channel
- `ReportService` — assembles data for monthly reports, calls Puppeteer
- `UpdateService` — sends update command to agent, tracks result
- `BillingService` — wraps Cashier, handles plan changes, trial logic

**Job Queue (Laravel Horizon)**
- All async work goes through jobs — never block a web request
- Scheduled jobs (via Laravel Scheduler): SSL check daily, domain check daily, backup trigger weekly/daily, report generation monthly
- Priority queues: `critical` (downtime alerts) > `default` (reports, backups)

---

### 3.2 WordPress Agent Plugin

Custom PHP plugin installed on client's WordPress site. This is your moat.

**What it does:**
- Sends heartbeat to your API every 5 minutes (configurable)
- Responds to commands from your platform (run backup, run update, get plugin list)
- Executes WP-CLI commands for updates
- Sends backup to Backblaze B2 directly (client VPS to B2 — your server never touches the backup file)
- Reports: PHP version, WP version, plugins list, active theme, disk usage

**Authentication:** Per-site signed API token (HMAC-SHA256). Generated when you add a site in admin panel. Never changes unless manually rotated.

**Full spec:** See `06_AGENT_PLUGIN_SPEC.md`

---

### 3.3 HTML Site Agent (Shell Script)

For non-WordPress sites (static HTML, basic PHP sites).

**What it does:**
- Cron job every 5 minutes: `curl` POST to your heartbeat endpoint
- Monthly backup: `tar.gz` site files, `rclone` upload to Backblaze B2
- Cannot do updates (static sites don't need them)
- Cannot do plugin scans (not applicable)

**Deployment:** You manually SSH into client's server, drop the script, add cron entry. 10 minutes per client.

---

### 3.4 Uptime Kuma (Headless)

Runs on your VPS at port 3001. Never exposed to clients.

**What you use it for:**
- External HTTP monitoring (separate from agent heartbeat — belt and suspenders)
- If both your agent heartbeat AND Uptime Kuma see downtime, it's definitely down
- SSL certificate check (Uptime Kuma has native SSL monitoring)

**Integration:** Laravel calls Uptime Kuma REST API to add/remove monitors when you add/remove sites. Your portal shows uptime % data fetched from Uptime Kuma API, displayed in your own UI.

---

### 3.5 Puppeteer Microservice

Tiny Node.js app running on same VPS at port 3002.

**Purpose:** Convert HTML to PDF for monthly reports.

**How it works:**
1. Laravel `ReportService` assembles report data
2. Renders a Blade view to HTML string
3. POSTs HTML string to `localhost:3002/render`
4. Puppeteer renders in headless Chrome, returns PDF binary
5. Laravel stores PDF on Backblaze B2, saves URL to DB
6. Email sent to client with PDF attached or link

**Why not wkhtmltopdf?** Puppeteer renders exactly like Chrome — CSS, fonts, flexbox all work perfectly. wkhtmltopdf uses old WebKit and requires fighting CSS to get decent output.

---

### 3.6 Backblaze B2 (Backup Storage)

Why B2 over S3:
- $0.006/GB/month (vs S3 $0.023/GB)
- Free egress to Cloudflare (if you ever put Cloudflare in front)
- S3-compatible API — any tool that works with S3 works with B2

**Bucket structure:**
```
reviveguard-backups/
├── tenant_1/
│   ├── client_1/
│   │   ├── site_1/
│   │   │   ├── 2025-04-01_full.tar.gz
│   │   │   └── 2025-04-08_full.tar.gz
```

**Retention:** Managed via B2 lifecycle rules. Monitor: 30 days. Guard: 90 days. Shield: 180 days.

---

## 4. Data Flow — Key Scenarios

### 4.1 Agent Heartbeat (every 5 min)
```
Client WP Plugin → POST /api/v1/heartbeat (signed token)
    → Laravel validates token → finds Site record
    → Updates Site.last_seen_at, Site.status = 'up'
    → If previously 'down': dispatch AlertJob (site recovered)
    → Job dispatched: ProcessHeartbeat (async, logs event)
```

### 4.2 Site Goes Down (detected two ways)
```
WAY 1 — Missed heartbeat:
    Laravel Scheduler (every 5 min) runs CheckMissedHeartbeats job
    → Finds sites where last_seen_at > 6 minutes ago
    → Sets Site.status = 'down'
    → Dispatches SendAlertJob (critical queue)

WAY 2 — Uptime Kuma webhook:
    Uptime Kuma configured with webhook → POST /api/v1/webhooks/uptime-kuma
    → Laravel receives, cross-references with DB
    → Dispatches SendAlertJob if not already dispatched
```

### 4.3 Monthly Report Generation
```
Laravel Scheduler (1st of each month, 09:00 client timezone):
    → Dispatch GenerateReportJob for each active site
    → Job: fetch month's events, uptime %, updates done, backups verified
    → POST to Puppeteer: localhost:3002/render (HTML template + data)
    → Receive PDF binary
    → Upload to B2: reviveguard-backups/tenant/client/site/reports/2025-04.pdf
    → Save PDF URL to reports table
    → Dispatch SendReportEmailJob
    → Client receives email with PDF attached
```

### 4.4 Stripe Subscription Created
```
Client completes checkout on portal/pricing page
    → Stripe webhook: POST /api/v1/webhooks/stripe
    → Event: customer.subscription.created
    → Laravel Cashier handles subscription record
    → BillingService: activate plan for client
    → Create Site record if not exists
    → Dispatch OnboardClientJob:
        - Add site to Uptime Kuma via API
        - Send welcome email (Resend)
        - Send onboarding instructions for agent installation
```

---

## 5. Infrastructure

### 5.1 VPS Specification

**Provider:** Hetzner Cloud (Frankfurt or US region per client preference)
**Tier for MVP:** CX31 — 2 vCPU, 8GB RAM, 80GB SSD — €12/month

**Process map on VPS:**
| Process | Port | Managed by |
|---|---|---|
| Nginx | 80, 443 | systemd |
| PHP-FPM (Laravel) | socket | systemd |
| Laravel Horizon | — | Supervisor |
| Laravel Scheduler | — | System cron (1-min interval) |
| PostgreSQL | 5432 | systemd |
| Redis | 6379 | systemd |
| Uptime Kuma | 3001 (internal) | PM2 |
| Puppeteer service | 3002 (internal) | PM2 |

Uptime Kuma and Puppeteer are internal only — Nginx does NOT proxy them externally.

### 5.2 SSL

- All domains: Let's Encrypt via Certbot
- Auto-renewal via Certbot systemd timer

### 5.3 Backup of YOUR Server

Self-backup: Hetzner daily snapshot (€1.68/month). Also: daily pg_dump to B2 in a separate `reviveguard-system` bucket.

---

## 6. Security

| Concern | Solution |
|---|---|
| Agent authentication | Per-site HMAC-SHA256 signed tokens — rotation possible via admin panel |
| API rate limiting | Laravel throttle middleware on all agent endpoints (60 req/min per token) |
| Admin panel access | 2FA via Laravel Fortify, IP allowlist optional |
| Client portal auth | Laravel Breeze + email verification |
| Database | No public port — PostgreSQL only accessible locally |
| Stripe webhooks | Webhook signature validation (Stripe-Signature header) |
| Backup files | B2 bucket is private — files accessed via signed URLs (1 hour expiry) |
| Dependency updates | Dependabot on your GitHub repo |

---

## 7. Scalability Path

The MVP runs on one server. Here's how you grow without rewriting:

| Threshold | Action |
|---|---|
| 100 clients | Upgrade to Hetzner CX41 (4 vCPU, 16GB) — €22/mo |
| 200 clients | Move PostgreSQL to managed DB (Supabase or Neon) |
| 300 clients | Move backups processing to separate worker VPS |
| 500 clients | Split Puppeteer to dedicated microservice with its own scaling |
| 1000+ clients | Multi-tenant re-evaluation, CDN for portal, dedicated Redis cluster |

None of these require architectural changes — just resource allocation changes.
