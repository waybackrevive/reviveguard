# ReviveGuard — Client Portal Specification

> **Note (July 2026):** For **shipped** portal behavior (billing, monitoring, timezone, toasts, add-ons), use **`10_PORTAL_UX_MASTER_PLAN.md`** as the source of truth. This file retains screen-level wireframes and legacy detail; admin ops are in **`11_ADMIN_OPS_MASTER_PLAN.md`**.

---

## Portal Philosophy

The portal has two jobs: **give clients peace of mind** and **give clients real ownership**.

Every screen should answer the unspoken question: "Is my website okay?" But unlike a passive monitoring tool, clients should feel genuinely in control — they can choose their plan, add sites, manage add-ons, view invoices, and submit tickets, all without needing to contact us.

**Design inspiration:** WPMaintenance's self-serve dashboard — clients own their experience. We provide the expertise behind it.

**Design tone:** Calm, clean, professional. Light theme. Like a premium SaaS dashboard — not a tech tool.
**Audience:** Non-technical small business owners. They don't know what PHP is. They care that their website is alive and protected.

---

## Technical Setup

- **URL:** `app.reviveguard.com/portal/*`
- **Framework:** Laravel Livewire (server-rendered, no separate React app)
- **Auth:** Custom `client` guard (email + password, magic link for first login)
- **Session:** PHP sessions, 8-hour timeout
- **Mobile:** Responsive layout, works on any screen size
- **Real-time feel:** Livewire polling every 60 seconds on dashboard (not websockets — polling is enough)

---

## Navigation (Sidebar)

## Navigation (Sidebar)

Fixed sidebar on desktop, hamburger drawer on mobile.

```
[● Revive Guard logo]

[+ Add website]          ← prominent button, always visible

● Dashboard
  My Websites
  Activity Log
  Reports
  Backups
  Support
─────────────
  Account
  Sign out

[Operated by WaybackRevive LLC]
```

The **"+Add website"** button is the most prominent CTA in the nav — mirrors WPMaintenance. Clients grow their own portfolio without asking you. Every added site = more MRR.

---

## Screen 0: Site Onboarding Wizard

URL: `/portal/add-website` (triggered by "+Add website" button)

3-step wizard. No full-page reloads — Livewire steps.

### Step 1 — Domain

```
┌─────────────────────────────────────────────────────────────┐
│  Add Website                                                 │
│                                                             │
│  ① Domain name ──────────── ② Package options ── ③ Order  │
│  (active)                                                   │
│                                                             │
│  Company name:  [_________________________________]          │
│                                                             │
│  Domain name:   [🔍 www.yourwebsite.com ] [Check]           │
│                                                             │
│  Create login details                                       │
│  We need access to your site to start protecting it.       │
│                                                             │
│  [WP Authorize us]   — or —   [Add credentials manually]   │
│                                                             │
│                              [Go to package options →]      │
└─────────────────────────────────────────────────────────────┘
```

**"Authorize us"** for WordPress: Opens our plugin download page. Client installs plugin, plugin registers automatically (generates agent token, calls our API). "Check" button verifies we received the heartbeat.

**"Add manually"**: Client enters WP admin URL + application password. We store it encrypted for initial setup only, then install plugin via WP REST API.

### Step 2 — Package Options

