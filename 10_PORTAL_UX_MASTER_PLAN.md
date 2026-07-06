# ReviveGuard — Platform & Portal Master Plan

> **Status:** Living document — reflects production as of **July 7, 2026**  
> **Audience:** Product owner, developers, AI agents, new team members  
> **Production:** `app.reviveguard.com` · Laravel 11 + Livewire portal + Filament admin  
> **Supersedes:** Outdated sections of `07_CLIENT_PORTAL_SPEC.md` (nav, billing, monitoring)

---

## 1. Executive summary

ReviveGuard is a **done-for-you** WordPress protection SaaS — not a DIY maintenance tool. Clients pay **per site/month** (Monitor $49 · Guard $99 · Shield $179). The portal answers one question: **“Is my site okay?”**

**Company:** WaybackRevive LLC (US). **Clients:** global — display times in client timezone; store UTC server-side.

### What is live today

| Area | Status |
|------|--------|
| Stripe billing (checkout, portal, invoices, plan change) | ✅ Shipped |
| Fleet sites table (not cards) | ✅ Shipped |
| Site workspace (7 tabs incl. Monitoring) | ✅ Shipped |
| Built-in HTTP uptime probes + plan-gated intervals | ✅ Shipped |
| SSL + domain expiry (daily jobs) | ✅ Shipped |
| Client timezone preference | ✅ Shipped |
| Add-ons Stripe checkout | ✅ Shipped |
| Support tickets | ✅ Shipped |
| Monthly PDF reports | ✅ Shipped |
| WP agent (heartbeat, backups, SSO) | ✅ Shipped |
| Filament admin ops panel | ✅ Shipped |
| Whop billing | ❌ Removed — Stripe only |

### What remains (priority order)

1. **Multi-region probe execution** — `monitor_region` is UI-only today; all HTTP checks run from app server  
2. **Portal feature tests** — billing Livewire, addons, tickets, SiteShow tabs (see §12)  
3. **Mobile fleet table polish** — compact rows on small screens  
4. **Agency features** — client grouping, team seats (Phase 2)  
5. **Evaluation → portal** flow hardening in Filament  

---

## 2. Product positioning (locked)

| Dimension | Decision |
|-----------|----------|
| **Who pays** | Site manager — solo owner, alumni, or agency |
| **What we sell** | Per-site monthly care: Monitor / Guard / Shield |
| **Differentiator** | Done-for-you + WaybackRevive trust |
| **vs WP Umbrella** | They sell DIY tools; we sell **outcomes** with a calm dashboard |
| **GTM** | Alumni first → evaluation inbound → agencies (Phase 2) |
| **Billing** | Stripe only — one subscription per site |

---

## 3. Architecture overview

```
┌─────────────────────────────────────────────────────────────────┐
│  Marketing site (reviveguard.com)  →  /evaluate, pricing      │
└────────────────────────────┬────────────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│  Client Portal (/portal) — Livewire + portal.auth guard           │
│  Sites · Alerts · Reports · Add-ons · Support · Billing           │
└────────────────────────────┬────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        ▼                    ▼                    ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────────┐
│ Stripe        │   │ WP Agent API  │   │ Scheduler (UTC)   │
│ webhooks      │   │ heartbeat     │   │ probes 2min       │
│ checkout      │   │ backups       │   │ SSL/domain daily  │
│ invoices      │   │ SSO           │   │ reports monthly   │
└───────────────┘   └───────────────┘   └───────────────────┘
                             │
┌────────────────────────────▼────────────────────────────────────┐
│  Filament Admin (/admin) — evaluations, invites, site ops        │
└─────────────────────────────────────────────────────────────────┘
```

**Stack:** Laravel 11, Livewire 3, Filament 3, PostgreSQL, Stripe, Backblaze B2, optional Uptime Kuma.

**Time:** `APP_TIMEZONE=UTC`. Client `timezone` column → portal display via `ClientTimezone` helper.

---

## 4. Portal routes & pages

Prefix: `/portal`. Middleware: `portal.auth`, `portal.timeout`, `portal.onboarded`.

| Route | Component | Purpose |
|-------|-----------|---------|
| `/portal/sites` | `MyWebsites` | **Home** — fleet table, search/filter, resume checkout |
| `/portal/sites/add` | `AddSite` → `SiteWizard` | URL → connection → plan → Stripe |
| `/portal/sites/{site}` | `SiteShow` | Site workspace (tabs below) |
| `/portal/alerts` | `Events` | Fleet-wide event feed |
| `/portal/reports` | `Reports` | Monthly PDF downloads |
| `/portal/addons` | `Addons` | One-time add-on orders |
| `/portal/tickets` | `Tickets` | Support tickets |
| `/portal/billing` | `Account` | Profile, timezone, plans, invoices, Stripe portal |
| `/portal/welcome-setup` | `WelcomeWizard` | First-login onboarding |

**Redirects:** `/dashboard`, `/my-websites`, `/backups`, `/account` → canonical routes above.

---

## 5. Site workspace tabs (`SiteShow`)

