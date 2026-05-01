# ReviveGuard — MVP Feature Specification

---

## The Rule for This Document

Every feature below is either **IN** or **OUT** of Phase 1 MVP. There is no "maybe" or "could be nice." If it's OUT, it does not exist in the codebase until Phase 2. This discipline is what makes Phase 1 shippable in weeks, not months.

**MVP success criterion:** 5-10 paying clients onboarded, receiving monthly reports, seeing their site health in a portal, billed correctly via Stripe. That's it.

---

## Phase 1 — MVP (Weeks 1-10)

### Feature 1: Admin Panel (Internal)

**IN:**
- Filament-based admin at `app.reviveguard.com/admin`
- Login with 2FA

**Client Management:**
- Create, view, edit, list clients
- Filter by status (invited / active / suspended / churned) and path (alumni / evaluation)
- View each client's path, invite status, subscription, and all their sites in one place
- Manually activate, suspend, or reactivate a client

**Invite Management (new — core of both onboarding paths):**
- Create a single invite: enter name, email, site URL, select path (alumni / evaluation)
- Bulk import alumni from CSV (columns: name, email, site_url — imported from WaybackRevive export)
- Select multiple imported alumni → "Send Invites" bulk action → system emails all selected
- View all invites: pending / accepted / expired with timestamps
- Resend expired invite (generates new token, extends expiry by 72h)
- Revoke a pending invite (invalidates token immediately)
- Invite detail view: shows token status, email sent timestamp, accepted timestamp, linked client

**Site Management:**
- Add site (for admin-managed onboarding), generate agent token, view site status, view events
- Manual backup trigger (queues command, agent picks it up)
- Manual update trigger (same pattern)
- View all events for a site (paginated list)
- View all backups for a site
- Rotate agent token (invalidates old token, generates new one)

**Evaluation Queue (Path B admin workflow):**
- Dedicated Filament resource showing all evaluation submissions
- Each entry: prospect name, site URL, submission date, site type, stated concern, status
- Status workflow: pending → reviewing → proposed → converted / declined / expired
- "Start Review" action → status → reviewing
- Notes field: team adds tech findings, risks, recommended plan
- "Send Proposal" action:
  - Creates a `client_invites` record (path='evaluation', evaluation_id linked)
  - System generates token and sends proposal email
  - Status → proposed
- "Decline" action → sends polite rejection email, status → declined
- Monthly cap indicator: shows "X / 26 accepted this month" in queue header
- Cap is configurable in admin settings (default: 26)

**Dashboard & Monitoring:**
- Global site health overview: X sites up, Y down, Z warnings (dashboard widget)
- Admin can manually trigger monthly report for any site
- Admin can see subscription status per client

