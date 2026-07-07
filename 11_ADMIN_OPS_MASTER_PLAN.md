# ReviveGuard — Admin Ops Master Plan

> **Status:** Locked direction — implement after portal stabilization  
> **Audience:** Naqeeb (product owner), ops team, developers, AI agents  
> **Production:** `app.reviveguard.com/admin` · Filament 3 · Super-admin only  
> **Last verified:** July 7, 2026 (code audit + live dashboard screenshot)  
> **Companion docs:** `10_PORTAL_UX_MASTER_PLAN.md`, `09_DEPLOYMENT_GUIDE.md`

---

## 1. Executive summary

The client portal is now the **client-facing truth** (Stripe billing, per-site monitoring, timezone, plan changes, add-ons, tickets). The admin panel must become the **operator-facing truth** — one place to run the business with **no blind spots**, no misleading numbers, and no guessing.

**Today’s admin dashboard is not wrong by accident — it is incomplete.** The screenshot shows:

| Widget | Shows | Problem (verified in code) |
|--------|-------|----------------------------|
| Total Sites **9** | All sites in DB | OK |
| Active / Healthy **1** · 5 down | `SiteStatus::ACTIVE` count | **Misleading label** — not “healthy” in portal sense |
| *(missing)* | — | **3 sites unaccounted** — `pending` + `suspended` not shown |
| Avg 7d Uptime **100%** | `avg(uptime_7d)` where not null | Excludes sites with no probe data |
| Active Clients **3** | `Client::where(is_active)` | **Wrong label** — says “Paying clients” but is NOT Stripe/paid count |

**Goal:** Admin = **complete business visibility** for retention, support, billing ops, and monitoring — aligned with everything shipped in the portal since Sprint B/C.

---

## 2. Product role of admin (locked)

| Dimension | Decision |
|-----------|----------|
| **Who uses it** | WaybackRevive ops — super-admins only |
| **Not for** | Clients, agencies, self-serve DIY |
| **Primary job** | See truth → act fast → retain clients |
| **vs Portal** | Portal = reassurance for owners; Admin = full operational detail |
| **Tone** | Clear numbers, drill-down links, no vanity metrics |

**Rule:** Every dashboard stat must either (a) link to a filtered list, or (b) show a breakdown so totals reconcile.

---

## 3. Current inventory (verified)

### 3.1 What exists today

| Area | Filament resource/page | Path |
|------|------------------------|------|
| Dashboard | Default + 5 widgets | `/admin` |
| Clients | `ClientResource` | `/admin/clients` |
| Invites | `ClientInviteResource` | `/admin/client-invites` |
| Sites | `SiteResource` | `/admin/sites` |
| Subscriptions | `SubscriptionResource` | `/admin/subscriptions` |
| Invoices | `InvoiceResource` | `/admin/invoices` |
| Add-on orders | `AddonOrderResource` | `/admin/addon-orders` |
| Evaluations | `SiteEvaluationResource` | `/admin/site-evaluations` |
| Events | `EventResource` (list + view) | `/admin/events` |
| Reports | `ReportResource` | `/admin/reports` |
| Backups | `BackupResource` (list only) | `/admin/backups` |
| Command queue | `SiteCommandResource` | `/admin/site-commands` |
| Tickets | `TicketResource` | `/admin/tickets` |
| Settings | `PlatformSettingsPage` | `/admin/platform-settings-page` |

**Widgets:** `NeedsAttentionWidget`, `SiteHealthOverview`, `SiteEventsChart` (incidents), `ClientActivityChart`  
**Support:** `app/Support/AdminDashboard.php`, `app/Support/StripeDashboard.php`  
**Provider:** `app/Providers/Filament/AdminPanelProvider.php`

### 3.2 Remaining portal gaps in admin

| Portal feature | Model / service | Admin gap |
|----------------|-----------------|-----------|
| Uptime probes (7-day chart) | `SiteUptimeProbe` | Site relation only — no global list |
| Plugin snapshots | `PluginSnapshot` | **Unused in Filament** |
| Notification log | `NotificationLog` | **No resource** |
| Hosting credentials | `sites.hosting_credentials` | **Not visible** (encrypted) |

---

## 4. Truth metrics — what admin must see

### 4.1 Business health (top row — replace current 4 stats)

