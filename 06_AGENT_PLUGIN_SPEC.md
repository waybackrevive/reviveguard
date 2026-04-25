# ReviveGuard — Agent Specification

---

## Overview

The agent is the bridge between a client's website and your platform. It runs on the client's site, sends data to your API, and executes commands your platform queues for it.

Two agent types:
1. **WordPress Plugin** — for WordPress sites (PHP)
2. **Shell Script** — for HTML/static sites (bash)

Both follow the same API contract. The platform doesn't care which type it's talking to.

---

## Agent Design Principles

1. **Never break the client's site** — the agent is a guest on the client's server. It must fail silently and never throw uncaught exceptions that affect the site.
2. **Minimal permissions** — only the access it needs. Never stores credentials in plaintext.
3. **Idempotent operations** — running the same command twice should be safe (same result, no duplicate records).
4. **Signed communication only** — every request authenticated with the site's unique token.
5. **Small footprint** — plugin should not slow down WP admin. All heavy operations run via WP-CLI or WP Cron, not on page load.

---

## Part A: WordPress Plugin

### Plugin Identity
- **Plugin name:** ReviveGuard Site Agent
- **Plugin slug:** `reviveguard-agent`
- **Requires:** WordPress 5.8+, PHP 7.4+
- **Recommended:** WP-CLI installed on server (required for update commands)

### File Structure
```
reviveguard-agent/
├── reviveguard-agent.php          # Main plugin file (headers, init)
├── includes/
│   ├── class-api-client.php       # Handles all outgoing HTTP requests to your platform
│   ├── class-heartbeat.php        # Heartbeat logic
│   ├── class-command-runner.php   # Receives and executes commands
│   ├── class-backup-handler.php   # Backup creation and B2 upload
│   ├── class-update-handler.php   # WP updates via WP-CLI
│   ├── class-plugin-inventory.php # Collects plugin list
│   └── class-site-info.php        # Collects site metadata
├── admin/
│   ├── class-admin-page.php       # WP admin settings page
│   └── views/
│       └── settings-page.php      # HTML for settings page
├── assets/
│   └── admin.css                  # Minimal styles for settings page
└── README.md
```

### Settings Stored in wp_options
```
reviveguard_agent_token          — the HMAC secret (encrypted at rest using wp_salt)
reviveguard_api_base_url         — your platform URL (default: https://app.reviveguard.com)
reviveguard_last_heartbeat       — timestamp of last successful heartbeat
reviveguard_connection_status    — 'connected', 'error', 'pending'
reviveguard_agent_version        — current plugin version
reviveguard_pending_command      — JSON of command currently being executed (or null)
```

### Heartbeat Mechanism

**How it runs:** WordPress Cron (`wp_schedule_event`) every 5 minutes.

> **Note:** WP Cron runs only when site receives traffic. For low-traffic sites, you add a server cron: `*/5 * * * * curl -s https://clientsite.com/wp-cron.php > /dev/null`. Add this during onboarding.

**Heartbeat flow:**
```
WP Cron fires → HeartbeatClass::run()
    → SiteInfo::collect() → builds payload array
    → ApiClient::post('/api/v1/agent/heartbeat', payload)
    → On success:
        → Update reviveguard_last_heartbeat
        → Update reviveguard_connection_status = 'connected'
        → Check response for commands[]
        → If commands exist: CommandRunner::queue($commands)
    → On failure (HTTP error or timeout):
        → Log to reviveguard debug log (not error_log — don't pollute client's logs)
        → Increment failure counter
        → After 3 consecutive failures: update connection_status = 'error'
        → Do NOT throw exception
```

### Command Execution

Commands are queued and executed on the NEXT background run (not inline with heartbeat response — keep heartbeat fast).

**Supported commands in Phase 1:**

#### `run_backup`
```
CommandRunner receives → schedules as_soon_as_possible via Action Scheduler
BackupHandler::run():
    1. Set reviveguard_pending_command
    2. Create temporary directory: /tmp/reviveguard-backup-{timestamp}/
    3. Export database: wp db export /tmp/.../database.sql (via WP-CLI)
       Fallback if no WP-CLI: use wpdb to dump all tables to SQL file
    4. Create archive: tar -czf backup.tar.gz /tmp/.../ --exclude=./wp-content/cache
    5. Calculate SHA256 checksum of archive
    6. Upload to Backblaze B2 via rclone:
       rclone copy /tmp/backup.tar.gz b2:reviveguard-backups/{tenant}/{client}/{site}/
    7. Verify upload: download first 1024 bytes, confirm file size matches
    8. Clean up /tmp directory
    9. Report result via POST /api/v1/agent/command-result
    10. Clear reviveguard_pending_command
```

**Backup exclusions (hardcoded):**
- `wp-content/cache/`
- `wp-content/upgrade/`
- `.git/`
- `node_modules/`
- `*.log` files
- Temporary files matching `/tmp*/`

