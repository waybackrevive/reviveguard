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
    │  Whop        │
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
- **Evaluation queue:** Review new-client evaluation requests, send proposals, accept/reject
- Override any automation manually
- View all events across all sites

**REST API**
- Receives heartbeats and data from agents
- Serves data to client portal (Livewire uses server-side rendering but API pattern for agents)
- Handles Whop webhooks

**Client Portal (Livewire) — Self-Serve**
- What clients log into — fully self-serve, client-owned dashboard
- **Site onboarding wizard:** Domain → Package selection → Order confirmation (3 steps)
- **Plan management:** Client can upgrade/downgrade plan, enable/disable add-ons at any time
- **Invoice history:** View all invoices, payment status, download PDFs
- **Support tickets:** Submit and track tickets from portal
- Shows live site health: uptime, SSL, domain, last backup, recent activity
- No separate Vue/React app — same Laravel app, different routes

**Core Services**
- `HeartbeatService` — processes incoming agent pings, updates `last_seen_at`, marks site up/down
- `BackupService` — triggers agent backup, receives backup file URL, logs to DB
- `AlertService` — decides who to notify, when, via what channel
- `ReportService` — assembles data for monthly reports, calls Puppeteer
- `UpdateService` — sends update command to agent, tracks result
- `BillingService` — wraps Cashier, handles plan changes, add-on billing, trial logic
- **`EvaluationService`** — manages new-client evaluation lifecycle: receive → review → propose → accept/reject

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

### 4.4 New Client Evaluation Flow
```
Prospect submits evaluation form on marketing site:
    → POST /evaluations/submit (public, rate-limited)
    → Creates site_evaluations record (status: pending)
    → Monthly cap check: if count(approved this month) >= 26 → add to waitlist
    → Admin panel shows new evaluation in Filament queue
    → Team reviews within 48h:
        - Check site URL: CMS, WP version, plugin count, hosting
        - Add internal notes and recommended plan
    Admin decision:
        IF "Send Proposal":
            → Admin creates client_invites record (path='evaluation', evaluation_id=...)
            → System generates random_bytes(32) plain token
            → System stores SHA-256 hash of token in client_invites.token_hash
            → EvaluationService sends "Site Evaluation Report" proposal email:
                - What we found (issues, risks, tech stack summary)
                - Recommended plan with justification
                - Pricing
                - "Accept proposal" button = app.reviveguard.com/portal/accept-invite?token=PLAIN
            → Prospect clicks Accept:
                - Token hashed, matched against DB record
                - Validated: not expired (72h TTL), not already used
                - Client record created (source='inbound', path='evaluation')
                - invite.accepted_at = NOW(), invite.client_id = new client
                - Logged into portal automatically
                - Onboarding wizard shown (same as Path A)
        IF "Decline":
            → Polite rejection email sent
            → 7-day follow-up task created
    → Prospect ignores invite link:
        → invite.expires_at passes → token invalid
        → Auto follow-up email after 7 days
        → Evaluation marked expired after 14 days
```

### 4.4b Alumni Invite (Path A — WaybackRevive Restored Clients)
```
Admin bulk-imports WaybackRevive restored clients (CSV or manual entry):
    → Creates client_invites records (path='alumni', pre-seeded with name/email/site_url)
    → Admin selects batch and clicks "Send Invites"
    → For each invite:
        - System generates random_bytes(32) plain token
        - Stores SHA-256 hash in client_invites.token_hash
        - Sends personalised outreach email:
              "Your site was restored by us — want to make sure it never goes down again?"
              CTA button = app.reviveguard.com/portal/accept-invite?token=PLAIN
    → Client clicks link:
        - Token validated (hash match, not expired, not used)
        - Client record created (source='waybackrevive_restored', path='alumni')
        - Wizard pre-filled with their site URL from invite record
        - Logged in automatically
        - 3-step wizard: confirm site → choose plan → go to Whop checkout

Security guarantee: No client can self-identify as alumni.
The only way to be treated as Path A is to hold a valid admin-generated token.
```

### 4.5 Self-Serve Plan Change
```
Client upgrades plan in portal:
    → Livewire component: PlanSelector
    → On confirm: POST to /portal/subscription/change
    → BillingService calls Whop API to swap plan (membership upgrade/downgrade)
    → Whop fires membership.went_valid with new plan data
    → WhopBillingService updates subscription record
    → Email sent: "Your plan has been updated to Shield"
    → Portal reflects new plan immediately
```

### 4.6 Client Self-Adds a Site (Onboarding Wizard)
```
Client clicks "+Add website" in portal (already authenticated via invite token):
    → Step 1: Enter domain name + company name
    → Step 2: Choose plan (pre-selected based on recommendation, or client picks)
    → Step 3: Order summary → "Subscribe" button → redirect to Whop hosted checkout
    → On Whop success:
        → Webhook: membership.went_valid (POST /api/v1/webhooks/whop)
        → BillingService matches webhook to pending client by whop_member_id
        → Subscription record created / updated
        → Site record created
        → Agent token generated
        → Agent installation instructions emailed
        → Uptime Kuma monitor created
        → Dashboard immediately shows "Awaiting first heartbeat"
```

### 4.7 Whop Membership Activated (billing webhook flow)
```
Whop fires membership.went_valid:
    → POST /api/v1/webhooks/whop (signature verified via Whop-Signature header)
    → WhopBillingService:
        - Find client by whop_member_id
        - Set subscription status → 'active'
        - If new membership: dispatch OnboardClientJob
            - Add site to Uptime Kuma via API
            - Send welcome email (Resend) with invite/activation link
            - Send agent installation instructions
        - If reactivation: restore suspended client

Whop fires membership.went_invalid or membership.was_banned:
    → Suspend client (sites paused, no new backups/updates)
    → Send "payment issue" notification email
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
| Whop webhooks | Webhook signature validation (Whop-Signature header) |
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