```
┌─────────────────────────────────────────────────────────────┐
│  Choose your maintenance package                             │
│                                                             │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │  Monitor    │  │  Guard  ✓    │  │  Shield          │   │
│  │  $19/mo     │  │  $49/mo      │  │  $99/mo          │   │
│  │  [Select]   │  │  [Selected]  │  │  [Select]        │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
│                                                             │
│  [Show/hide full plan comparison]                           │
│                                                             │
│  Add-ons:                                                   │
│  ○ Extra backup storage (10GB)  +$5/mo                      │
│  ○ Speed optimization audit     $49 one-time                │
│                                 [Proceed to order →]        │
│                                                             │
│  ┌───────────────────────────────────────────────────┐      │
│  │  SUMMARY                     [✎ Adjust domain]   │      │
│  │  Domain: johnsbakery.com                          │      │
│  │  Package: Guard — $49/mo                          │      │
│  │  Add-ons: none                                    │      │
│  │  Total: $49/mo                                    │      │
│  │  [Proceed to order →]                             │      │
│  └───────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Step 3 — Order

```
┌─────────────────────────────────────────────────────────────┐
│  Order Summary                                               │
│                                                             │
│  johnsbakery.com                                            │
│  Guard plan — $49/month                                     │
│  Billed monthly. Cancel anytime.                            │
│                                                             │
│  [→ Proceed to secure checkout]                             │
│                                                             │
│  ⓘ You haven't been charged yet.                           │
│  Checkout is handled securely by Whop.                      │
└─────────────────────────────────────────────────────────────┘
```

→ Redirects to Whop hosted checkout with `redirect_url` back to `/portal/welcome`

---

## Screen 1: Dashboard

URL: `/dashboard`

This is the first screen after login. Must answer "is everything okay?" in under 3 seconds.

### Layout (Desktop)

```
┌─────────────────────────────────────────────────────────────┐
│  Good morning, John.                          [Your Site ▼] │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────┐  ┌────────────────┐  ┌─────────────┐  │
│  │  ● SITE IS UP   │  │  99.97% Uptime │  │  Last Backup│  │
│  │  johnsbakery.com│  │  Last 30 days  │  │  2 days ago │  │
│  │  Checked 3m ago │  │                │  │  ✓ Verified │  │
│  └─────────────────┘  └────────────────┘  └─────────────┘  │
│                                                              │
│  ┌─────────────────┐  ┌────────────────┐                    │
│  │  SSL Certificate│  │  Domain        │                    │
│  │  153 days left  │  │  289 days left │                    │
│  │  ✓ Valid        │  │  ✓ Active      │                    │
│  └─────────────────┘  └────────────────┘                    │
│                                                              │
├─── Recent Activity ─────────────────────────────────────────┤
│                                                              │
│  ✓  Backup completed successfully          Apr 1, 02:00     │
│  ✓  3 plugins updated                      Mar 31, 03:15    │
│  ✓  WordPress 6.5.2 installed              Mar 30, 03:00    │
│  ℹ  Monthly report ready                   Apr 1, 09:00     │
│                                                              │
│  [View all activity →]                                       │
└─────────────────────────────────────────────────────────────┘
```

### Status Card Colors
- **Site is UP:** green pill badge, calm blue card background
- **Site is DOWN:** red pill badge, subtle red card background — shows "DOWN since [time]"
- **Warning (SSL < 30 days):** amber badge on SSL card
- **All good:** no drama, just clean data

### Uptime Display
- Single percentage number: `99.97%`
- Label: "Last 30 days"
- No chart in Phase 1 (too complex for MVP) — just the number is enough
- Phase 2: add a small sparkline chart

### Recent Activity
- Last 5 events across the site
- Icons by event type:
  - ✓ green checkmark: success events (backup ok, update ok, site recovered)
  - ⚠ amber warning: warnings (SSL/domain expiry approaching)
  - ✗ red X: critical (site down, backup failed)
  - ℹ blue info: informational (report ready, ticket response)
- Clicking any event shows a modal with full event details
- "View all activity →" → Events screen

### Auto-refresh
Livewire polls every 60 seconds. If status changes (site goes from up to down), the status card updates automatically without page reload.

---

## Screen 2: Events (Activity Log)

URL: `/events`

**Heading:** "Activity Log"

**Filters (simple row above table):**
```
[All Events ▼]  [All Severities ▼]  [Last 30 days ▼]
```

**Table columns:**
```
Date/Time          | Event                           | Type        | Status
Apr 1, 2025 02:00  | Backup completed (145 MB)       | Backup      | ✓ Success
Mar 31, 03:15      | 3 plugins updated                | Updates     | ✓ Success
Mar 30, 14:22      | Site was unreachable for 4 min  | Downtime    | ✗ Resolved
Mar 28, 09:00      | SSL expires in 30 days          | SSL Warning | ⚠ Warning
```

**Pagination:** 20 per page, standard prev/next.

**Event detail modal (click any row):**
```
┌───────────────────────────────────────┐
│  Backup completed successfully         │
│  April 1, 2025 at 02:00 UTC            │
│                                        │
│  Backup size: 145 MB                   │
│  Duration: 42 seconds                  │
│  Storage: Secure cloud backup          │
│  Verified: ✓ Checksum confirmed        │
│                                        │
│  [Close]                               │
└───────────────────────────────────────┘
```

**Technical terms that clients NEVER see:**
- "Backblaze B2" → "Secure cloud backup"
- "HMAC" → nothing, never shown
- "Plugin update --all" → "Plugins updated"
- "tar.gz" → "backup file"
- "HTTP 503" → "site was unreachable"

---

## Screen 3: Reports

URL: `/reports`

**Heading:** "Monthly Reports"

**Subheading:** "Your monthly site health report, delivered automatically."

**Report list:**
```
┌───────────────────────────────────────────────────────────┐
│  March 2025           Generated Apr 1 ·  [Download PDF]   │
│  Uptime: 100% · 5 updates · 4 backups                     │
├───────────────────────────────────────────────────────────┤
│  February 2025        Generated Mar 1 ·  [Download PDF]   │
│  Uptime: 99.8% · 8 updates · 4 backups                    │
└───────────────────────────────────────────────────────────┘
```

**Download PDF:** opens signed B2 URL in new tab. URL expires after 1 hour (generated fresh on click).

**Empty state (new client, no reports yet):**
"Your first monthly report will be ready on [date of next 1st of month]. We'll email it to you."

---

## Screen 4: Backups

URL: `/backups`

**Heading:** "Backups"

**Subheading on plan context:**
- Monitor: "Your site is backed up monthly. Files are kept for 30 days."
- Guard: "Your site is backed up weekly. Files are kept for 90 days."
- Shield: "Your site is backed up daily. Files are kept for 180 days."

**Backup list:**
```
┌─────────────────────────────────────────────────────────┐
│  Apr 1, 2025 02:00  │  Full backup  │  145 MB  │  ✓    │
│  Mar 25, 2025 02:00 │  Full backup  │  144 MB  │  ✓    │
│  Mar 18, 2025 02:00 │  Full backup  │  143 MB  │  ✓    │
│  Mar 11, 2025 02:00 │  Full backup  │  143 MB  │  ✓    │ ← oldest shown
└─────────────────────────────────────────────────────────┘
```

**No download button** — clients cannot self-serve download backups. They request a restore via support ticket. This is intentional: prevents misuse, keeps restore process quality-controlled.

**Below list:**
> "Need to restore a backup? [Open a support ticket →] and we'll restore it for you."

---

## Screen 5: Support Tickets

URL: `/tickets`

**Heading:** "Support"

### Ticket Submission Form
```
Need help with your website?

