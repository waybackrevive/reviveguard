# ReviveGuard — Database Schema

---

## Multi-Tenancy Model

**Approach:** Single database, shared tables, `tenant_id` column on every relevant table.

**Why not separate databases per tenant?**
- MVP has 10 clients. Separate DB per tenant adds ops complexity with zero benefit at this scale.
- `stancl/tenancy` package handles the `tenant_id` scoping automatically via global scopes.
- Migrate to DB-per-tenant only if a large enterprise client requires contractual data isolation.

**Package:** `stancl/tenancy` v3 — configured in "single database" mode.

---

## Table Definitions

---

### `tenants`
The top-level account. In Phase 1, only one tenant exists: WaybackRevive. Phase 2 adds reseller tenants.

```sql
CREATE TABLE tenants (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(255) NOT NULL,              -- "WaybackRevive"
    slug            VARCHAR(100) NOT NULL UNIQUE,       -- "waybackrevive"
    domain          VARCHAR(255),                       -- "app.reviveguard.com" or custom
    logo_url        VARCHAR(500),
    primary_color   VARCHAR(7) DEFAULT '#1a1a2e',       -- hex, for portal branding
    settings        JSONB DEFAULT '{}',                 -- flexible config store
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

---

### `users`
Your team members (internal). Different from clients.

```sql
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            VARCHAR(50) NOT NULL DEFAULT 'agent',
                    -- roles: 'owner', 'admin', 'agent'
    two_factor_secret   VARCHAR(255),
    two_factor_recovery_codes TEXT,
    email_verified_at   TIMESTAMP WITH TIME ZONE,
    remember_token  VARCHAR(100),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_users_tenant ON users(tenant_id);
