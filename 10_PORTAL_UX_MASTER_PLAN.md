# ReviveGuard — Portal UX Master Plan (Final)

> **Status:** Locked direction — implement in order below  
> **Audience:** Naqeeb (product owner), developers, AI agents  
> **Supersedes:** Outdated sections of `07_CLIENT_PORTAL_SPEC.md` (nav, dashboard, card layout)  
> **Last updated:** July 6, 2026  
> **Inspiration reference:** WP Umbrella layout density and fleet table — **not** their DIY tool model

---

## 1. Executive summary

ReviveGuard is a **done-for-you** website protection service (not a self-serve maintenance tool). The portal must feel like **agency-grade infrastructure operated by humans** — calm, scannable, zero jargon — for non-technical business owners and (later) freelancers/agencies managing client sites.

**What we built (Sprint A/C — shipped):** Honest status logic, Sites as home, welcome wizard, client labels, ReviveGuard branding shell.

**What is still wrong (your live screenshot):** The Sites page uses **stacked fat cards** with repeated yellow banners, empty metric grids (`—`), confusing actions (`Access`, tiny link row), and visual noise. It reads like a debug dashboard, not WP Umbrella’s clean **fleet table**.

**Next UX sprint (C2):** Replace card list with **one table + one primary action per row**. Move detail to site drill-down. Remove duplicate CTAs and empty-state columns.

---

## 2. Product positioning (locked)

| Dimension | Decision |
|-----------|----------|
| **Who pays** | Site manager — solo owner, alumni, or agency (same product) |
| **What we sell** | Per-site monthly care: Monitor $49 / Guard $99 / Shield $179 |
| **Differentiator** | Done-for-you + WaybackRevive trust — not cheapest dashboard |
| **vs WP Umbrella** | They sell tools for agencies to DIY; we sell **outcomes** with a visible dashboard |
| **GTM order** | Alumni first → evaluation inbound → agencies (Phase 2) |
| **Onboarding** | Three-speed: trust alumni, evaluate strangers, automate agencies (`01_BUSINESS_PLAN.md` §3b) |
| **Billing** | Stripe (Sprint B) — remove Whop entirely |

---

## 3. User roles & mental models

### 3.1 Site manager (portal user)

One login = one **workspace** (name from welcome wizard). They may own 1 site or 50.

| Sub-type | Examples | Portal needs |
|----------|----------|----------------|
| **Solo alumni** | Restored WaybackRevive client | Minimal UI, one site, “is it okay?” |
| **Solo new** | Evaluation-approved owner | Same UI after invite |
| **Freelancer** | 5–20 client sites | Table, client labels, fast scan |
| **Agency** | 20+ sites | Table, filters, client labels (Phase 2: team) |

**Rule:** Never build separate products per sub-type. Same IA; density scales with site count.

### 3.2 Admin (Filament)

Internal ops: evaluations, invites, manual backup, tickets. **Queue-first**, not mirror of client UI.

### 3.3 End-client (Phase 2+)

Agencies may never give their clients portal access. Optional read-only sub-portal later — **not Phase 1**.

---

## 4. WP Umbrella inspiration matrix

Study their screenshots as **layout and density** reference only.

### 4.1 Adopt (layout & behavior)

| Pattern | Umbrella | ReviveGuard adaptation |
|---------|----------|------------------------|
| **Fleet table** | Sites as rows, not cards | Primary Sites view = `<table>` |
| **Toolbar** | Search + filter chips + sort | One row above table |
| **Status at a glance** | Column per concern | Status · Uptime · SSL · Backup · Plan |
| **Row click** | Opens site workspace | Click row → `/portal/sites/{id}` |
| **Site workspace** | Left sub-nav: Uptime, Plugins, Backups | Sub-nav: Overview, Activity, Backups, Connection, Plan |
| **Onboarding** | Role cards + workspace name | Already built — keep, polish copy |
| **Whitespace** | Calm, not cramped | DM Sans, 10px radius, subtle borders |
| **Single primary CTA** | “+ Add site” top-right | Sidebar + toolbar only — no bottom promo card |

### 4.2 Reject (wrong business model)

