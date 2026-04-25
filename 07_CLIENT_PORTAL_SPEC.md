# ReviveGuard — Client Portal Specification

---

## Portal Philosophy

The portal has one job: **give clients peace of mind.**

Every screen should answer the unspoken question: "Is my website okay?" The answer is almost always yes. Make "yes" feel reassuring and trustworthy, not clinical.

**Design tone:** Calm, clean, professional. Like a premium dashboard — not a tech tool.
**Audience:** Non-technical small business owners. They don't know what PHP is. They care that their website is alive and protected.

---

## Technical Setup

- **URL:** `portal.reviveguard.com` (Phase 1)
- **Framework:** Laravel Livewire (server-rendered, no separate React app)
- **Auth:** Laravel Breeze (email + password, forgot password via email)
- **Session:** PHP sessions, 8-hour timeout
- **Mobile:** Responsive layout, works on any screen size
- **Real-time feel:** Livewire polling every 60 seconds on dashboard (not websockets — polling is enough)

---

## Authentication Screens

### Login Page

URL: `portal.reviveguard.com/login`

**Elements:**
- ReviveGuard logo (top center)
- Heading: "Sign in to your dashboard"
- Email field
- Password field
- "Forgot password?" link
- "Sign in" button
- No "Create account" link (clients are created by admin, not self-registered)

**Behavior:**
- Failed login: show inline error "Invalid email or password"
- After 5 failed attempts: 60-second lockout with countdown shown
- On success: redirect to Dashboard
- "Forgot password?" sends reset link to email (standard Laravel Breeze flow)

---

### Forgot Password / Reset Password

Standard Laravel Breeze pages, branded. No custom logic needed.

---

## Main Navigation

Fixed sidebar on desktop, hamburger drawer on mobile.

**Nav items:**
```
[Logo]

● Dashboard
  Sites          (if client has multiple sites)
  Reports
  Backups
  Tickets
─────────────
  Account
  Sign out
```

For Phase 1, most clients have **1 site**. The "Sites" item still exists for future, but clicking into it just shows their one site.

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

## Screen 6: Account Settings

URL: `/account`

```
┌─────────────────────────────────────────────────────────────┐
│  Account Settings                                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Name:           [John Smith              ]                  │
│  Email:          [john@johnsbakery.com    ]                  │
│  WhatsApp:       [+1 (415) 555-1234       ]                  │
│                  (For urgent site alerts)                    │
│                                                              │
│  [Save Changes]                                              │
│                                                              │
├─── Change Password ─────────────────────────────────────────┤
│  Current password:   [________________]                      │
│  New password:       [________________]                      │
│  Confirm password:   [________________]                      │
│  [Update Password]                                           │
│                                                              │
├─── Your Plan ───────────────────────────────────────────────┤
│  Current plan:  Guard — $49/month                            │
│  Next billing:  May 1, 2025                                  │
│  [Manage billing & invoices →]     ← links to Stripe portal  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**"Manage billing & invoices →"** opens Stripe Customer Portal in new tab. Client can update card, view/download invoices, cancel subscription there. You don't build any of this.

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