CREATE INDEX idx_users_email ON users(email);
```

---

### `clients`
End customers who pay for maintenance plans. One client can have multiple sites.

```sql
CREATE TABLE clients (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    phone           VARCHAR(50),
    whatsapp_number VARCHAR(50),                        -- e.g. "+14155551234"
    company         VARCHAR(255),
    country         VARCHAR(100),
    timezone        VARCHAR(100) DEFAULT 'UTC',
    notes           TEXT,                               -- internal notes
    portal_password VARCHAR(255),                       -- hashed
    portal_last_login   TIMESTAMP WITH TIME ZONE,
    -- Stripe
    stripe_customer_id  VARCHAR(255) UNIQUE,
    -- Status
    status          VARCHAR(50) DEFAULT 'active',
                    -- 'active', 'suspended', 'churned'
    source          VARCHAR(100),
                    -- 'waybackrevive_restored', 'inbound', 'referral'
    referrer_client_id  UUID REFERENCES clients(id),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_clients_tenant ON clients(tenant_id);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_clients_stripe ON clients(stripe_customer_id);
```

---

### `plans`
Subscription plan definitions. Seeded, not created dynamically.

```sql
CREATE TABLE plans (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    name            VARCHAR(100) NOT NULL,              -- "Monitor", "Guard", "Shield"
    slug            VARCHAR(100) NOT NULL,              -- "monitor", "guard", "shield"
    price_monthly   DECIMAL(10,2) NOT NULL,             -- 19.00, 49.00, 99.00
    price_annually  DECIMAL(10,2),                      -- optional annual price
    stripe_price_id_monthly VARCHAR(255),               -- Stripe Price ID
    stripe_price_id_annually VARCHAR(255),
    features        JSONB NOT NULL DEFAULT '{}',
                    -- {"uptime_check": true, "wp_updates": false, "backup_days": 30, ...}
    max_sites       INT DEFAULT 1,
    is_active       BOOLEAN DEFAULT true,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_plans_tenant ON plans(tenant_id);
```

**Features JSONB structure example:**
```json
{
    "uptime_monitoring": true,
    "ssl_monitoring": true,
    "domain_monitoring": true,
    "backup_frequency": "weekly",
    "backup_retention_days": 90,
    "wp_core_updates": true,
    "wp_plugin_updates": true,
    "malware_scanning": true,
    "broken_link_check": true,
    "content_edits_hours": 0,
    "support_tickets_per_month": 1,
    "report_frequency": "monthly",
    "emergency_restore_sla_hours": null,
    "priority_support": false
}
```

---

### `subscriptions`
Managed by Laravel Cashier — this is Cashier's default table with minor additions.

```sql
CREATE TABLE subscriptions (
    id              BIGSERIAL PRIMARY KEY,
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    client_id       UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    plan_id         UUID REFERENCES plans(id),
    name            VARCHAR(255) NOT NULL DEFAULT 'default',
    stripe_id       VARCHAR(255) NOT NULL UNIQUE,
    stripe_status   VARCHAR(255) NOT NULL,
    stripe_price    VARCHAR(255),
    quantity        INT,
    trial_ends_at   TIMESTAMP WITH TIME ZONE,
    ends_at         TIMESTAMP WITH TIME ZONE,
    billing_cycle   VARCHAR(20) DEFAULT 'monthly',      -- 'monthly', 'annually'
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_subscriptions_client ON subscriptions(client_id);
CREATE INDEX idx_subscriptions_stripe ON subscriptions(stripe_id);
```

---

### `sites`
Individual websites being monitored. Core entity.

```sql
CREATE TABLE sites (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    client_id       UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    plan_id         UUID REFERENCES plans(id),
    subscription_id BIGINT REFERENCES subscriptions(id),

    -- Identification
    name            VARCHAR(255) NOT NULL,              -- "John's Bakery Website"
    url             VARCHAR(500) NOT NULL,              -- "https://johnsbakery.com"
    domain          VARCHAR(255) NOT NULL,              -- "johnsbakery.com"

    -- Type
    site_type       VARCHAR(50) DEFAULT 'wordpress',
                    -- 'wordpress', 'html', 'other'

    -- Agent
    agent_token     VARCHAR(255) UNIQUE,                -- HMAC secret key
    agent_version   VARCHAR(50),                        -- plugin version installed
    agent_installed_at  TIMESTAMP WITH TIME ZONE,

    -- Status
    status          VARCHAR(50) DEFAULT 'pending',
                    -- 'pending', 'active', 'down', 'paused', 'cancelled'
    last_seen_at    TIMESTAMP WITH TIME ZONE,
    downtime_since  TIMESTAMP WITH TIME ZONE,           -- set when status → 'down'

    -- Site metadata (updated by agent)
    php_version     VARCHAR(20),
    wp_version      VARCHAR(20),
    wp_debug_enabled    BOOLEAN,
    plugin_count    INT,
    theme_name      VARCHAR(255),
    disk_usage_mb   INT,
    site_metadata   JSONB DEFAULT '{}',                 -- anything else agent sends

    -- External monitoring
    uptime_kuma_monitor_id  INT,                        -- ID from Uptime Kuma API

    -- SSL
    ssl_expires_at  DATE,
    ssl_issuer      VARCHAR(255),
    ssl_last_checked    TIMESTAMP WITH TIME ZONE,

    -- Domain
    domain_expires_at   DATE,
    domain_registrar    VARCHAR(255),
    domain_last_checked TIMESTAMP WITH TIME ZONE,

    -- Backup
    last_backup_at  TIMESTAMP WITH TIME ZONE,
    last_backup_size_mb INT,
    last_backup_status  VARCHAR(50),                    -- 'success', 'failed', 'running'
    last_backup_url VARCHAR(1000),                      -- B2 signed URL (short-lived)

    -- Uptime stats cache (updated by scheduled job)
    uptime_30d      DECIMAL(5,2),                       -- e.g. 99.97
    uptime_7d       DECIMAL(5,2),

    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_sites_tenant ON sites(tenant_id);
CREATE INDEX idx_sites_client ON sites(client_id);
CREATE INDEX idx_sites_status ON sites(status);
CREATE INDEX idx_sites_last_seen ON sites(last_seen_at);
CREATE INDEX idx_sites_domain ON sites(domain);
```

---

### `events`
Immutable log of everything that happens to a site. Never update, only insert.

```sql
CREATE TABLE events (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    site_id         UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,

    type            VARCHAR(100) NOT NULL,
                    -- 'site_down', 'site_recovered', 'backup_success', 'backup_failed',
                    -- 'wp_update_success', 'wp_update_failed', 'ssl_expiry_warning',
                    -- 'domain_expiry_warning', 'malware_found', 'malware_clean',
                    -- 'heartbeat_received', 'plugin_update_success', 'plugin_update_failed'

    severity        VARCHAR(20) DEFAULT 'info',
                    -- 'critical', 'warning', 'info', 'success'

    title           VARCHAR(255) NOT NULL,              -- human-readable summary
    description     TEXT,                               -- detail
    metadata        JSONB DEFAULT '{}',                 -- type-specific data

    -- Was a notification sent?
    notified_at     TIMESTAMP WITH TIME ZONE,
    notification_channels   JSONB DEFAULT '[]',         -- ["email", "whatsapp"]

    occurred_at     TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_events_site ON events(site_id);
CREATE INDEX idx_events_type ON events(type);
CREATE INDEX idx_events_severity ON events(severity);
CREATE INDEX idx_events_occurred ON events(occurred_at DESC);
CREATE INDEX idx_events_tenant ON events(tenant_id);
```

---

### `backups`
Detailed backup records. Each row = one backup file.

```sql
CREATE TABLE backups (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    site_id         UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,

    type            VARCHAR(50) DEFAULT 'full',         -- 'full', 'database', 'files'
    status          VARCHAR(50) NOT NULL,               -- 'running', 'success', 'failed'

    -- Storage
    storage_path    VARCHAR(1000),                      -- B2 object key
    file_size_mb    INT,
    checksum        VARCHAR(255),                       -- SHA256 of backup file

    -- Timing
    started_at      TIMESTAMP WITH TIME ZONE,
    completed_at    TIMESTAMP WITH TIME ZONE,
    duration_seconds    INT,

    -- Retention
    expires_at      DATE NOT NULL,                      -- when B2 lifecycle deletes it
    is_manual       BOOLEAN DEFAULT false,              -- true if triggered manually

    error_message   TEXT,                               -- if status = 'failed'
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_backups_site ON backups(site_id);
CREATE INDEX idx_backups_status ON backups(status);
CREATE INDEX idx_backups_created ON backups(created_at DESC);
```

---

### `reports`
Monthly generated reports.

```sql
CREATE TABLE reports (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    site_id         UUID NOT NULL REFERENCES sites(id) ON DELETE CASCADE,

    period_start    DATE NOT NULL,                      -- first day of reported month
    period_end      DATE NOT NULL,                      -- last day of reported month

    -- Content snapshot (data at time of generation)
    uptime_percent  DECIMAL(5,2),
    total_events    INT DEFAULT 0,
    downtime_events INT DEFAULT 0,
    updates_applied INT DEFAULT 0,
    backups_verified INT DEFAULT 0,
    ssl_days_remaining  INT,
    domain_days_remaining   INT,
    report_data     JSONB NOT NULL DEFAULT '{}',        -- full data snapshot for re-rendering

    -- File
    pdf_url         VARCHAR(1000),                      -- B2 signed URL (long-lived, 1yr)
    pdf_size_bytes  INT,

    -- Delivery
    generated_at    TIMESTAMP WITH TIME ZONE,
    emailed_at      TIMESTAMP WITH TIME ZONE,

    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_reports_site ON reports(site_id);
CREATE INDEX idx_reports_period ON reports(period_start DESC);
```

---

### `support_tickets`
Simple support request system. Not a full helpdesk — just enough for Phase 1.

```sql
CREATE TABLE support_tickets (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    client_id       UUID NOT NULL REFERENCES clients(id),
    site_id         UUID REFERENCES sites(id),          -- optional, ticket may not be site-specific

    subject         VARCHAR(500) NOT NULL,
    description     TEXT NOT NULL,
    status          VARCHAR(50) DEFAULT 'open',
                    -- 'open', 'in_progress', 'resolved', 'closed'
    priority        VARCHAR(20) DEFAULT 'normal',
                    -- 'low', 'normal', 'high', 'urgent'

    -- Assignment
    assigned_to     UUID REFERENCES users(id),

    -- Resolution
    resolved_at     TIMESTAMP WITH TIME ZONE,
    resolution_note TEXT,

    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_tickets_client ON support_tickets(client_id);
CREATE INDEX idx_tickets_status ON support_tickets(status);
```

---

### `notifications_log`
Record of every notification sent. For debugging and client-facing "alert history."

```sql
CREATE TABLE notifications_log (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    site_id         UUID REFERENCES sites(id),
    event_id        UUID REFERENCES events(id),
    client_id       UUID REFERENCES clients(id),

    channel         VARCHAR(50) NOT NULL,               -- 'email', 'whatsapp'
    recipient       VARCHAR(255) NOT NULL,              -- email address or phone number
    subject         VARCHAR(500),
    body            TEXT,

    status          VARCHAR(50) DEFAULT 'sent',         -- 'sent', 'failed', 'bounced'
    provider_message_id VARCHAR(255),                   -- Resend/Twilio message ID
    error_message   TEXT,

    sent_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_notifications_site ON notifications_log(site_id);
CREATE INDEX idx_notifications_sent ON notifications_log(sent_at DESC);
```

---

## Entity Relationship Summary

```
tenants
    ├── users (team members)
    ├── plans (Monitor, Guard, Shield)
    └── clients
            ├── subscriptions (linked to plans + Stripe)
            └── sites
                    ├── events (immutable log)
                    ├── backups
                    ├── reports
                    └── support_tickets
```

---

## Seed Data

On fresh install, seed these records:

```sql
-- One tenant
INSERT INTO tenants (name, slug) VALUES ('WaybackRevive', 'waybackrevive');

-- Three plans
INSERT INTO plans (tenant_id, name, slug, price_monthly, features, sort_order)
VALUES
  (tenant_id, 'Monitor', 'monitor', 19.00, '{"backup_frequency":"monthly","backup_retention_days":30,"uptime_monitoring":true,"ssl_monitoring":true,"domain_monitoring":true,"wp_core_updates":false,"wp_plugin_updates":false,"malware_scanning":false}', 1),
  (tenant_id, 'Guard',   'guard',   49.00, '{"backup_frequency":"weekly","backup_retention_days":90,"uptime_monitoring":true,"ssl_monitoring":true,"domain_monitoring":true,"wp_core_updates":true,"wp_plugin_updates":true,"malware_scanning":true}', 2),
  (tenant_id, 'Shield',  'shield',  99.00, '{"backup_frequency":"daily","backup_retention_days":180,"uptime_monitoring":true,"ssl_monitoring":true,"domain_monitoring":true,"wp_core_updates":true,"wp_plugin_updates":true,"malware_scanning":true,"content_edits_hours":1,"priority_support":true}', 3);
```