**OUT:**
- Multi-user admin roles (you're the only admin in Phase 1)
- Audit log of admin actions
- Bulk operations across sites (other than invite send)
- Custom plan builder
- Reseller management

---

### Feature 2: WordPress Agent Plugin

**IN:**
- Heartbeat every 5 minutes to `/api/v1/agent/heartbeat`
- Sends: WP version, PHP version, plugin count, theme name, disk usage, agent version
- Receives command list in heartbeat response
- Executes `run_backup` command: creates tar.gz of files + DB export, uploads to B2 via rclone, reports result
- Executes `run_wp_updates` command: runs `wp core update`, `wp plugin update --all`, reports result
- Sends plugin list when changes detected
- Simple admin settings page in WP dashboard: shows token, connection status, last heartbeat timestamp
- Secure token storage in `wp_options` table

**OUT:**
- Staging environment pre-update testing
- Selective plugin updates (all-or-nothing in Phase 1)
- Rollback after failed update (Phase 2)
- Performance metrics (page load time, etc.)
- Security scan from agent side (Uptime Kuma external scan covers Phase 1)
- Multisite WordPress support

---

### Feature 3: HTML Site Agent

**IN:**
- Single bash script: `reviveguard-agent.sh`
- Heartbeat: `curl` POST to heartbeat endpoint every 5 min via cron
- Monthly backup: `tar.gz` site files + `rclone` to B2
- Reports backup result via `curl` POST to `/api/v1/agent/command-result`
- Installation instructions: one `README.md` with copy-paste commands

**OUT:**
- Auto-update for static sites (N/A)
- Plugin scanning (N/A)
- Rollback (Phase 2)

---

### Feature 4: Uptime Monitoring

**IN:**
- Uptime Kuma running headless on VPS
- When admin adds a site: Laravel calls Uptime Kuma API to create HTTP monitor
- When site goes down: Uptime Kuma webhook → Laravel → alert dispatched
- When site recovers: Uptime Kuma webhook → Laravel → recovery alert dispatched
- Agent heartbeat as secondary check (belt and suspenders)
- 30-day uptime percentage fetched from Uptime Kuma API, cached in `sites.uptime_30d`
- Uptime data shown in client portal as a percentage

**OUT:**
- Multi-location monitoring (single server check in Phase 1)
- Custom check frequency per plan
- Uptime status page for clients (Livewire portal page serves this purpose)
- Transaction monitoring / form submission checks

---

### Feature 5: SSL & Domain Monitoring

**IN:**
- Daily scheduled job: check SSL expiry for all active sites using PHP `openssl` functions
- Alert thresholds: 60, 30, 7 days before expiry
- Daily scheduled job: check domain expiry via WHOIS using `iodev/whois` PHP package
- Alert thresholds: 60, 30, 7 days before expiry
- One alert per threshold (not every day once triggered)
- SSL data stored in `sites.ssl_expires_at`, `sites.ssl_issuer`
- Domain data stored in `sites.domain_expires_at`

**OUT:**
- DNS record change monitoring
- HTTP response code monitoring (Uptime Kuma handles this)
- Certificate authority specific alerts

---

### Feature 6: Backup System

**IN:**
- Backup triggered by command queue (admin triggers, or scheduler for plan frequency)
- Agent executes backup locally, uploads to B2 directly
- Backup record created in `backups` table with status
- Plan-based retention: Monitor=30d, Guard=90d, Shield=180d (managed by B2 lifecycle rules)
- Backup verification: after upload, agent downloads first 512 bytes and verifies checksum
- Admin can see backup history per site
- Client can see backup history in portal (last 10 entries, no download link — they can request restore)

**OUT:**
- Client-initiated backup download (security risk, complexity — they email you to request restore)
- Incremental backups (full only in Phase 1)
- Backup to multiple destinations
- Database-only backups
- Scheduled backup time customization per client

---

### Feature 7: WordPress Updates

**IN:**
- Guard and Shield plans only
- Admin can manually trigger update run for any site
- Scheduler triggers update run weekly (Guard) / weekly (Shield — same frequency, just included)
- Command queued → agent picks up → runs `wp core update` then `wp plugin update --all`
- Result reported back: success/failure, which items were updated, any errors
- Events logged for each update run
- Email notification to client after update: "3 plugins and WordPress core updated on your site"

**OUT:**
- Update scheduling by client preference (always runs Sunday 02:00 UTC in Phase 1)
- Selective plugin updates
- Pre-update staging test
- Automatic rollback on failure (if update fails, admin is alerted to handle manually)
- Visual diffing before/after

---

### Feature 8: Client Portal

**IN:**
- Login page: `portal.reviveguard.com` (or client's own subdomain — Phase 2)
- Password-based login, no SSO
- Forgot password via email
- Dashboard view: site status, uptime %, last backup date, days until SSL/domain expiry
- Events list: chronological log of all site events (last 30 days shown)
- Reports list: all monthly reports, PDF download link (signed URL, 1-hour expiry)
- Backups list: last 10 backups, status, date, size
- Support ticket: simple form (subject + description), view own tickets and their status
- Account page: update name, email, WhatsApp number, change password
- Responsive design (works on mobile)
- Branded: your logo, your colors

**OUT:**
- Real-time push notifications in portal (polling every 60 seconds is enough)
- Multiple sites on one dashboard view (client clicks into each site separately)
- Client-customizable notification preferences (you set the defaults, they can't override in Phase 1)
- Chat widget
- Invoice history in portal (Stripe portal handles this — link to it)

---

### Feature 9: Notifications & Alerts

**IN:**
- Email via Resend for all notifications
- WhatsApp via Meta Cloud API for critical alerts (site down, malware found)
- Alert types in Phase 1:
  - Site down (immediate, critical)
  - Site recovered (immediate, info)
  - SSL expiry warning (daily digest — one email per threshold per site)
  - Domain expiry warning (daily digest)
  - Backup failed (next business hour)
  - Monthly report ready (with PDF attached)
  - Update complete (summary email, not per-plugin)
  - Support ticket response (when admin replies)
- Notification goes to: client email + client WhatsApp (if provided)
- Admin also gets: site down alert, backup failed alert, malware alert
- `notifications_log` records every send attempt

**OUT:**
- Slack integration
- SMS (WhatsApp covers the "immediate mobile" need)
- Client-configurable notification frequency
- Snooze / acknowledge alerts in portal
- Escalation policies

---

### Feature 10: Billing (Whop)

**IN:**
- 3 subscription plans configured on Whop (monthly billing)
- Checkout via Whop hosted checkout — no custom checkout UI needed
- `WhopBillingService` handles all membership lifecycle events
- Whop webhooks handled: `membership.went_valid`, `membership.went_invalid`, `membership.was_banned`
- **Automated self-service onboarding (zero human involvement):**
  - User buys plan on Whop → `membership.went_valid` webhook fires
  - `WhopBillingService` auto-creates `Client` record (locked portal password)
  - `OnboardClientJob` dispatched: creates `Site` record, Uptime Kuma monitor, generates magic activation link
  - Magic link sent via `sendWelcome()` email (Resend) — valid 72 hours
  - User clicks link → sets own password on `/portal/activate/{client}?token=...` page → auto-logged in → redirected to dashboard
  - If link expires: user uses standard "Forgot Password" flow
- **Plan change / upgrade notification:**
  - When existing client's `membership.went_valid` fires again (upgrade or reactivation)
  - `sendPlanUpdated()` email sent to confirm new plan and billing details
  - Subscription record updated; client stays active with updated plan
- On cancellation/payment failure: subscription marked inactive, portal access revoked, email sent
- On ban: client deactivated immediately
- Admin can see subscription status per client in Filament

**OUT:**
- Annual billing option (Phase 2)
- Custom checkout page (Whop checkout is fine for Phase 1)
- Promo codes / discounts UI (manage in Whop dashboard)
- Metered / usage-based billing

---

### Feature 12: New-Client Evaluation Flow (Path B Onboarding)

**IN:**
- Public `GET /evaluation` page on marketing site with form:
  - Name, email, website URL (required)
  - Site type (WordPress / HTML / Other) (required)
  - "What's your biggest concern about your website?" (textarea, optional)
- Form submits to `POST /evaluations` (rate-limited: max 3 per IP per day)
- `site_evaluations` record created with `status = pending`
- Confirmation page shown + confirmation email sent (Resend)
- **Admin Evaluation Queue (Filament):**
  - New evaluations appear in a dedicated Filament resource with queue view
  - Each evaluation card shows: site URL, prospect name, submission date, site type, their stated concern
  - Team clicks "Start Review" → status → `reviewing`
  - Team adds internal notes (tech findings, risks, recommended plan)
  - Team clicks "Send Proposal" → status → `proposed`:
    - `EvaluationService` sends proposal email (Resend):
      - "Here's what we found" section with their site's risk summary
      - Recommended plan with feature breakdown
      - Clear pricing
      - "Accept this proposal" button (magic link, 72h TTL, hashed token in DB)
      - "Not right now" link (marks as declined)
- **Prospect accepts proposal:**
  - Magic link validates `proposal_token` + expiry
  - `Client` record created from evaluation data
  - `OnboardClientJob` dispatched (same as Path A)
  - `converted_client_id` set on evaluation record
  - Portal activation email sent
- **Monthly cap enforcement:**
  - Before accepting any new evaluation for review: count accepted evaluations this calendar month
  - If count ≥ 26: show waitlist page instead of evaluation form
  - Waitlist: capture name + email, get notified when next month's spots open
  - Cap is configurable in admin settings (default: 26)
- **Auto follow-up:**
  - If proposal not accepted after 7 days: send one follow-up with a free tip ("We noticed your SSL expires in 45 days — here's what that means")
  - After 14 days with no response: mark `expired`

**OUT:**
- Automated site scanning/crawling during evaluation (manual review by team)
- Instant approval without human review
- Self-serve checkout without evaluation (Path A alumni are the only ones who skip evaluation)
- Multiple proposal versions / negotiation

---

### Feature 13: Self-Serve Portal (Client Ownership)

Clients have full control over their own dashboard and plan — they don’t have to contact you to change anything.

**IN:**
- **Site Onboarding Wizard (3 steps, like WPMaintenance):**
  - **Step 1 — Domain:** Enter company name + domain URL
  - **Step 2 — Package:** Choose plan (Monitor/Guard/Shield) with feature comparison visible. Select add-ons.
  - **Step 3 — Order:** Review selection + total → "Proceed to checkout" → Whop hosted checkout
  - After checkout completion: wizard shows "Your site is being set up" → redirect to dashboard
- **Plan Management (self-serve):**
  - Client can see current plan on Account page
  - "Change plan" → shows all plans → select → confirm → Whop upgrade/downgrade flow
  - Downgrade: takes effect at end of billing period (shown to client clearly)
  - Upgrade: takes effect immediately, prorated
- **Add-on Toggle:**
  - Client can enable/disable add-ons (e.g., extra backup storage) directly in portal
  - Each toggle shows price impact immediately
  - Confirm → Whop billing updated
- **Invoice History:**
  - Table: Date | Invoice # | Amount | Status | Download
  - "Download" links to PDF (generated by Puppeteer, stored on B2)
  - Invoices auto-generated monthly by `BillingService` after successful charge
  - "Pay with [method]" button for outstanding amounts if applicable
- **Billing Details:**
  - Client can update payment method (redirects to Whop billing portal)
  - View next billing date and amount

**OUT:**
- Client-initiated plan cancellation from portal (they must email — gives you a save opportunity)
- Custom billing cycles per client
- Multiple payment methods stored
- Client-facing discount codes

---

### Feature 11: Automated Reports

**IN:**
- Monthly report generated on 1st of each month at 09:00 UTC
- PDF generated via Puppeteer microservice
- Report includes: uptime %, downtime events with timestamps, WP updates applied (names and versions), backups confirmed, SSL days remaining, domain days remaining, next month's planned actions
- Branded PDF: your logo, client's site name, month/year, professional layout
- PDF stored on B2
- Report record saved to `reports` table
- Email sent with PDF attached (not just a link — actually attached, max ~1MB per report)
- Admin can manually trigger report for any site/month

**OUT:**
- Custom report templates per plan
- Client-selectable report frequency (monthly only in Phase 1)
- White-label report with client's own branding
- Interactive HTML reports (PDF only)

---

## Explicitly Out of Scope (Phase 1) — Full List

To be crystal clear on what you will NOT be building:

- Reseller/white-label accounts
- API access for clients
- Mobile app
- Public status page per client
- Custom client subdomain portal (`client.reviveguard.com`)
- SEO health monitoring
- Performance/speed monitoring
- Google Analytics integration
- Spam protection monitoring
- Firewall management
- CDN management
- Staging environment management
- Multisite WordPress support
- Non-English interface
- GDPR consent management
- Two-factor auth for clients (admin only)
- Affiliate/referral tracking system
- Zapier / n8n integration
- Content delivery features
- Chatbot / live chat

---

## Phase 2 Preview (Don't Build, But Design For)

The database schema and API are designed to accommodate these without breaking changes:

- Annual billing (just add `stripe_price_id_annually` to plans, already in schema)
- Reseller tenants (already in multi-tenant architecture)
- Custom client subdomains (tenant routing already in place via `stancl/tenancy`)
- Rollback after failed update (add rollback command type to command queue)
- Selective plugin updates (filter on plugin list before queuing update command)
- WhatsApp Business API upgrade (same API surface, just higher volume)