| Stat | Source (verified) | Breakdown required |
|------|-------------------|-------------------|
| **Sites** | `Site::count()` | Protected · Setup · Checkout · Attention · Down · Paused |
| **MRR / paying sites** | `Subscription::where(status, active)` per site | Not `Client::is_active` |
| **Uptime (7d)** | `SiteUptimeProbe` or `sites.uptime_7d` | Only **paid + monitoring on** sites |
| **Open ops** | Tickets open + unresolved critical events | Actionable count |

Use **`Site::portalStatusKey()`** for client-aligned status — same language as portal (`protected`, `setup`, `checkout`, `issue`, `down`, `paused`).

### 4.2 Status reconciliation (fix screenshot math)

```
Total sites = protected + setup + checkout + issue + down + paused + suspended
```

Current widget only shows `active + down + warning` — ignores `pending` and `suspended`. That is why **9 total ≠ 1 + 5**.

**Implementation:** `SiteHealthOverview` → replace with `BusinessOverviewWidget` using `portalStatusKey()` aggregation.

### 4.3 Charts that tell the truth

| Chart | Purpose | Data |
|-------|---------|------|
| **Incidents (14d)** | Critical + warning events | Keep, add click-through to Events |
| **Client activity (14d)** | `client_action` events | New — plan changes, settings, tickets |
| **New subscriptions (14d)** | `subscriptions.created_at` | Revenue signal ✅ A7 |
| **Probe failures (14d)** | `uptime_probe` down events | Monitoring truth ✅ A7 |

Do **not** mix client actions into incident chart — different mental model.

---

## 5. Target navigation (ops-first)

```
Dashboard                    ← truth metrics + action queue

── Clients & revenue ──
Clients                      ← sites count, paying sites, timezone, Stripe ID
Sites                        ← portal status, plan, sub, monitoring, uptime
Subscriptions                ← NEW: per-site Stripe status, period end
Invoices                     ← NEW: sync from Stripe, PDF link
Add-on orders                ← NEW: paid / in progress / done

── Pre-sales ──
Site evaluations
Client invites

── Monitoring & care ──
Events                       ← + type filter, message, view page
Backups                      ← + download action
Reports                      ← NEW: list + PDF download
Support tickets

── System ──
Platform settings
```

Remove duplicate sort (`Client Invites` and `Sites` both `navigationSort = 2`).

---

## 6. Resource improvements (by priority)

### Sprint A1 — Dashboard truth (quick wins) ✅ Shipped July 7, 2026

**Files:** `app/Support/AdminDashboard.php`, `app/Filament/Widgets/SiteHealthOverview.php`, `SiteEventsChart.php`, `ClientActivityChart.php`, `NeedsAttentionWidget.php`

- [x] Rename stats to match reality (“Paying sites” not “Paying clients”)
- [x] Full site status breakdown stat or secondary line (`portalStatusKey()`)
- [x] Use `config('app.tenant_id')` not hardcoded UUID
- [x] Link each stat to filtered Sites/Clients list (`->url()`)
- [x] Split events chart: incidents vs client activity
- [x] Add “Needs attention today” table widget:
  - Sites down (paid)
  - SSL/domain expiring < 30 days
  - Open tickets > 24h
  - Checkout abandoned (unpaid site > 7 days)

### Sprint A2 — Site ops alignment ✅ Shipped July 7, 2026

**Files:** `app/Filament/Resources/SiteResource.php`, `app/Models/Site.php` (scopes), `SiteResource/RelationManagers/*`

- [x] Portal status, plan, subscription, monitoring, probe, agent, uptime columns
- [x] Filters: portal status, plan, monitoring paused, SSL expiring, unpaid
- [x] Edit form: monitoring + billing read-only sections; probe status locked on edit
- [x] Relation managers: events, backups, reports, commands, probes (24h)

### Sprint A3 — Client ops alignment ✅ Shipped July 7, 2026

**Files:** `app/Filament/Resources/ClientResource.php`, `ClientResource/RelationManagers/*`, `app/Support/PortalAccess.php`, `AdminPortalAccessController`

