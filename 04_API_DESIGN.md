# ReviveGuard — API Design

---

## API Philosophy

- **REST, not GraphQL** — KISS rule. REST is predictable, debuggable, and every developer knows it.
- **Versioned from day 1** — all routes prefixed `/api/v1/`. No regrets later.
- **Two API layers:** Agent API (machine-to-machine, token auth) + Portal API (browser, session auth)
- **JSON everywhere** — `Content-Type: application/json` always
- **Fail loudly** — meaningful error codes and messages. No silent failures.

---

## Authentication

### Agent API Authentication
Every request from an agent (WP plugin or shell script) must include:

```
Authorization: Bearer {site_agent_token}
```

The `site_agent_token` is a unique HMAC-SHA256 secret generated per site when added in admin panel. Stored hashed in the `sites.agent_token` column.

**Request signing (additional layer for backup triggers):**
For mutation requests (backup, update), the agent also sends an HMAC signature of the request body:
```
X-ReviveGuard-Signature: sha256={hmac_hex}
```

Laravel middleware validates this before processing.

### Portal / Admin API Authentication
Session-based. Laravel Breeze handles login. CSRF token required for all POST/PUT/DELETE.

### Stripe Webhooks
```
Stripe-Signature: {stripe_signature_header}
```
Validated using `Laravel\Cashier\Http\Middleware\VerifyWebhookSignature`.

---

## Agent API Routes

Base: `/api/v1/agent/`

---

### POST `/api/v1/agent/heartbeat`
Agent sends this every 5 minutes. Most frequent call in the system.

**Auth:** Bearer token (site agent token)

**Request body:**
```json
{
    "timestamp": "2025-04-01T10:00:00Z",
    "site_url": "https://example.com",
    "wp_version": "6.5.2",
    "php_version": "8.2.1",
    "plugin_count": 12,
    "theme_name": "Astra",
    "disk_usage_mb": 2048,
    "debug_mode": false,
    "agent_version": "1.0.3"
}
```

**Response 200:**
```json
{
    "status": "ok",
    "server_time": "2025-04-01T10:00:01Z",
    "commands": []
}
```

**Response with pending command:**
```json
{
    "status": "ok",
    "server_time": "2025-04-01T10:00:01Z",
    "commands": [
        {
            "id": "cmd_abc123",
            "type": "run_backup",
            "params": {
                "backup_type": "full",
                "destination": "b2"
            }
        }
    ]
}
```

The command pattern: your platform queues a command for the site. Next time agent heartbeats, it receives the command, executes it, and reports back. Simple polling — no websockets needed.

---

### POST `/api/v1/agent/command-result`
Agent reports the result of a previously received command.

**Auth:** Bearer token

**Request body:**
```json
{
    "command_id": "cmd_abc123",
    "type": "run_backup",
    "status": "success",
    "result": {
        "file_size_mb": 145,
        "checksum": "sha256:abc...",
        "b2_path": "tenant_1/client_1/site_1/2025-04-01_full.tar.gz",
        "duration_seconds": 42
    },
    "error": null
}
```

**Response 200:**
```json
{ "status": "received" }
```

---

### POST `/api/v1/agent/plugin-list`
Agent sends full plugin inventory. Called after each heartbeat if plugin list has changed.

**Auth:** Bearer token

**Request body:**
```json
{
    "plugins": [
        {
            "slug": "woocommerce",
            "name": "WooCommerce",
            "version": "8.7.0",
            "latest_version": "8.8.0",
            "is_active": true,
            "update_available": true,
            "auto_update_enabled": false
        }
    ]
}
```

**Response 200:**
```json
{ "status": "received", "plugins_logged": 12 }
```

---

### POST `/api/v1/agent/event`
Agent reports a notable event it detected locally.

**Auth:** Bearer token

**Request body:**
```json
{
    "type": "php_error_spike",
    "severity": "warning",
    "title": "PHP error rate increased",
    "description": "140 errors in last hour (threshold: 50)",
    "metadata": { "error_count": 140, "threshold": 50 },
    "occurred_at": "2025-04-01T09:55:00Z"
}
```

**Response 200:**
```json
{ "status": "logged", "event_id": "evt_xyz789" }
```

---

## Webhook API Routes

### POST `/api/v1/webhooks/uptime-kuma`
Uptime Kuma configured to POST here on status change.

**Auth:** Shared secret in header: `X-Webhook-Secret: {secret}`

**Request body (Uptime Kuma format):**
```json
{
    "heartbeat": {
        "status": 0,
        "time": "2025-04-01 10:00:00",
        "msg": "No response"
    },
    "monitor": {
        "id": 42,
        "name": "johnsbakery.com",
        "url": "https://johnsbakery.com"
    }
}
```

Laravel looks up `sites.uptime_kuma_monitor_id = 42`, updates status, dispatches alert if needed.

---

### POST `/api/v1/webhooks/stripe`
Stripe sends all subscription events here.

**Auth:** Stripe-Signature header validation

**Events handled:**
- `customer.subscription.created` → activate client plan
- `customer.subscription.updated` → update plan record
- `customer.subscription.deleted` → pause/cancel site monitoring
- `invoice.payment_succeeded` → log payment, reset monthly support ticket count
- `invoice.payment_failed` → send payment failure email to client

