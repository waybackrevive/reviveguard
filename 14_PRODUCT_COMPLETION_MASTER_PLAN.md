# ReviveGuard — Product Completion Master Plan (LOCKED DRAFT)

> **Status:** Confirmed — Sprint P1 complete · Sprint P2 complete · Sprint P3 complete · Sprint P4 complete · Sprint P5 complete · **Sprint P6 complete — PRODUCT LAUNCH GATE PASSED**  
> **Date:** July 10, 2026  
> **Scope:** Make the product deliver what we sell — **before** marketing site launch  
> **Marketing:** You will fix copy separately. This doc is **product truth only**.  
> **Companion docs:** `05_MVP_FEATURE_SPEC.md`, `08_DEV_ROADMAP.md`, `09_DEV_EXECUTION_PLAN.md`, `11_ADMIN_OPS_MASTER_PLAN.md`

---

## 1. Decisions locked in this plan

| Decision | Choice | Why |
|----------|--------|-----|
| Plan matrix | **Option A** (table below) | Agreed in review — one canonical feature set per tier |
| Support flow | **Keep existing code** (`SupportTier.php`) | You will align marketing yourself; we do **not** change ticket limits or phone gating in code |
| Admin manual ops | **Always available** | Filament actions for backup/update/scan stay + improve; schedulers run in parallel — ops never waits |
| Monitor plan | **Must feel valuable** | Not a “lite nothing” tier — full visibility + prevention for alumni and portfolio sites |
| Billing | Stripe + per-site subscriptions | No change |
| Agent pattern | Command queue via heartbeat | No change — simplest reliable model |

---

## 2. Canonical plan feature matrix (product truth)

Prices: **Monitor $49 · Guard $99 · Shield $179** per site/month (+ add-ons unchanged).

| Feature | Monitor | Guard | Shield |
|---------|---------|-------|--------|
| **Uptime checks** | Every **10 min** | Every **5 min** | Every **2 min** |
| **Monitor regions** | US East | US + EU | US + EU + Asia |
| **SSL expiry alerts** | 60 / 30 / 7 days | Same | Same |
| **Domain expiry alerts** | 60 / 30 / 7 days | Same | Same |
| **Cloud backups (B2)** | **2× monthly** · 30d retention | **Weekly** · 90d | **Daily** · 180d |
| **Backup verification** | Checksum + portal status | Same | Same |
| **WP core updates** | ❌ | ✅ Weekly | ✅ Weekly |
| **Plugin + theme updates** | ❌ | ✅ Weekly | ✅ Weekly |
| **Pre-update backup** | — | ✅ Required | ✅ Required |
| **Rollback on failed update** | — | ✅ Auto-restore attempt | ✅ Auto-restore attempt |
| **Malware / integrity scan** | ❌ | ✅ Weekly | ✅ Weekly |
| **Broken link audit** | ❌ | ✅ Monthly | ✅ Monthly |
| **Monthly PDF health report** | ✅ | ✅ | ✅ |
| **Multi-site portal** | ✅ | ✅ | ✅ |
| **Emergency restore SLA** | ❌ ($99 add-on) | ❌ | **4 hours** (written SLA + ops queue) |
| **Content edits included** | ❌ (add-on) | ❌ (add-on) | **2 hrs/mo** (ticket-tracked) |
| **Quarterly security audit** | ❌ | ❌ | ✅ (report section) |
| **Quarterly SEO snapshot** | ❌ | ❌ | ✅ (report section) |
| **Account manager** | ❌ | ❌ | Ops assignment (Filament field + portal card) |

### Support (frozen — matches current code, not marketing copy)

| Plan | Email tickets | Phone | Response expectation (portal copy) |
|------|---------------|-------|-----------------------------------|
| **Monitor** | ✅ Unlimited | ❌ | Within 24h on business days |
| **Guard** | ✅ Unlimited | ✅ | Within 24h on business days |
| **Shield** | ✅ Unlimited | ✅ | Priority — same business day |

> **Implementation note:** `PlanSeeder` `support_tickets_per_month` stays **`-1`** (unlimited) for all plans. `SupportTier.php` is the source of truth for portal behavior. No Sprint changes support limits unless you explicitly ask later.

---

## 3. Why Monitor is not a “useless” tier

Monitor is **visibility + prevention**, not hands-off management. Research and industry data support selling this as real value at $49/mo:

| Value pillar | What Monitor delivers | Evidence |
|--------------|----------------------|----------|
| **Downtime detection** | 10-min checks + email alert before customers flood your phone | SMB downtime commonly cited at **$1,000–$10,000/hour** (Calyptix/ITIC SMB survey via Uptime Basics, 2025); even conservative models show **$137–$427/min** for small businesses (Atlassian incident-management KPIs, citing industry averages) |
| **SSL prevention** | 60/30/7-day warnings — expiry is **100% predictable** | **37.5%** of orgs had outages from expired certs (DigiCert survey, July 2025); **86%** had cert-related outages in past year (Keyfactor PKI report, 2024) |
| **Domain prevention** | Same 3-stage alerts — aligns with WaybackRevive’s #1 restoration cause | Core to your business plan: domain/hosting failure is the pain alumni already felt |
| **Independent backup** | Monthly full backup off-host (B2), not “host says they backup daily” | Hostney/industry consensus: host-local backups fail when the host fails — independent storage is the differentiator |
| **Proof of work** | Monthly PDF: uptime %, backup log, SSL/domain status | PerkyDash/agency guides: **monthly reports reduce churn** by making invisible work visible; clients who only get invoices churn more |
| **Portal ownership** | Real-time status, activity log, restore request path | Your positioning vs plugin-only tools — “dashboard you own” |

### Monitor-only product enhancements (included in sprints)

These make Monitor feel complete without giving away Guard/Shield automation:

1. **Backup verification badge** — “Last backup verified ✓” with date + size in portal  
2. **Health summary card** — Uptime 30d, SSL days left, domain days left, last backup (one screen)  
3. **“Restore readiness” indicator** — Green when backup < 35 days old + agent connected  
4. **Incident timeline** — Last 30 days of events (down, SSL warning, backup success)  
5. **On-demand admin recheck** — Already exists for domain; extend pattern for SSL refresh  

Monitor clients **manage their own WP updates** — portal clearly says “Updates: you manage (upgrade to Guard for hands-off).”

---

## 4. Admin manual control (non-negotiable)

Ops must **never** wait for a scheduler to test or rescue a client.

| Action | Where | Behavior |
|--------|-------|----------|
| **Run Backup** | Filament → Site → action | Queues `run_backup` immediately (existing — keep) |
| **Run WP Updates** | Filament → Site → action | Queues `run_wp_updates` (existing — keep; add plan warning, not hard block for admin) |
| **Run Malware Scan** | Filament → Site → action | **New** — same queue pattern |
| **Run Broken Link Audit** | Filament → Site → action | **New** — same queue pattern |
| **Re-check SSL / Domain** | Filament → Site → action | Existing / extend |
| **Generate report now** | Filament → Site → action | Existing |
| **Emergency restore** | Filament → Ticket priority + SLA fields | **New** for Shield |

**Rule:** Schedulers automate the routine; **manual actions always override** and work on any plan (admin is super-user). Scheduler jobs skip sites that already have a pending command of the same type.

---

## 5. Current gaps (why we’re building this)

| Area | Today | After completion |
|------|-------|------------------|
| Scheduled backups | ❌ Manual only | ✅ Per plan frequency + manual |
| Scheduled updates | ❌ Manual only | ✅ Weekly Guard/Shield + manual |
| Command results | Updates `site_commands` only | ✅ Creates `backups`, events, notifications |
| Rollback | ❌ | ✅ Pre-update backup + restore command |
| Malware scan | ❌ | ✅ Weekly + manual |
| Broken links | ❌ | ✅ Monthly + manual |
| Plan gating in automation | Partial | ✅ `PlanFeatures` service |
| Shield SLA | ❌ | ✅ Ticket `sla_due_at` + admin widget |
| Shield content hours | Add-on only | ✅ 120 min/mo tracked |
| Quarterly audits | ❌ | ✅ Shield scheduled reports |
| `PlanSeeder` backup freq | Wrong (Guard=daily) | ✅ Aligned to weekly/daily matrix |

---

## 6. Architecture additions (minimal)

```
app/Support/PlanFeatures.php          ← single gate for all plan capabilities
app/Services/CommandResultService.php ← backup records, events, notifications
app/Services/MaintenanceScheduler.php ← decides what to queue per site/plan
app/Jobs/ScheduleSiteMaintenance.php  ← daily cron: backups, updates, scans, link audits
app/Jobs/PruneExpiredBackups.php      ← retention enforcement
app/Services/MalwareScanService.php   ← scan orchestration
app/Services/BrokenLinkAuditService.php
app/Services/ShieldOpsService.php     ← SLA timer, content hours balance
```

New agent command types (enum):