- [x] Columns: timezone (in sites summary), paying site count, open tickets, Stripe customer ID (masked)
- [x] Replace single subscription badge with “X sites · Y paid”
- [x] Relation managers: Sites, Subscriptions, Invoices, Tickets
- [x] Portal link: signed 30-minute URL (`portal.admin-access`) — not generic `/portal/login`
- [x] Suspend: revokes portal only; monitoring + Stripe unchanged (documented in modal)

### Sprint A4 — Billing resources (NEW) ✅ Shipped July 7, 2026

| Resource | Path | Notes |
|----------|------|-------|
| `SubscriptionResource` | `/admin/subscriptions` | Client, site, plan, status, period end, canceled at, Stripe link |
| `InvoiceResource` | `/admin/invoices` | Sync from Stripe (header + per-row), receipt + Stripe links |
| `AddonOrderResource` | `/admin/addon-orders` | Client, site, addon, status, paid_at |

**Support:** `app/Support/StripeDashboard.php`, `InvoiceService::syncInvoicesForClient()` / `syncAllTenantInvoices()`

### Sprint A5 — Events & support depth

### Sprint A5 — Events & support depth ✅ Shipped July 7, 2026

**EventResource:**
- [x] View page with message + metadata JSON (`/admin/events/{id}`)
- [x] Filter by `type` and source (client vs system)
- [x] `message` column (truncated)
- [x] Source badge (Client / System)

**TicketResource:** client + site column links and row actions; dashboard queue ticket links (Client, Site, Ticket)

**BackupResource:** B2 signed download action (reuses `BackblazeService`)

### Sprint A6 — Reports & commands ✅ Shipped July 7, 2026

- [x] `ReportResource` — `/admin/reports`, filter by site/client, PDF download
- [x] `SiteCommandResource` — `/admin/site-commands`, global queue, active filter, nav badge

### Sprint A7 — Admin UI polish ✅ Shipped July 7, 2026

**Files:** All `app/Filament/Resources/*` navigation, `AdminPanelProvider.php`, `AdminDashboard.php`, dashboard widgets, `needs-attention.blade.php`, `ClientResource.php`

- [x] Ops-first navigation groups: Clients & revenue, Pre-sales, Monitoring & care, System
- [x] Unique `navigationSort` per resource (no duplicate Client Invites / Sites)
- [x] Remove Whop field from client form (Stripe-only product)
- [x] Est. MRR stat on dashboard (sum of active plan prices)
- [x] New subscriptions chart (14d) + probe failures chart (14d)
- [x] Needs attention widget: type badges, empty state, action buttons

---

## 7. Ops workflows (end-to-end)

### 7.1 New alumni client

```
Invite sent (ClientInviteResource)
→ Client activates (see onboarding_completed_at on Client)
→ Site connected (last_seen_at)
→ Stripe checkout (Subscription active)
→ Dashboard: site moves setup → protected
```

**Admin must see each step** — no site stuck in “setup” without visibility.

### 7.2 Site goes down

```
Probe fails (SiteUptimeService) → Event uptime_probe
→ Admin dashboard: down count + incident chart spike
→ Events list → Site relation → last probes
→ Optional: ticket auto-created (future)
```

### 7.3 Client changes plan

```
Portal plan change → StripeBillingService → Event client_action
→ Admin: client activity chart + invoice row
→ Subscription resource shows new plan + period
```

### 7.4 SSL/domain expiry

```
Daily job → warning event
→ Dashboard “attention” queue
→ Site list filter “SSL < 30 days”
```

### 7.5 Support ticket

```
Portal ticket → TicketResource (badge on nav)
→ Reply → email via NotificationService
→ Link to client sites + recent events
```

---

## 8. Known bugs to fix (verified)

| Bug | Location | Fix |
|-----|----------|-----|
| Dashboard totals don’t reconcile | `SiteHealthOverview` | Full `portalStatusKey()` breakdown |
| “Paying clients” = active accounts | `SiteHealthOverview` L48-50 | Count `Subscription::active` per site |
| `ViewAction` on events has no page | `EventResource` | Add ViewRecord page + infolist |
| Hardcoded tenant UUID | Widgets, SiteResource | Use `config('app.tenant_id')` |
| Plan change in admin form doesn’t sync Stripe | `SiteResource` form | Read-only plan or wire `StripeBillingService` |
| `monitoring_paused` invisible | Site model exists | Add column + filter |
| Client `hasOne` subscription | `ClientResource` | Per-site subs via relation manager |
| Whop field still in client form | `ClientResource` L54-57 | ~~Remove or hide (Stripe-only)~~ ✅ A7 |