#### `run_wp_updates`
```
UpdateHandler::run():
    1. Check WP-CLI available: shell_exec('which wp')
       If not available: report failure with message "WP-CLI not installed"
    2. Update WordPress core: wp core update --allow-root
    3. Record result (version before/after)
    4. Update all plugins: wp plugin update --all --allow-root
    5. Record which plugins were updated (name, old version, new version)
    6. Update all themes: wp theme update --all --allow-root
    7. Record results
    8. Report full result via POST /api/v1/agent/command-result
```

**Safety check before updates:**
- Confirm backup exists from last 24 hours (check reviveguard_last_backup timestamp)
- If no recent backup: run backup first, then updates
- This is not a staging test — it's a direct production update with a safety net

### Plugin Admin Page

Located at: WordPress Dashboard → Settings → ReviveGuard Agent

**What it shows:**
```
┌──────────────────────────────────────────────┐
│  ReviveGuard Site Agent                       │
│                                               │
│  Connection Status:  ● Connected              │
│  Last Heartbeat:     2 minutes ago            │
│  Platform:           app.reviveguard.com      │
│                                               │
│  ─────────────────────────────────────────── │
│  Site Information                             │
│  WordPress Version:  6.5.2                    │
│  PHP Version:        8.2.1                    │
│  Active Plugins:     14                       │
│                                               │
│  ─────────────────────────────────────────── │
│  Settings                                     │
│  API Token:  ••••••••••••••••  [Reveal]       │
│  API URL:    https://app.reviveguard.com      │
│                                               │
│  [Save Settings]  [Send Test Heartbeat]       │
└──────────────────────────────────────────────┘
```

Admin sees this. Client never does — this is in WP admin which clients don't typically access in your managed maintenance context. You access this once during setup.

### Plugin Installation Flow

During client onboarding:

1. Admin generates agent token for site in your Filament panel → copies token
2. Admin installs plugin on client's WP site (manual upload or via WP admin → Plugins → Upload)
3. Admin enters API URL and token in plugin settings
4. Clicks "Send Test Heartbeat"
5. Platform receives heartbeat, marks site as `agent_installed`, updates status to `active`
6. Onboarding complete

Or automate: provide client a WP-CLI one-liner:
```bash
wp plugin install https://app.reviveguard.com/downloads/reviveguard-agent.zip --activate \
  && wp option update reviveguard_agent_token "THEIR_TOKEN_HERE"
```

### Dependencies

The plugin has zero Composer dependencies — pure WordPress PHP. This is intentional:
- No dependency conflicts with client's other plugins
- No autoloader setup required
- Installs and runs on any standard WP setup
- rclone must be installed on client's server for B2 uploads (check in backup handler, report clearly if missing)

---

## Part B: HTML Site Agent (Shell Script)

### Use Case
Client has a static HTML site, basic PHP site, or any non-WordPress site. Can't install a plugin. You SSH in once, drop the script, set cron.

### Script: `reviveguard-agent.sh`