Subject: [_________________________________]
Site:     [My Bakery Website         ▼]   (dropdown, pre-selected if only 1 site)
Message:  [                              ]
          [                              ]
          [                              ]
          (Please describe the issue in detail)

          [Submit Ticket]
```

**Validation:**
- Subject: required, min 5 chars
- Message: required, min 20 chars

**On submit:**
- Inline success message: "Ticket submitted. We'll respond within 24 hours."
- Email confirmation sent to client
- Ticket appears in "Your Tickets" list immediately

### Tickets List
```
┌─────────────────────────────────────────────────────────────┐
│  Your Tickets                                                │
│                                                             │
│  ● Open    Contact form not working          Submitted Apr 2 │
│  ● Resolved Plugin conflict causing 404s     Submitted Mar 8 │
└─────────────────────────────────────────────────────────────┘
```

**Clicking a ticket:**
- Shows subject, your message, status, and any admin response
- No in-ticket reply in Phase 1 — if client needs to add more info, they open a new ticket or reply to the email notification

**Plan limit enforcement:**
- Monitor plan: 0 ticket slots/month (show: "Support tickets are available on Guard and Shield plans. [Upgrade →]")
- Guard: 1/month counter shown ("1 of 1 support tickets used this month")
- Shield: unlimited (no counter shown)

---

## Screen 6: Account & Plan Management

URL: `/portal/account`

Split into tabs: **My details** | **My plan** | **Billing & invoices**

### Tab: My Details
```
Name:           [John Smith              ]
Email:          [john@johnsbakery.com    ]
WhatsApp:       [+1 (415) 555-1234       ]
                (For urgent site alerts)

[Save Changes]

─── Change Password ──────────────────────────────────────────
Current password:   [________________]
New password:       [________________]
Confirm password:   [________________]
[Update Password]
```

### Tab: My Plan

```
Current plan:    Guard — $49/month
Included sites:  1 site
Next billing:    May 1, 2025