| Tab | Purpose | Gated by payment |
|-----|---------|------------------|
| **Overview** | Uptime 30d, SSL/domain days, last backup, recent activity | Partial |
| **Monitoring** | Uptime chart (7d), SSL/domain expiry, settings, incidents | Paid only |
| **Activity** | Site audit log | No |
| **Backups** | Backup history | Paid |
| **Reports** | Site PDFs | Paid |
| **Connection** | Plugin guide, token, test connection | No |
| **Plan** | Compare plans, upgrade/downgrade modal | No |

**Monitoring tab (current — minimal layout):**

1. Compact bar: Monitor interval · Region · Save · Pause  
2. Four cards: Currently up for · Uptime (30d) · SSL expires · Domain expires  
3. **Uptime rate — last 7 days** (daily bar chart)  
4. Incident timeline (client timezone on timestamps)  

Timezone link: settings row → Account profile.

---

## 6. Sites list (fleet table) — ✅ Sprint C2 done

- **Table rows**, not stacked cards (`my-websites.blade.php`)
- Columns: Site · Status · Uptime · SSL · Domain · Backup · Plan · Actions
- Search + status filter chips
- Row click → site workspace
- Inline: WP Admin SSO, Complete payment, Remove (unpaid only)
- Summary line under title (not 4 stat cards)

---

## 7. Billing & Stripe — ✅ Sprint B done

| Feature | Implementation |
|---------|----------------|
| New site checkout | `StripeBillingService::createCheckoutSession()` |
| Checkout success | `CheckoutSuccessController` + webhook |
| Plan upgrade | Prorated charge today (`always_invoice`) |
| Plan downgrade | Credit on next invoice |
| Plan change UI | Modal on `SiteShow` + `Account` (`plan-change-modal` blade) |
| Invoices in portal | `InvoiceService` sync from Stripe |
| Payment method | Stripe Customer Portal link |
| Add-ons | One-time Checkout → `AddonOrder` |
| Onboarding job | `OnboardClientJob` after first payment |

**Plans:** Monitor $49 · Guard $99 · Shield $179 per site/month.

---

## 8. Monitoring — ✅ shipped (with known gap)

### HTTP uptime probes (primary)

| Plan | Min interval | Allowed intervals |
|------|--------------|-------------------|
| Monitor | 10 min | 10, 30 |
| Guard | 5 min | 5, 10, 30 |
| Shield | 2 min | 2, 5, 10, 30 |

- Scheduler: `ProbeSiteUptime` **every 2 minutes** (skips sites not due per `monitor_interval_minutes`)
- Storage: `site_uptime_probes` table
- Incidents: `uptime_probe` events on down/up transitions
- Portal: 7-day daily chart, 30d uptime %, pause/resume monitoring

### SSL & domain

- Daily jobs: `CheckSslExpiry` (06:00), `CheckDomainExpiry` (07:00)
- Alerts at 60 / 30 / 7 days
- Portal: separate expiry cards on Overview + Monitoring

### Agent heartbeat

- `CheckMissedHeartbeats` every 5 min (30-min threshold)
- Skips if recent HTTP probe shows site up

### Optional: Uptime Kuma

- Webhook + `UpdateUptimeStats` every 6h — supplementary to built-in probes

### Known gap

**`monitor_region`** is saved and shown in UI but **not used** in probe execution — all checks from app server until multi-region workers ship.

### Timezone

- `ClientTimezone` — 13 zones, default US Eastern
- Account profile dropdown saves `clients.timezone`
- Monitoring incidents + chart grouped by client TZ

---

## 9. Add-ons, tickets, reports, alerts

| Feature | Route | Notes |
|---------|-------|-------|
| **Add-ons** | `/portal/addons` | Config: `reviveguard_addons.php`, Stripe one-time |
| **Tickets** | `/portal/tickets` | Plan-gated priority; Shield = high |
| **Reports** | `/portal/reports` + site tab | Monthly PDF via Backblaze signed URL |
| **Alerts** | `/portal/alerts` | Fleet `Event` feed with filters |

---

## 10. Admin (Filament `/admin`)

Super-admin only. Key resources: Clients, Sites, Events, Tickets, Backups, Invites, Evaluations.

**Site ops:** regen token, trigger backup, WP updates, generate report, domain re-check.

**Platform settings:** Stripe keys/prices, email, Backblaze, monitoring integrations.

---

## 11. Agent API (`/api/v1/agent/*`)

Bearer token per site. Endpoints: `heartbeat`, `command-result`, `plugin-list`, `event`, `sso-consume`.

Webhooks: Stripe, Uptime Kuma.

---

## 12. QA & test status (July 7, 2026)

**Automated:** 97 tests passing (`php artisan test`).

| Area | Tested | Gap |
|------|--------|-----|
| Agent API | ✅ | — |
| Portal auth/routes | ✅ smoke | No billing/addons/tickets Livewire tests |
| Plan catalog / billing helpers | ✅ | No Stripe webhook integration tests |
| MonitorSettings / SiteUptimeChart | ✅ | No ProbeSiteUptime job tests |
| Filament routes | ✅ smoke | No action tests |
| ClientTimezone | ✅ | — |

**Manual QA checklist (production):**