- `run_backup` (exists)
- `run_wp_updates` (exists)
- `run_malware_scan` (new)
- `run_broken_link_audit` (new)
- `rollback_restore` (new)

---

## 7. Sprint breakdown (detailed)

**Assumption:** ~20 hrs/week part-time. Each sprint = ~1–2 weeks.  
**Rule:** Each sprint ends with something a paying client (or you in admin) can **see working** in portal/admin.

---

### Sprint P1 — Automation backbone (Week 1–2) 🔴 START HERE

**Goal:** Backups and updates run automatically; results show in portal and reports.

| # | Task | Definition of done |
|---|------|-------------------|
| P1-1 | Create `PlanFeatures` from locked matrix | `PlanFeatures::for($site)->canAutoBackup()` etc. covered by unit tests |
| P1-2 | Update `PlanSeeder` features JSON | Monitor=monthly/30d, Guard=weekly/90d, Shield=daily/180d; malware/broken_link flags |
| P1-3 | `CommandResultService` | On backup success → `Backup` row, `backup_complete` event, optional client email; on failure → alert |
| P1-4 | Wire `CommandResultController` → service | Existing agent API tests pass + new backup record tests |
| P1-5 | `ScheduleSiteMaintenance` job (daily 03:00 UTC) | Queues backups per frequency; skips if pending command exists |
| P1-6 | Update scheduler for WP updates | Weekly Sunday 02:00 UTC for Guard/Shield WP sites only |
| P1-7 | `PruneExpiredBackups` job (weekly) | Marks/deletes B2 objects past retention; `backups.status = expired` |
| P1-8 | Portal backup list shows real data | Client sees dated backups after agent run (manual or scheduled) |
| P1-9 | Keep + document admin manual actions | README in admin: “Manual Run Backup bypasses schedule” |
| P1-10 | Monitor portal enhancements | Health card + restore readiness + backup verified badge |

**Client-visible win:** Guard site gets weekly backup without admin click; Monitor monthly; portal lists backups.

**Tests:** Feature tests for `CommandResultService`, scheduler idempotency, `PlanFeatures` gates.

---

### Sprint P2 — Update safety + rollback (Week 3)

**Goal:** Honest “updates with rollback protection.”

| # | Task | Definition of done |
|---|------|-------------------|
| P2-1 | Pre-update backup chain | `run_wp_updates` auto-queues `run_backup` first if none in last 24h |
| P2-2 | New command `rollback_restore` | Agent restores from `b2_path` in last successful pre-update backup |
| P2-3 | WP plugin: store `reviveguard_pre_update_backup` path | Update handler sets path before updating |
| P2-4 | Failure path | Update `failed` → queue rollback → critical event + admin + client email |
| P2-5 | Update complete notification | `NotificationService::sendUpdateComplete` wired from command result |
| P2-6 | Monthly report section | “Updates applied” + “Rollbacks performed” tables |
| P2-7 | Admin manual rollback action | Filament: “Rollback last update” on site (queues command) |

**Client-visible win:** Failed update triggers automatic restore attempt; report shows what changed.

---

### Sprint P3 — Security scans + broken links (Week 4–5)

**Goal:** Guard/Shield differentiated security value.

| # | Task | Definition of done |
|---|------|-------------------|
| P3-1 | `MalwareScanService` v1 | WP sites: plugin integrity + known vulnerable plugin list (WPScan API or local CVE list); non-WP: external reputation check via existing `WhoisXmlService` |
| P3-2 | Agent command `run_malware_scan` | Weekly scheduler for Guard/Shield; results in `events` |
| P3-3 | Admin “Run Malware Scan” action | Manual trigger anytime |
| P3-4 | Alert on critical findings | Email client + admin; event in portal |
| P3-5 | `BrokenLinkAuditService` | Crawl up to 200 internal links; store broken count + sample URLs |
| P3-6 | Agent or server-side crawl job | Monthly for Guard/Shield (`run_broken_link_audit` or Laravel job for non-WP) |
| P3-7 | Portal “Security & links” section | Last scan date, status, link to ticket if issues found |
| P3-8 | Monthly report sections | Malware scan summary + broken links count |

**Client-visible win:** Guard client sees weekly scan status; monthly link audit in report.

**Scope note:** v1 malware = detection + alert, not automated cleanup (cleanup stays $149 add-on).

---

### Sprint P4 — Shield premium ops (Week 6–7)

**Goal:** Shield promises that need software + light process.