| Umbrella feature | Why not in client portal |
|------------------|--------------------------|
| Bulk plugin update UI | We perform updates — client doesn’t |
| Performance / PageSpeed scores | Phase 2+; confuses non-technical users now |
| “4 pending updates” as user action | Show “Managed by ReviveGuard” (Guard/Shield) or hide column |
| API keys, team, labels (MVP) | Phase 2 agency features |
| PHP issues / risk scores | Admin/evaluation only — never client-facing |
| Technical column headers | “Last heartbeat” → “Last checked” |

---

## 5. Current UI diagnosis (from live portal)

Your screenshot (`5 sites`, `0 protected`, `4 setup`, `1 issue`) exposes these **specific failures**:

| Problem | Why it confuses non-technical users |
|---------|-------------------------------------|
| **Card stack** | 5 sites = 5 large blocks; endless scroll; no scan pattern |
| **Repeated yellow banners** | Same “One step left” ×4 feels broken and alarming |
| **Metric row full of `—`** | Looks broken; wastes 40% of card height |
| **“We're on it” without context** | Scary red border + no explanation on list view |
| **“Access” button** | Means hosting credentials — users think “access my site” |
| **Tiny text links** | Open site / Alerts / Reports — low affordance |
| **Bottom “Have another website?”** | Duplicates sidebar “+ Add site” |
| **4 stat cards + filter + cards** | Three layers of summary before data |
| **Plan badge on row + checkout banner** | Mixed payment states unclear |

**Root cause:** We reskinned the old card layout without changing **information architecture** to match Umbrella’s table-first fleet view.

---

## 6. Information architecture (final)

### 6.1 Global sidebar (Phase 1)

```
[+ Add site]          ← only green button in nav

Sites                 ← HOME (default after login)
Alerts                ← fleet-wide notification feed
Reports               ← all PDFs, filter by site
Support               ← tickets
─────────────────
Billing               ← plans, invoices, Stripe portal
Sign out

Operated by WaybackRevive LLC
```

**Removed from nav:** Dashboard (merged), My Websites (merged), Backups (per-site only), Account (merged into Billing).

### 6.2 Sites list (HOME) — **target layout**

```
┌─────────────────────────────────────────────────────────────────────────┐
│ Sites                                    [+ Add site]                    │
│ 5 sites · 0 protected · 4 need setup · 1 needs attention               │  ← one line, not 4 cards
├─────────────────────────────────────────────────────────────────────────┤
│ [Search...]  [All ▾] [Setup needed] [Protected] [Attention]   Sort ▾      │
├──────┬────────────┬──────────┬───────┬────────┬───────┬────────┬──────┤
│      │ Site       │ Status   │ Uptime│ SSL    │ Backup│ Plan   │      │
├──────┼────────────┼──────────┼───────┼────────┼───────┼────────┼──────┤
│ 🌐   │ Joe's Site │ Setup    │  —    │ 74 days│  —    │ Guard  │  →   │
│      │ joes.com   │ needed   │       │        │       │        │      │
├──────┼────────────┼──────────┼───────┼────────┼───────┼────────┼──────┤
│ ...  │            │          │       │        │       │        │      │
└──────┴────────────┴──────────┴───────┴────────┴───────┴────────┴──────┘
```