```bash
#!/bin/bash
# ReviveGuard Site Agent — Shell Script
# Version: 1.0.0
# Install location: /opt/reviveguard/reviveguard-agent.sh

# ── Configuration (set during installation) ──────────────────────────────────
REVIVEGUARD_TOKEN="SET_YOUR_TOKEN_HERE"
REVIVEGUARD_API_URL="https://app.reviveguard.com"
SITE_ROOT="/var/www/html"                   # absolute path to site root
SITE_URL="https://example.com"
B2_BUCKET="reviveguard-backups"
B2_PATH="tenant_1/client_1/site_1"          # provided by platform during setup
BACKUP_RETENTION_DAYS=30                     # set per plan
LOG_FILE="/opt/reviveguard/agent.log"
# ─────────────────────────────────────────────────────────────────────────────

log() { echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] $1" >> "$LOG_FILE"; }

send_heartbeat() {
    PAYLOAD=$(cat <<EOF
{
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "site_url": "$SITE_URL",
    "agent_version": "1.0.0",
    "site_type": "html",
    "disk_usage_mb": $(du -sm "$SITE_ROOT" 2>/dev/null | cut -f1)
}
EOF
)
    RESPONSE=$(curl -s -o /tmp/rg_response.json -w "%{http_code}" \
        -X POST "$REVIVEGUARD_API_URL/api/v1/agent/heartbeat" \
        -H "Authorization: Bearer $REVIVEGUARD_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD" \
        --max-time 15)

    if [ "$RESPONSE" = "200" ]; then
        log "Heartbeat OK"
        # Check for commands in response
        COMMANDS=$(cat /tmp/rg_response.json | python3 -c "import json,sys; data=json.load(sys.stdin); print(json.dumps(data.get('commands', [])))" 2>/dev/null)
        if [ "$COMMANDS" != "[]" ] && [ -n "$COMMANDS" ]; then
            handle_commands "$COMMANDS"
        fi
    else
        log "Heartbeat FAILED — HTTP $RESPONSE"
    fi
    rm -f /tmp/rg_response.json
}

run_backup() {
    COMMAND_ID=$1
    TIMESTAMP=$(date -u +%Y-%m-%d_%H%M%S)
    BACKUP_FILE="/tmp/reviveguard-backup-$TIMESTAMP.tar.gz"

    log "Starting backup..."
    tar -czf "$BACKUP_FILE" -C "$(dirname "$SITE_ROOT")" "$(basename "$SITE_ROOT")" \
        --exclude="*.log" --exclude="*/cache/*" 2>/dev/null
    CHECKSUM=$(sha256sum "$BACKUP_FILE" | cut -d' ' -f1)
    SIZE_MB=$(du -m "$BACKUP_FILE" | cut -f1)
    B2_OBJECT_PATH="$B2_PATH/${TIMESTAMP}_full.tar.gz"

    rclone copy "$BACKUP_FILE" "b2:$B2_BUCKET/$B2_PATH/" --b2-chunk-size 96M 2>/dev/null

    if [ $? -eq 0 ]; then
        log "Backup uploaded: $B2_OBJECT_PATH"
        STATUS="success"
        ERROR=""
    else
        log "Backup upload FAILED"
        STATUS="failed"
        ERROR="rclone upload failed"
    fi

    rm -f "$BACKUP_FILE"

    # Report result
    curl -s -X POST "$REVIVEGUARD_API_URL/api/v1/agent/command-result" \
        -H "Authorization: Bearer $REVIVEGUARD_TOKEN" \
        -H "Content-Type: application/json" \
        -d "{\"command_id\":\"$COMMAND_ID\",\"type\":\"run_backup\",\"status\":\"$STATUS\",\"result\":{\"file_size_mb\":$SIZE_MB,\"checksum\":\"$CHECKSUM\",\"b2_path\":\"$B2_OBJECT_PATH\"},\"error\":\"$ERROR\"}" \
        --max-time 15 > /dev/null
}

handle_commands() {
    # Basic JSON parsing without jq dependency
    # For production: require jq to be installed
    log "Commands received: $1"
    # Command routing handled here
    # Simplified for spec — actual impl uses jq
}

# ── Entry point ───────────────────────────────────────────────────────────────
ACTION=${1:-heartbeat}

case "$ACTION" in
    heartbeat) send_heartbeat ;;
    backup)    run_backup "${2}" ;;
    *)         log "Unknown action: $ACTION" ;;
esac
```

### Installation (Admin Does This Once Per Client)

```bash
# 1. SSH into client server
ssh user@clientserver.com

# 2. Create directory
sudo mkdir -p /opt/reviveguard

# 3. Download agent script from your platform
sudo curl -s https://app.reviveguard.com/downloads/reviveguard-agent.sh \
    -o /opt/reviveguard/reviveguard-agent.sh
sudo chmod +x /opt/reviveguard/reviveguard-agent.sh

# 4. Set configuration (sed replace the placeholders)
sudo sed -i 's|SET_YOUR_TOKEN_HERE|ACTUAL_TOKEN|g' /opt/reviveguard/reviveguard-agent.sh
sudo sed -i 's|https://example.com|https://actual-client-site.com|g' /opt/reviveguard/reviveguard-agent.sh
# ... set other vars

# 5. Install rclone (if not present)
curl https://rclone.org/install.sh | sudo bash
# Configure rclone with B2 credentials: rclone config

# 6. Add cron jobs
(crontab -l 2>/dev/null; echo "*/5 * * * * /opt/reviveguard/reviveguard-agent.sh heartbeat") | crontab -

# 7. Test
/opt/reviveguard/reviveguard-agent.sh heartbeat
```

**Time to install per client:** ~15 minutes first time, ~10 minutes after you've done it once.

---

## Agent Token Lifecycle

| Event | Action |
|---|---|
| Site added in admin panel | Token generated, shown once, stored hashed |
| Token compromised | Admin → Rotate Token → new token generated, admin manually updates plugin/script settings |
| Site removed | Token invalidated immediately in DB |
| Client account cancelled | All site tokens invalidated |

**Token format:** 64-character hex string (`bin2hex(random_bytes(32))`)
**Storage:** SHA-256 hash stored in DB. Plain token shown once at creation (copy it now). Same model as GitHub personal access tokens.

---

## Agent Version Management

- Agent version included in every heartbeat payload
- Platform logs which agent version each site runs
- When you release a new plugin version: upload to WordPress.org or self-host update server
- Filament shows sites running outdated agent versions with a warning badge
- No auto-update of the agent itself in Phase 1 — admin updates manually during client onboarding if needed