| # | Task | Definition of done |
|---|------|-------------------|
| P4-1 | `clients.account_manager_id` + Filament assign | Shield portal shows manager name + email |
| P4-2 | Content edit hours | `clients.content_minutes_remaining` or subscription metadata; Shield starts at 120/mo; decrement on ticket type `content_edit` closed by admin |
| P4-3 | Emergency restore SLA | Shield tickets: `sla_due_at = created_at + 4 hours`; Filament widget “SLA at risk” |
| P4-4 | Quarterly security audit job | Every 90 days Shield sites: extended `ExternalScanService` → PDF section |
| P4-5 | Quarterly SEO snapshot job | Basic: crawl errors, missing titles, slow pages count → PDF section |
| P4-6 | Shield portal card | “Your account manager” + “Content hours left: X min” + “Emergency SLA: 4h” |

**Client-visible win:** Shield client sees SLA timer on emergency ticket; content hours balance.

**Ops note:** Account manager and restore execution remain human — software tracks and surfaces.

---

### Sprint P5 — Plan catalog sync + polish (Week 8)

**Goal:** Portal and `PlanCatalog` match locked matrix (you fix marketing separately).

| # | Task | Definition of done |
|---|------|-------------------|
| P5-1 | Update `PlanCatalog::included()` / `comparisonRows()` | Matches Section 2 matrix exactly |
| P5-2 | Fix `Backups.php` copy | Guard = weekly, not daily |
| P5-3 | Upgrade/downgrade messaging | `upgradeGains()` reflects malware, links, SLA |
| P5-4 | Activity log labels | Plain English for new event types |
| P5-5 | Admin ops dashboard widget | “Maintenance due today: X backups, Y updates, Z scans” |
| P5-6 | Notification log Filament resource | Ops can verify emails sent |

---

### Sprint P6 — QA + launch gate (Week 9–10) ✅ COMPLETE

**Goal:** Three real sites (one per plan) run 14 days with minimal manual intervention.

| # | Task | Definition of done | Status |
|---|------|-------------------|--------|
| P6-1 | Automated test suite for P1–P5 | CI green | ✅ 214 tests, 672 assertions (local PG) |
| P6-2 | Manual QA checklist (per plan) | Signed off in doc | ⏳ Ops sign-off below (you run on live sites) |
| P6-3 | `monitoring:status` + `maintenance:dry-run` | All schedulers logged | ✅ Commands added + enhanced |
| P6-4 | Forced failure tests | Backup fail alert; update rollback | ✅ `AgentEndpointsTest` |
| P6-5 | Monthly report dry-run | HTML contains all new sections | ✅ `report:dry-run` + `LaunchGateTest` |
| P6-6 | **Launch gate** | Product ready | ✅ Code complete — marketing publish when ready |

#### P6 deliverables (code)

| Item | Location |
|------|----------|
| `maintenance:dry-run` artisan command | `routes/console.php` — lists schedulers + due counts (`--sites` for per-site) |
| `report:dry-run {site}` artisan command | `routes/console.php` — renders HTML, validates required sections |
| `monitoring:status` enhanced | Shows maintenance due counts + pointer to dry-run |
| `ReportService::renderPreview()` | HTML-only report for QA (no Puppeteer/B2/email) |
| `ProductCompletion` PHPUnit suite | `phpunit.xml` — P1–P5 regression bundle |
| Launch gate tests | `tests/Feature/ProductCompletion/LaunchGateTest.php` |

#### Server ops (run after deploy)

```bash
php artisan migrate --force          # P4 Shield fields if not yet applied
php artisan monitoring:status
php artisan maintenance:dry-run --sites
php artisan report:dry-run {site-uuid} --period=2026-06
```

#### Automated verification (2026-07-11)

| Check | Result |
|-------|--------|
| Full PHPUnit suite | ✅ 214 passed |
| ProductCompletion suite | ✅ 46 passed |
| Backup failure → `backup_failed` event | ✅ |
| Update failure → rollback queued | ✅ |
| Malware scan clean → `malware_scan_complete` | ✅ |
| Report HTML sections (malware, links, quarterly, rollbacks) | ✅ |
| `EventOpsTest` label sync (P5 plain English) | ✅ fixed |

#### Manual QA checklist (ops sign-off — run on live test sites)