**Row rules:**
- **Entire row clickable** → site workspace
- **Status column** = single pill (Setup needed | Protected | Needs attention | We're on it | Complete payment)
- **No per-row yellow banners** on list view — status column is enough
- **Empty metrics** show `—` in table cells only (compact), not 4-column mini-grid per card
- **Checkout rows** show one orange “Complete payment” button in actions column — not full-width banner
- **Remove** “Access” from list — move to site → Connection tab (admin-only credentials)

**When 0 sites:** Full-page empty state with single CTA (no table headers).

**When 1 site:** Option A (recommended): still show table with one row — consistent. Option B: auto-open site overview — already implemented; **disable for table era** or keep as user preference later.

### 6.3 Site workspace (`/portal/sites/{id}`)

**Header:** Client label (if set) · site name · URL · status pill · plan badge

**Sub-nav (vertical or horizontal tabs):**

| Tab | Purpose | Non-technical copy |
|-----|---------|-------------------|
| **Overview** | Status, uptime bar, SSL/domain cards, last backup, “what we did this month” | Default tab |
| **Activity** | Site-filtered events | “What happened” |
| **Backups** | History + “Request restore” → support ticket | “Your backup copies” |
| **Reports** | Site PDFs only | “Monthly reports” |
| **Connection** | Plugin install guide, token (masked), test connection | “Connect your site” — **setup sites land here** |
| **Plan** | This site’s plan, upgrade, add-ons (post-Stripe) | “Your coverage” |

**Setup-needed sites:** After add-site, redirect to **Connection** tab with step-by-step guide (not list banners).

### 6.4 Alerts (was Activity Log)

- Fleet-wide, reverse chronological
- Plain titles: “Site was unreachable”, “Backup completed”, “SSL renewal due in 7 days”
- Filter: All / Attention / Good news
- Click → site workspace

### 6.5 Billing

- Table: Site | Plan | Price | Next bill date
- Invoices list with download
- “Manage payment method” → Stripe Customer Portal
- No Whop links

### 6.6 Support

- Simple form + ticket list
- Plan limits enforced (Monitor: contact sales; Guard: 1/mo; Shield: unlimited)

---

## 7. User flows (strategic)

### 7.1 Alumni fast-track

```
Invite email → set password → Welcome wizard (30s)
→ Add site (URL + optional client label)
→ Connection tab (install plugin — copy token, video/link)
→ Live “Waiting for connection…” → “Connected ✓”
→ Choose plan → Stripe Checkout
→ Overview tab shows Protected
```

### 7.2 Evaluation (stranger)

```
Marketing /evaluate → admin review → proposal email
→ Accept → same flow as alumni from Welcome wizard onward
```

### 7.3 Add another site (returning)

```
+ Add site → URL + label → Connection → Plan/Stripe → back to Sites table
```

### 7.4 Agency (Phase 2)

Same flow; table scales; client label column prominent; optional “Clients” grouping later.

---

## 8. Status system (client-facing language)

| Internal | Client sees | List color | When |
|----------|-------------|------------|------|
| `pending` (unpaid) | **Complete payment** | Amber | Before Stripe |
| No `last_seen_at` | **Setup needed** | Gray | Plugin not connected |
| `active` + connected | **Protected** | Green | Happy path |
| `warning` | **Needs attention** | Amber | SSL/domain soon |
| `down` + was connected | **We're on it** | Red | Real outage — we’re working |
| `suspended` | **Paused** | Gray | Billing issue |

**Never show “Down” on list for setup state.** Logic already in `Site::portalStatusKey()` — UI must match.

---

## 9. Design system (ReviveGuard brand)

| Token | Value |
|-------|--------|
| Primary | `#0A7A3E` (brand green) |
| Primary hover | `#086332` |
| Success surface | `#E8F5EE` |
| Warning | `#D97706` |
| Issue | `#DC2626` |
| Pending/setup | `#6B7280` |
| Font | DM Sans (Google Fonts) |
| Card/table radius | 10px |
| Shadow | `0 1px 3px rgba(0,0,0,.06)` |
| Logo mark | `RG` shield tile + Revive**Guard** wordmark |

### 9.1 Tone

- Calm > urgent (unless real issue)
- Short sentences
- No: heartbeat, agent, token, tar.gz, B2, HTTP, plugin list
- Yes: connection, protection, backup copy, secure cloud storage, our team

### 9.2 Density

- Prefer **one line per site** in table (~56–64px row height)
- Max **one** summary strip under page title (not 4 stat cards)
- Max **one** primary CTA per screen region

---

## 10. Onboarding wizard (polish — already built)

Keep split layout. Refinements:

- After complete → open **Add site** modal/page, not inline wizard in list
- Remove WP “Authorize us / manual credentials” from client wizard Step 1 for MVP — **plugin-only connection** on Connection tab (credentials = admin request via support)
- Step order: **URL → Plan → Stripe → Connection** (pay then connect, or connect then pay for alumni invite-with-site — decide per path)

**Recommended order for new sites:**

1. URL + client label  
2. Connection (plugin) — can be “pending” while they pay  
3. Plan selection  
4. Stripe Checkout  

Alumni with pre-created site from invite: skip URL → Connection → Plan → Stripe.

---

## 11. What to remove (noise audit)

| Remove from Sites list | Reason |
|------------------------|--------|
| 4 summary stat cards | Replace with one text summary line |
| Per-card metric grid | Table columns suffice |
| Per-card yellow setup banners | Status column + Connection tab |
| Bottom “Have another website?” card | Sidebar CTA enough |
| “Access” on list row | Move to Connection (rename “Hosting login details”) |
| Inline wizard embedded in list | Dedicated `/portal/sites/add` route or modal |
| “Alerts” and “Reports” text links per row | Row click → site; global nav for fleet |

---

## 12. Implementation sprints (remaining)

### Sprint B — Stripe (billing, blocking revenue)

- [ ] `laravel/cashier` + Stripe products/prices  
- [ ] Remove Whop (service, webhooks, settings, wizard checkout)  
- [ ] Checkout Session on add-site / resume payment  
- [ ] Billing page + Customer Portal link  
- [ ] Invoice webhook  

**Exit:** Client can pay $49/$99/$179 via Stripe.

### Sprint C2 — Fleet table UX (this document’s core)

- [ ] Replace `my-websites.blade.php` card loop with Livewire table component  
- [ ] Single-line summary under title  
- [ ] Filter chips (not only dropdown)  
- [ ] Row click → site workspace  
- [ ] Setup sites: redirect to Connection tab  
- [ ] Remove bottom promo + list banners  
- [ ] Mobile: table → stacked compact rows (not full cards)  
- [ ] Polish `site-show.blade.php` with sub-nav tabs  

**Exit:** Non-technical user can scan 10 sites in &lt;5 seconds.

### Sprint C3 — Add-site flow cleanup

- [ ] Dedicated add-site route (full page, not inline)  
- [ ] Simplify wizard steps per §10  
- [ ] Connection guide component (download plugin, token, copy, FAQ)  

### Sprint D — Admin queue UX (Filament)

- [ ] Dashboard widget: Needs setup | Evaluations | Issues  
- [ ] Evaluation → proposal flow unchanged but faster UI  

### Sprint E — Agency (Phase 2, post 5–10 clients)

- [ ] Client grouping column / filter  
- [ ] Team seats  
- [ ] Volume pricing  

---

## 13. Success criteria (definition of done)

Portal UX is **ready for alumni launch** when:

1. [ ] New user completes Welcome → Add site → Connect plugin → Pay → sees **Protected** without contacting support  
2. [ ] Sites list readable in one screen for 10 sites (table, not cards)  
3. [ ] Zero instances of “Down” for plugin-not-connected sites  
4. [ ] No repeated full-width banners on list view  
5. [ ] Every user-facing string passes jargon rule (§9.1)  
6. [ ] Stripe billing works end-to-end  
7. [ ] Mobile: usable without horizontal scroll on list  

---

## 14. Document map (keep in sync)

| Doc | Role |
|-----|------|
| `01_BUSINESS_PLAN.md` | Model, pricing, three-speed onboarding |
| `05_MVP_FEATURE_SPEC.md` | In/out scope — update billing to Stripe |
| `07_CLIENT_PORTAL_SPEC.md` | **Update after C2** to match this plan |
| `10_PORTAL_UX_MASTER_PLAN.md` | **This file** — source of truth for portal UX |
| `.cursor/rules` | Agent behavior + locked stack |

---

## 15. Immediate next action

**Do not start random UI tweaks.** Implement in order:

1. **Sprint B** (Stripe) — revenue path  
2. **Sprint C2** (fleet table) — your screenshot problems  
3. **Sprint C3** (add-site + connection guide)  

Confirm with product owner, then implement C2 wireframes in code.

---

*This plan synthesizes: business model pivot (site managers / B2B-ready), WP Umbrella layout inspiration, done-for-you differentiation, shipped Sprint A/C work, live portal feedback, and alumni-first GTM.*