---

## Portal API Routes (Client-Facing)

These are consumed by Livewire components — mostly server-side rendered, but these endpoints also allow future mobile app or external access.

Base: `/portal/api/`

All routes require client session authentication.

---

### GET `/portal/api/sites`
Returns all sites for authenticated client.

**Response 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "name": "My Bakery Website",
            "url": "https://example.com",
            "status": "up",
            "plan": "Guard",
            "uptime_30d": 99.97,
            "last_seen_at": "2025-04-01T10:00:01Z",
            "last_backup_at": "2025-03-31T02:00:00Z",
            "ssl_expires_at": "2025-09-01",
            "ssl_days_remaining": 153,
            "domain_expires_at": "2026-01-15",
            "domain_days_remaining": 289,
            "wp_version": "6.5.2",
            "updates_pending": 2
        }
    ]
}
```

---

### GET `/portal/api/sites/{id}/events`
Recent events for a site, paginated.

**Query params:** `?page=1&per_page=20&type=all&severity=all`

**Response 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "type": "backup_success",
            "severity": "success",
            "title": "Backup completed successfully",
            "description": "Full backup (145 MB) stored securely",
            "occurred_at": "2025-04-01T02:00:00Z"
        }
    ],
    "meta": { "current_page": 1, "total": 47, "per_page": 20 }
}
```

---

### GET `/portal/api/sites/{id}/reports`
List of generated monthly reports.

**Response 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "period": "March 2025",
            "period_start": "2025-03-01",
            "uptime_percent": 100.00,
            "updates_applied": 5,
            "backups_verified": 4,
            "pdf_url": "https://...(signed B2 URL)...",
            "generated_at": "2025-04-01T09:00:00Z"
        }
    ]
}
```

---

### GET `/portal/api/sites/{id}/backups`
Backup history for a site.

**Response 200:**
```json
{
    "data": [
        {
            "id": "uuid",
            "type": "full",
            "status": "success",
            "file_size_mb": 145,
            "created_at": "2025-04-01T02:00:00Z",
            "expires_at": "2025-07-01",
            "is_manual": false
        }
    ]
}
```

---

### POST `/portal/api/tickets`
Client submits a support ticket.

**Request body:**
```json
{
    "site_id": "uuid-optional",
    "subject": "My contact form stopped working",
    "description": "Since yesterday, nobody gets a confirmation email after submitting..."
}
```

**Response 201:**
```json
{
    "id": "uuid",
    "status": "open",
    "created_at": "2025-04-01T10:05:00Z",
    "message": "Ticket received. We'll respond within 24 hours."
}
```

---

## Admin API Routes (Internal — Filament uses these)

Base: `/admin/api/`
Auth: Admin session + 2FA

These power Filament's resource pages. Filament auto-generates most of these — documented here for completeness.

### Clients
- `GET /admin/api/clients` — paginated list with search, filters
- `POST /admin/api/clients` — create new client
- `GET /admin/api/clients/{id}` — client detail with sites
- `PUT /admin/api/clients/{id}` — update client
- `DELETE /admin/api/clients/{id}` — soft delete

### Sites
- `GET /admin/api/sites` — all sites across all clients
- `POST /admin/api/sites` — add new site (triggers: generate agent token, add to Uptime Kuma)
- `GET /admin/api/sites/{id}` — full site detail
- `PUT /admin/api/sites/{id}` — update
- `POST /admin/api/sites/{id}/pause` — pause monitoring
- `POST /admin/api/sites/{id}/resume` — resume monitoring
- `POST /admin/api/sites/{id}/rotate-token` — generate new agent token
- `POST /admin/api/sites/{id}/trigger-backup` — queue immediate backup
- `POST /admin/api/sites/{id}/trigger-update` — queue WP update run

### Reports
- `POST /admin/api/sites/{id}/generate-report` — manually trigger report generation
- `POST /admin/api/reports/{id}/resend` — resend report email to client

---

## Error Response Format

All errors follow this format:

```json
{
    "error": {
        "code": "SITE_NOT_FOUND",
        "message": "No site found for provided token",
        "details": null
    }
}
```

### Standard Error Codes

| HTTP | Code | Meaning |
|---|---|---|
| 400 | `VALIDATION_ERROR` | Request body failed validation |
| 401 | `UNAUTHORIZED` | Missing or invalid token |
| 403 | `FORBIDDEN` | Valid token but insufficient permissions |
| 404 | `SITE_NOT_FOUND` | Agent token doesn't match any site |
| 404 | `CLIENT_NOT_FOUND` | Client not found |
| 409 | `COMMAND_ALREADY_PENDING` | Another command of same type is queued |
| 422 | `PLAN_LIMIT_REACHED` | e.g. submitted 2nd support ticket on Monitor plan |
| 429 | `RATE_LIMITED` | Too many requests |
| 500 | `INTERNAL_ERROR` | Something unexpected broke |

---

## Rate Limits

| Endpoint group | Limit |
|---|---|
| Agent heartbeat | 60 requests/5 minutes per token |
| Agent command-result | 30/minute per token |
| Portal read endpoints | 120/minute per session |
| Portal write endpoints (tickets) | 10/hour per session |
| Admin endpoints | No limit (internal only) |
| Webhook endpoints | No limit (validated by signature) |