---

## 9. Design principles (admin)

| Principle | Application |
|-----------|-------------|
| **Totals reconcile** | Every headline number breaks down to a list |
| **Same language as portal** | `portalStatusKey()` labels, not raw enums |
| **Drill-down everywhere** | Stat → filtered table → site edit → relations |
| **Read-only for client-owned** | Monitoring settings, billing — admin views, portal edits |
| **Action queue > vanity charts** | “What needs me today?” above charts |
| **Retention signals** | Churn risk: unpaid checkout, down sites, expiring SSL, silent clients |

---

## 10. Implementation order

| Sprint | Scope | Exit criteria |
|--------|-------|---------------|
| **A1** | Dashboard truth widgets | Stats reconcile; links work; labels honest |
| **A2** | Site resource enrichment | Portal status, monitoring, probes visible |
| **A3** | Client resource + relations | Per-site billing visible per client |
| **A4** | Subscription, Invoice, AddonOrder resources | Billing ops without Stripe dashboard only |
| **A5** | Events view + filters; backup download | Incidents debuggable in < 2 min |
| **A6** | Reports + commands | Full care loop visible |
| **A7** | Admin UI polish | Nav groups, MRR, charts, attention queue UX |

**Do not start A4 until A1–A2 ship** — dashboard truth first, then depth.

---

## 11. Success criteria

Admin ops is **ready for scale** when:

1. [x] Dashboard site counts sum to total — automated (`AdminDashboardTest`, `AdminOpsAcceptanceTest`)
2. [x] “Paying sites” matches Stripe active subscriptions count — automated
3. [ ] Any down paid site visible on dashboard within 2 minutes of probe — **manual / cron timing**
4. [x] Admin can open a site and see portal status, probes, tickets — automated; invoices via Client → Invoices tab
5. [ ] Client plan change in portal appears in admin within 1 page refresh — **manual Stripe webhook**
6. [x] No Filament resource contradicts portal status keys — `portalStatusKey()` used on Sites + dashboard
7. [x] Ops can answer client health from admin alone — Clients edit + relations + billing resources (A3–A4)

---

## 12. Key code locations

| Area | Path |
|------|------|
| Panel provider | `app/Providers/Filament/AdminPanelProvider.php` |
| Dashboard widgets | `app/Filament/Widgets/`, `app/Support/AdminDashboard.php` |
| Site portal status | `app/Models/Site.php` → `portalStatusKey()` |
| Uptime probes | `app/Services/SiteUptimeService.php`, `app/Models/SiteUptimeProbe.php` |
| Billing | `app/Services/StripeBillingService.php`, `app/Models/Subscription.php`, `Invoice.php` |
| Client activity | `app/Services/ClientActivityService.php` |
| Portal monitoring UI | `app/Livewire/Portal/SiteShow.php` (reference for parity) |

---

## 13. Document map

| Doc | Role |
|-----|------|
| `10_PORTAL_UX_MASTER_PLAN.md` | Client portal — shipped features & UX |
| **`11_ADMIN_OPS_MASTER_PLAN.md`** | **This file** — admin truth dashboard & ops |
| `07_CLIENT_PORTAL_SPEC.md` | Legacy — update after A2 |
| `09_DEPLOYMENT_GUIDE.md` | Production deploy |

---

## 14. Immediate next action

1. ~~**Implement A1**~~ — dashboard truth widgets shipped  
2. ~~**Implement A2**~~ — Site table + edit aligned with portal  
3. ~~**Implement A3**~~ — Client ops alignment  
4. ~~**Implement A4**~~ — Billing resources  
5. ~~**Implement A5**~~ — Events & support depth  
6. ~~**Implement A6**~~ — Reports & commands  
7. ~~**Implement A7**~~ — Admin UI polish  
8. **Production deploy** + manual QA checklist (§11)

---

*This plan is based on verified code audit of all 25 Filament files, live dashboard screenshot (9 sites / 1 active / 5 down discrepancy), and full portal feature set shipped through July 7, 2026.*