[↑ Upgrade to Shield]  [Downgrade to Monitor]

─── Active Add-ons ───────────────────────────────────────────
○ Extra backup storage (10GB)   +$5/mo      [Enable]
○ Speed optimization audit      $49 once    [Purchase]

─── Plan Feature Comparison ──────────────────────────────────
[Show comparison table ▼]
```

**Upgrade:** Opens Whop upgrade flow. Takes effect immediately with proration.
**Downgrade:** Queues for end of billing period. Shows clear date.
**Add-on toggle:** Immediately updates Whop subscription, shows new total.

### Tab: Billing & Invoices

```
TOTAL OUTSTANDING
$0

Payment method:  Visa ending 4242   [Update payment method →]

INVOICES
─────────────────────────────────────────────────────────────
Date         Invoice #      Amount     Status
Apr 1, 2025  RVG-2025-004  $49.00     Paid    [Download PDF]
Mar 1, 2025  RVG-2025-003  $49.00     Paid    [Download PDF]
Feb 1, 2025  RVG-2025-002  $49.00     Paid    [Download PDF]
Jan 1, 2025  RVG-2025-001  $49.00     Paid    [Download PDF]
─────────────────────────────────────────────────────────────

* Prices exclude applicable taxes.

[Update payment method →]    ← links to Whop billing portal
[Invoice details →]          ← links to update billing address
```

---

## Screen 7: My Websites

URL: `/portal/websites`

Shows all sites this client has added. For most clients: 1 site. For growing businesses: multiple.

```
MY WEBSITES

johnsbakery.com          Guard     ● UP      [View dashboard]
johnsbakeryblog.com      Monitor   ● UP      [View dashboard]

[+ Add another website]
```

Clicking "View dashboard" switches the active site context and loads Screen 1 for that site.

---

## Notification Emails (What Clients Receive)

All emails sent via Resend. Branded with your logo.

### Email: Site Is Down
```
Subject: ⚠ Your website is not responding — johnsbakery.com

[Logo]

Your website johnsbakery.com appears to be offline.

We detected this at 10:23 AM UTC on April 1, 2025. Our team has been 
alerted and is investigating.

We'll notify you as soon as it's back online.

If you believe your hosting provider is responsible, you may want to 
contact them directly.

[View Dashboard →]
```

### Email: Site Recovered
```
Subject: ✓ Your website is back online — johnsbakery.com

Your website johnsbakery.com is back online.

Downtime duration: 4 minutes 22 seconds
Detected at: 10:23 AM UTC
Recovered at: 10:27 AM UTC

[View Dashboard →]
```

### Email: Monthly Report
```
Subject: Your April site report is ready — johnsbakery.com

Here's what happened on your website in March 2025:

  Uptime:         100%
  Updates:        5 items updated
  Backups:        4 backups stored
  SSL expires:    153 days from now
  Domain expires: 289 days from now

Your full report is attached as a PDF.

[View Dashboard →]
```
— Attached: `march-2025-site-report.pdf`

### Email: SSL/Domain Expiry Warning
```
Subject: ⚠ Your SSL certificate expires in 30 days — johnsbakery.com

Your SSL certificate for johnsbakery.com expires on September 1, 2025 
(30 days from now).

An expired SSL certificate causes browsers to show a security warning 
to your visitors — this can cause people to immediately leave your site.

We're handling the renewal for you as part of your maintenance plan. 
No action needed on your end. We'll confirm once it's renewed.

[View Dashboard →]
```

---

## UX Rules (Non-Negotiable)

1. **No jargon** — rewrite every technical term in plain English before it shows on any client-facing screen
2. **Status must be visible without scrolling** — the site up/down status is always above the fold on dashboard
3. **Empty states must explain** — no blank tables. "No events this month — that's a good sign!" not an empty white box
4. **Errors must be human** — "Something went wrong. Try refreshing." not "500 Internal Server Error"
5. **Success must be acknowledged** — after ticket submission, backup request, profile save — always show confirmation
6. **Loading states** — Livewire shows subtle loading indicator when polling/refreshing. Never leave the user staring at stale data with no indication it's refreshing
7. **Mobile works** — all screens tested at 375px width minimum