- [ ] Login → welcome wizard → add site → connect plugin → pay → Protected status  
- [ ] Sites table scan (10+ sites)  
- [ ] Monitoring tab: 7-day chart, SSL/domain dates, pause/resume  
- [ ] Plan upgrade (prorated charge) + downgrade (credit message)  
- [ ] Invoices appear after sync on Billing tab  
- [ ] Timezone change reflects on incident timestamps  
- [ ] Add-on checkout completes  
- [ ] Support ticket submits  
- [ ] Report PDF downloads  
- [ ] WP Admin SSO opens  
- [ ] Stripe test mode banner when applicable  

---

## 13. Status system (client language)

| Internal | Client sees | When |
|----------|-------------|------|
| Unpaid | **Complete payment** | Before Stripe |
| No connection | **Setup needed** | Plugin not connected |
| Active + paid | **Protected** | Happy path |
| Warning | **Needs attention** | SSL/domain soon |
| Down (was connected) | **We're on it** | Real outage |
| Suspended | **Paused** | Billing issue |

Logic: `Site::portalStatusKey()` — never show Down for never-connected sites.

---

## 14. Design system

| Token | Value |
|-------|--------|
| Primary | `#0A7A3E` |
| Font | DM Sans |
| Radius | 10px cards, subtle borders |
| Tone | Calm, short sentences, no jargon on client UI |

**Monitoring UI principle:** Minimal and scannable — four metric cards + 7-day chart + incidents. No engineer tables (HTTP ms, probe logs) on default view.

---

## 15. User flows

### Alumni fast-track

```
Invite → password → Welcome wizard → Add site → Connection tab
→ Plugin connected → Plan → Stripe → Overview shows Protected
```

### Plan change (existing site)

```
Site → Plan tab OR Billing → Compare → Modal confirms charge/credit
→ Stripe updates → active immediately
```

### Monitoring

```
Paid site → Monitoring tab → auto probes every N min
→ down event → email alert → incident in timeline
```

---

## 16. Sprint tracker — plan vs reality

### ✅ Done (was in original plan)

| Sprint / item | Status |
|---------------|--------|
| Sprint B — Stripe billing | ✅ |
| Sprint C2 — fleet table | ✅ |
| Sprint C3 — add-site route + connection guide | ✅ |
| Honest status logic | ✅ |
| Welcome wizard | ✅ |
| Collapsible sidebar nav | ✅ |
| Monitoring settings (interval/region/pause) | ✅ |
| Per-check probe data + 7-day chart | ✅ |
| Plan-gated monitor intervals | ✅ |
| Plan change modal + proration | ✅ |
| Invoice sync in portal | ✅ |
| Client timezone | ✅ |
| Add-ons portal | ✅ |
| Favicon | ✅ |

### 🔶 Partial

| Item | Notes |
|------|-------|
| Multi-region probes | UI only |
| Mobile table | Works but not optimized stacked rows |
| Jargon audit | Mostly done; “Agent” still on monitoring card |
| `07_CLIENT_PORTAL_SPEC.md` sync | Not updated |

### ❌ Not started (Phase 2)

| Item | Notes |
|------|-------|
| Agency client grouping | — |
| Team seats | — |
| Performance/PageSpeed scores | Out of scope MVP |
| End-client sub-portal | Phase 2 |
| Evaluation queue UX polish | Basic Filament exists |

---

## 17. Key code locations

| Area | Path |
|------|------|
| Portal routes | `routes/portal.php` |
| Sites list | `app/Livewire/Portal/MyWebsites.php` |
| Site workspace | `app/Livewire/Portal/SiteShow.php` |
| Billing | `app/Services/StripeBillingService.php`, `InvoiceService.php` |
| Monitoring probes | `app/Services/SiteUptimeService.php`, `app/Jobs/ProbeSiteUptime.php` |
| Intervals | `app/Support/MonitorSettings.php` |
| Chart | `app/Support/SiteUptimeChart.php` |
| Timezone | `app/Support/ClientTimezone.php` |
| Plan modal | `resources/views/components/portal/plan-change-modal.blade.php` |
| Scheduler | `routes/console.php` |
| Deploy | `.github/workflows/deploy.yml` |

---

## 18. Document map

| Doc | Role |
|-----|------|
| `01_BUSINESS_PLAN.md` | Business model, pricing, GTM |
| `05_MVP_FEATURE_SPEC.md` | Scope in/out |
| `07_CLIENT_PORTAL_SPEC.md` | Legacy spec — update to match this file |
| `09_DEPLOYMENT_GUIDE.md` | Production deploy steps |
| **`10_PORTAL_UX_MASTER_PLAN.md`** | **This file** — platform context & status |
| `.cursor/rules` | Agent coding rules |

---

## 19. Immediate next actions

1. Deploy latest `master` and run manual QA checklist (§12)  
2. Consider hiding “Agent X ago” on monitoring card (jargon) or rename “Plugin last seen”  
3. Implement multi-region probes OR hide region dropdown until ready  
4. Add portal billing Livewire feature tests before alumni scale  

---

*Last verified: July 7, 2026 — 97 tests green, monitoring chart 7 days, minimal monitoring UI restored.*