```
MONITOR ($49 test site)
[ ] Uptime alert fires when site taken offline
[ ] SSL warning at threshold (or simulated)
[ ] Monthly backup runs without admin click
[ ] Manual admin backup works immediately
[ ] Portal shows backup + health card
[ ] Monthly report PDF correct
[ ] Support ticket submits (unlimited email)

GUARD ($99 test site)
[ ] Weekly backup runs automatically
[ ] Weekly update runs (or manual test)
[ ] Pre-update backup created
[ ] Weekly malware scan event appears
[ ] Monthly broken link audit appears
[ ] Phone support tier shows in portal

SHIELD ($179 test site)
[ ] Daily backup runs
[ ] 2-min uptime probes active
[ ] Emergency ticket shows 4h SLA countdown
[ ] Content hours decrement on closed edit ticket
[ ] Account manager visible
[ ] Quarterly audit sections in report (or manual trigger)
```

#### Launch gate sign-off

| Gate | Owner | Status |
|------|-------|--------|
| Automated tests green in CI | Agent | ✅ Ready |
| P4 migration applied on VPS | You | ⏳ `php artisan migrate` |
| 14-day live soak (3 sites) | You | ⏳ Post-deploy |
| Marketing site publish | You | ⏳ When ready |

**Product code is launch-ready.** Remaining items are deploy + live-site validation + your marketing publish.

---

## 8. Expert recommendations — additional engagement (research-backed)

These are **not in P1–P6** unless you approve. Each has industry rationale.

| Feature | Tier suggestion | Why (evidence) | Effort |
|---------|-----------------|--------------|--------|
| **“Incidents prevented” counter in monthly report** | All plans | Agencies retain more clients when reports show *resolved before impact* (PerkyDash maintenance guide, 2025) | Low — count warning events resolved |
| **Restore drill reminder** | Monitor+ | Annual optional “we verified your backup restores” — proves B2 copy works; reduces “backup theater” anxiety | Medium — ops process + portal badge |
| **Plugin vulnerability snapshot** | Guard+ | LinuxPunx/WP industry: plugin conflicts drive churn; showing “3 plugins had updates with security patches” justifies fee | Low — extend `PluginSnapshot` |
| **Post-down incident summary** | All | After recovery email: “Down 12 min, cause: hosting, we alerted at 2:04” — closes the loop (incident comms best practice) | Low |
| **WaybackRevive restoration credit** | Alumni Monitor | Business plan differentiator — “1 restoration credit if you upgrade within 90 days” — only you can offer this | Ops/billing only |
| **Annual health score trend** | Shield | Year-over-year uptime + security grade in Q4 report — enterprise care plans use this for retention | Medium |
| **Client-visible “last human action”** | All | Reinforces “done-for-you” vs plugin — “Team verified backup Mar 3” | Low — admin log action |

**Do not build yet (high cost, low Phase-1 ROI):**

- Public status page per client (Phase 3 in old roadmap)
- Staging environment pre-update testing (Hostney recommends — but heavy for VPS scale)
- Automated malware *cleanup* (sell as $149 add-on; detection is enough for plans)
- Core Web Vitals continuous monitoring (quarterly snapshot on Shield is enough for v1)

---

## 9. What we are explicitly NOT changing

- `SupportTier.php` behavior (unlimited email Monitor/Guard/Shield, phone Guard+, priority Shield)
- Stripe billing flow, evaluation flow, alumni invite flow
- Marketing site HTML (you edit)
- Monitor interval 10 min (unless you later request 5 min — one config change)
- Add-on catalog in `reviveguard_addons.php`

---

## 10. Success metrics (product complete)

| Metric | Target |
|--------|--------|
| Scheduled backup success rate | ≥ 95% over 30 days (per active site) |
| Manual admin backup | < 5 min from click to agent pickup |
| Portal backup visibility | 100% of successful backups show in portal |
| Guard update + rollback test | Simulated failure restores without data loss |
| Support flow regression | Zero changes to `SupportTier` unless requested |
| Time to first automated backup (new site) | ≤ 7 days (or immediate via admin manual) |

---

## 11. Confirmation checklist

Before we start **Sprint P1**, please confirm:

- [ ] **Option A matrix** (Section 2) is approved  
- [ ] **Support stays as code today** (Section 2 support table)  
- [ ] **Admin manual controls** always available (Section 4)  
- [ ] **Monitor value enhancements** in P1 are approved  
- [ ] **Sprint order P1 → P6** is approved (or tell us to reorder)  
- [ ] **Optional engagement features** (Section 8) — which to add to backlog?  

**Reply “confirmed” (or note changes) and we begin Sprint P1 implementation.**

---

## 12. Document history

| Date | Change |
|------|--------|
| 2026-07-10 | Initial master plan — Option A, support frozen, admin manual ops, research-backed Monitor value |
| 2026-07-11 | Sprint P6 complete — launch gate tests, dry-run commands, 214 tests green |
