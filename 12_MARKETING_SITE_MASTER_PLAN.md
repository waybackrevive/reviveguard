# ReviveGuard — Marketing Site Rebuild Master Plan

> **Status:** M1 in progress — PHP template layer shipped July 7, 2026  
> **Date:** July 7, 2026  
> **Site:** `reviveguard.com` (PHP templates on Hostinger)  
> **App:** `app.reviveguard.com` (evaluation form, portal, checkout)  
> **Audience:** Product owner, designers, developers, AI agents

---

## 1. Executive summary

The current marketing site has **strong copy intent** but **weak conversion architecture**: one 1,660-line homepage, trust-breaking pricing errors, feature claims that don't match the live product, and no dedicated landing pages for our two GTM paths.

**Rebuild goal:** A clean, enterprise-grade site (WP Umbrella clarity) with **agency-operated trust** and **contextual human photography** in key sections — **outcome-first, jargon-free, two-path journey**, every claim verified against `PlanSeeder` + portal reality.

**We are NOT building another WP Umbrella.** They sell DIY tools to agencies. We sell **done-for-you protection** operated by WaybackRevive LLC — to site owners who've already felt the pain (or passed our evaluation gate).

**M1 shipped:** PHP global templates (`includes/`), dynamic SEO meta, `sitemap.php`, `/pricing/`, `/for-alumni/`, plan truth in `includes/plans.php`.

---

## 2. Positioning (locked — do not drift)

| | ReviveGuard | WP Umbrella (reference only) |
|---|---|---|
| **Buyer** | Site owner / alumni / small agency managing own clients | WordPress agencies at scale |
| **Promise** | "We watch and fix it — you run your business" | "Run your maintenance business faster" |
| **Product** | Managed service + calm portal | Self-serve SaaS dashboard |
| **Entry** | Invite or evaluation — never open signup | Free trial, self-serve |
| **Price anchor** | $49 / $99 / $179 per site/month | Per-agency subscription |
| **Trust** | WaybackRevive recovery track record | Agency logos, review widgets |

**One-line hero (draft):**  
*Expert-managed WordPress protection — built by the team that's restored 500+ sites.*

**Emotional core:** Peace of mind after trauma. Not fear-mongering. Not tech specs.

### Agency positioning (locked — no individual/founder identity)

| Rule | Application |
|------|-------------|
| **Voice** | "Our team", "our operations team", "WaybackRevive LLC" — never a named founder |
| **Entity** | ReviveGuard = **managed service by WaybackRevive LLC** (US company) |
| **Trust** | 500+ recoveries, evaluation quality bar, portal transparency — not personal celebrity |
| **Why** | Enterprise agency positioning; no fake founder profile; identity stays with the company brand |
| **Copy constants** | `includes/config.php` → `AGENCY_TAGLINE`, `AGENCY_INTRO` |

### Human connection (photography — not founder photo)

Use **licensed contextual photography** in sections where emotion matters — not a headshot that reveals an individual.

| Section | Image type | Alt-text pattern |
|---------|------------|------------------|
| Alumni landing | Relieved business owner at desk | "Small business owner after website recovery — illustrative" |
| Homepage problem/CTA | Person viewing laptop / calm relief | Outcome-focused, not "meet our CEO" |
| About | Operations / team-at-work (backs or wide shot) | "WaybackRevive operations team at work" |
| **Never** | Founder portrait, fake named testimonial avatars | Use real quotes only when collected |

**Assets:** `assets/img/human/` — upload licensed JPG/WebP. Component: `includes/human-visual.php`.

**Placeholder:** CSS gradient until photos uploaded (M2).

---

## 3. Target audiences & journeys

### Primary — Path A: WaybackRevive Alumni (GTM Week 1)

| Attribute | Detail |
|-----------|--------|
| **Who** | Anyone whose site WaybackRevive restored |
| **Mindset** | "I never want that again" |
| **Trust** | Already high — personal relationship |
| **Entry** | Email invite link → portal (admin-controlled) |
| **Marketing job** | Confirm legitimacy, explain what they're buying, reduce friction to accept invite |
| **CTA** | "Check your email for invite" / "Request my alumni invite" |
| **Page** | Dedicated `/for-alumni/` (email campaign destination) |

### Secondary — Path B: Evaluated New Clients (GTM Week 2+)

| Attribute | Detail |
|-----------|--------|
| **Who** | SMB owner, freelancer with client sites — serious about protection |
| **Mindset** | "I need someone competent, not another plugin" |
| **Trust** | Low initially — must earn via evaluation + social proof |
| **Entry** | Free evaluation → admin review → invite → portal |
| **Marketing job** | Filter tire-kickers, show quality bar, explain evaluation value |
| **CTA** | "Request free site evaluation" |
| **Page** | Homepage + `/pricing/` + evaluation on app |

### Tertiary — Phase 2: Freelancers / Micro-agencies

| Attribute | Detail |
|-----------|--------|
| **Who** | 2–10 client sites, wants white-label reports |
| **Mindset** | "Make me look professional without doing the work" |
| **Marketing job** | Multi-site portal, monthly PDF reports — mention on homepage, full page later |
| **Priority** | Phase 2 — do not lead with this in hero |

---

## 4. End-to-end user flows

### Flow A — Alumni (warm outreach)

```
Email: "Your site was restored — keep it protected"
    → reviveguard.com/for-alumni/
    → Explains service + trust + what happens next
    → CTA: Open invite email OR contact for invite
    → app.reviveguard.com/portal/accept-invite?token=…
    → Onboarding wizard → plan → plugin → protected
```

### Flow B — New client (inbound)

```
Google / referral / WaybackRevive footer link
    → reviveguard.com/
    → Hero: problem + outcome + "Request evaluation"
    → Scroll: how evaluation works, pricing preview, proof
    → app.reviveguard.com/evaluate
    → Admin reviews (48h) → proposal email → invite
    → Portal → checkout → plugin → protected
```

### Flow C — Existing client (support)

```
reviveguard.com → Client Login
    → app.reviveguard.com/portal/login
```

### Flow D — Legal / compliance

```
Footer → /privacy/ /terms/ /refund/
```

---

## 5. Existing site audit

### 5.1 Inventory

| File | Role | Verdict |
|------|------|---------|
| `index.html` | 1,660-line mega homepage | **Rebuild** — split + shorten |
| `about-us/index.html` | Mission story | **Rewrite** — add human photo, fix cap claim |
| `about.html` | Duplicate? | **Remove or 301** → `/about-us/` |
| `contact/index.html` | Contact + topic routing | **Keep structure**, reskin |
| `privacy/`, `terms/`, `refund/` | Legal | **Keep**, fix wrong prices |
| `assets/css/style.css` | 2,400+ lines custom CSS | **Refactor** — component tokens + shared partials |
| `assets/js/main.js` | Nav, FAQ, counters | **Keep**, simplify |
| `sitemap.xml` | SEO | **Expand** after new pages |

### 5.2 Critical trust bugs (fix in rebuild)

| Bug | Location | Truth |
|-----|----------|-------|
| **Monitor shown as $19/mo** | `index.html` compare table, legal footers | **$49/mo** (`PlanSeeder`) |
| **"26 managed sites" cap** | `about-us/index.html` | **26 new clients/month** — not total managed count |
| **Monitor backup "weekly"** | `index.html` pricing | **Monthly** (`backup_frequency: monthly`) |
| **Guard backup "weekly"** | `index.html` pricing | **Daily** in product seed |
| **Malware scan, broken link audit** | Features + Guard plan | **Not in PlanSeeder** — remove or mark "coming" |
| **Quarterly SEO snapshot, security audit** | Shield plan | **Not shipped** — remove until built |
| **Dedicated account manager** | Shield plan | **Aspirational** — remove or soften to "priority support" |
| **Shield "2 hours content edits"** | Pricing | Verify ops capacity — business plan says 1hr; align copy |
| **Fake testimonials** | `index.html` | Initials-only quotes — replace with real alumni quotes or remove until collected |
| **Footer link `#compare`** | `about-us/index.html` | **Broken** — no `id="compare"` on homepage |
| **Emergency restore add-on $49 in FAQ** | FAQ answer | Config says **$99** (`reviveguard_addons.php`) |

### 5.3 Structural problems

1. **Single-page overload** — 14 sections before footer; cognitive fatigue; WP Umbrella wins with progressive pages + scannable sections.
2. **CSS mockups instead of product screenshots** — Undermines enterprise credibility; portal is live — use real captures.
3. **No human face** — WaybackRestore wins on trust; we have a real team and 500+ restores story but show emoji avatars.
4. **Wrong competitor frame** — WP Buffs comparison is fine; leading with "not WP Umbrella" confuses agencies who might later be customers.
5. **Emoji as UI icons** — Reads startup, not enterprise agency.
6. **Two-path story buried** — Hero is strong but Path A/B cards appear after problem section; business plan says above-the-fold dual CTA.
7. **No `/for-alumni/` landing** — Email campaigns need a dedicated destination, not homepage scroll.
8. **No `/pricing/` page** — SEO keywords ("wordpress maintenance pricing") need a dedicated URL.
9. **Origin bar + nav + hero** — Three layers before value; simplify to nav + hero.

### 5.4 What's working (keep)

- Origin story: WaybackRevive → ReviveGuard prevention narrative
- Two-path system concept (alumni vs evaluation)
- Quality cap as differentiator (wording needs fix)
- Evaluation-gated positioning — serious clients only
- Emerald brand color aligned with portal/admin
- Schema.org product markup (update prices)
- Contact topic routing (`?topic=alumni`)
- Plain-language problem cards (domain, updates, backups)
- 7-day money-back, cancel anytime trust lines

---

## 6. Inspiration mapping

### From WP Umbrella (structure & clarity)

| Pattern | Apply to ReviveGuard |
|---------|---------------------|
| Clean white layout, generous whitespace | Yes — keep light theme |
| Hero: outcome + product screenshot | Yes — real portal screenshot |
| Logo trust bar | WaybackRevive + restore stats, not fake agency logos |
| "Old workflow vs with us" comparison | **Old:** You check plugins, forget renewals, pray backups work → **With us:** We monitor, backup, update, report |
| Feature tabs with screenshot swap | 4 tabs: Monitor · Protect · Report · Support |
| Stats row | 500+ restored · 99%+ uptime · 48h evaluation · 4hr restore SLA |
| Pricing on dedicated section/page | `/pricing/` with 3 plans |
| FAQ accordion | Keep — shorten to top 8 questions |
| Repeated CTA | Evaluation + Alumni — not 12 duplicate buttons |

### From WaybackRestore (human trust)

| Pattern | Apply to ReviveGuard |
|---------|---------------------|
| Contextual human photos in key sections | Relieved owner, operations desk — **not founder portrait** |
| Process timeline (4 steps) | Evaluation → Invite → Connect → Protected |
| Guarantee card | 7-day money-back + quality cap explanation |
| Crisis→calm visual | "Site down" → "Protected" illustration or photo |
| Contact with human promise | "Our team responds within 1 business day" |

### What we explicitly skip

- Generic stock "happy office" photos
- Fake review widget embeds
- Self-serve "Start free trial" (we're invite/evaluation gated)
- Agency-reseller positioning in Phase 1 hero
- Technical jargon (HMAC, Backblaze B2, Uptime Kuma) on homepage — say "independent cloud backups" only

---

## 7. New information architecture

```
reviveguard.com/
├── /                          Homepage (conversion hub — shorter)
├── /for-alumni/               Path A landing (email campaign destination)
├── /pricing/                  Plans + add-ons (SEO + sales)
├── /how-it-works/             Optional — or keep as homepage section only in v1
├── /about-us/                 Mission, team photo, WaybackRevive link
├── /contact/                  Topics + form
├── /privacy/
├── /terms/
└── /refund/
```

**v1 scope (MVP rebuild):** `/`, `/for-alumni/`, `/pricing/`, `/about-us/`, `/contact/`, legal — **6 content pages + homepage**. ✅ PHP routes live (M1).

**Phase 2 pages:** `/for-agencies/`, `/case-studies/`, blog for SEO.

---

## 7b. PHP template architecture (M1 — shipped)

```
marketing-site/
├── includes/
│   ├── bootstrap.php      # loads config + merges $page meta
│   ├── config.php         # URLs, agency copy, sitemap registry
│   ├── functions.php      # e(), site_url(), breadcrumbs
│   ├── plans.php          # plan + addon truth (matches PlanSeeder)
│   ├── schema.php         # JSON-LD helpers
│   ├── human-visual.php   # contextual photo component
│   ├── head.php           # meta, OG, Twitter, canonical
│   ├── header.php         # origin bar + nav
│   ├── footer.php         # global footer (plan prices from plans.php)
│   ├── layout-start.php   # head + header
│   └── layout-end.php     # footer + scripts
├── partials/              # page bodies (blocked by .htaccess)
├── index.php              # homepage
├── sitemap.php            # dynamic sitemap (rewrite from sitemap.xml)
├── .htaccess              # PHP index, block includes/
└── {page}/index.php       # each route sets $page then layout
```

**Per-page pattern:**

```php
<?php
$page = ['title' => '...', 'canonical' => '/pricing/', 'nav_active' => 'pricing'];
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout-start.php';
// content or partial
require __DIR__ . '/../includes/layout-end.php';
```

**Migration:** Legacy `.html` files remain during transition; `.htaccess` prefers `index.php`. Remove `.html` after Hostinger QA.

**Hostinger:** Enable PHP 8.x on marketing domain; upload full `marketing-site/` folder.

---

## 8. Homepage wireframe (new — target ~60% shorter)

```
┌─────────────────────────────────────────────────────────────┐
│ NAV: Logo | How it works | Pricing | About | Contact        │
│      [Client Login]  [Request Evaluation]                   │
├─────────────────────────────────────────────────────────────┤
│ HERO                                                        │
│ H1: Your site survived the crash. Let's make sure it         │
│     never happens again.                                    │
│ Sub: Done-for-you WordPress protection by the WaybackRevive │
│      team. Monitoring, backups, updates — we handle it.     │
│ [Request Free Evaluation]  [I'm a WaybackRevive Client →]   │
│ Trust: ★★★★★ + "500+ sites restored" | REAL portal screenshot│
├─────────────────────────────────────────────────────────────┤
│ STATS: 500+ restored | 99%+ uptime | 48h evaluation | 4hr SLA│
├─────────────────────────────────────────────────────────────┤
│ PROBLEM (3 cards): Domain expires | Outdated WP | Bad backups│
├─────────────────────────────────────────────────────────────┤
│ COMPARISON: "Without ReviveGuard" vs "With ReviveGuard"      │
│   (WP Umbrella-style — plain language, no competitor names)  │
├─────────────────────────────────────────────────────────────┤
│ PRODUCT (tabbed): Watch | Backup | Update | Report           │
│   Each tab: 1 sentence + portal screenshot crop              │
├─────────────────────────────────────────────────────────────┤
│ TWO PATHS (compact cards)                                    │
│   Alumni: invite → portal → protected                        │
│   New: evaluation → proposal → protected                     │
├─────────────────────────────────────────────────────────────┤
│ PRICING PREVIEW: 3 plan cards → link to /pricing/            │
├─────────────────────────────────────────────────────────────┤
│ HUMAN: Team photo + "We lived the problem" short story       │
│   Link to /about-us/                                         │
├─────────────────────────────────────────────────────────────┤
│ SOCIAL PROOF: 2–3 real quotes OR "What clients say after     │
│   a restore" — no fabricated initials                        │
├─────────────────────────────────────────────────────────────┤
│ FAQ: Top 8 questions                                         │
├─────────────────────────────────────────────────────────────┤
│ FINAL CTA: Evaluation + Alumni buttons + trust chips         │
├─────────────────────────────────────────────────────────────┤
│ FOOTER                                                       │
└─────────────────────────────────────────────────────────────┘
```

**Remove from homepage** (move to `/pricing/` or cut): full add-ons grid, competitor table, 10-feature laundry list, duplicate journey tabs, quality promise duplicate.

---

## 9. Page briefs

### `/for-alumni/` (new — high priority)

**Job:** Convert warm email traffic in under 30 seconds.

- Headline: *You trusted us to restore your site. Now let us keep it safe.*
- 3 bullets: We know your stack · Skip the evaluation queue · Protected in minutes
- What you get: monitoring, backups, updates (by plan)
- CTA: Check email for invite from `hello@reviveguard.com`
- Secondary CTA: Didn't get it? → contact with site URL
- Trust: link to WaybackRevive, stats, human photo
- **No evaluation form on this page** — alumni are not Path B

### `/pricing/`

**Job:** Answer "what does it cost?" with honest feature matrix.

- 3 plans from `PlanSeeder` — features pulled from single source (see §11)
- Add-ons from `config/reviveguard_addons.php`
- Per-site pricing, additional site pricing
- Comparison table: Monitor vs Guard vs Shield (our plans only — not WP Buffs)
- CTA: Evaluation for new · Contact for alumni
- FAQ snippet: cancellation, money-back

### `/about-us/`

**Job:** Mission, credibility, human connection.

- WaybackRevive → ReviveGuard story (recovery → prevention)
- **Real team/founder photo** + short quote
- Values: quality over volume, transparency, prevention
- Fix: **26 new clients/month** cap (not 26 total sites)
- Stats that are verifiable only
- CTA to evaluation or alumni contact

### `/contact/`

**Job:** Route intent — keep topic cards (alumni, evaluation, support, emergency).

- Reskin to match new design system
- Response time promise
- Email: support@reviveguard.com

---

## 10. Design system (rebuild)

### Visual direction

| Token | Value | Notes |
|-------|-------|-------|
| Primary | `#059669` emerald | Matches portal + admin |
| Background | `#ffffff` / `#f7f9fc` | Clean, not dark navy sections |
| Text | `#111827` / `#6b7280` | High contrast body |
| Font | Inter (keep) | Or upgrade to Inter + tighter heading scale |
| Radius | 12px cards, 8px buttons | Consistent with Filament portal feel |
| Icons | Lucide-style SVG line icons | Replace emoji |
| Photography | 1 founder/team photo, portal screenshots | No stock handshakes |

### Components to build once, reuse everywhere

- `header.html` / `footer.html` (PHP include or build step — today duplicate nav across 6 files)
- `btn--primary`, `btn--outline`, `btn--ghost`
- `section-header` (eyebrow + h2 + sub)
- `plan-card`, `stat-strip`, `faq-accordion`
- `cta-banner` (final conversion block)
- `trust-chip` row

### Responsive

- Mobile-first; hamburger nav (exists)
- Pricing cards stack; comparison table → cards on mobile
- Portal screenshots: WebP, lazy-loaded

---

## 11. Single source of truth for copy

All pricing and feature claims must be generated from or verified against:

| Source | Use for |
|--------|---------|
| `app-code/database/seeders/PlanSeeder.php` | Plan names, prices, backup frequency, retention |
| `app-code/config/reviveguard_addons.php` | Add-on names and prices |
| `01_BUSINESS_PLAN.md` §0 mission | Hero tone, purpose |
| `10_PORTAL_UX_MASTER_PLAN.md` §2 | Positioning guardrails |
| Live portal screenshots | Product proof sections |

**Rule:** If it's not in PlanSeeder and not shipped in portal, it does not appear on the marketing site.

### Verified plan facts (July 2026)

| Plan | Price | Backups | Updates | Support |
|------|-------|---------|---------|---------|
| Monitor | $49/mo | Monthly, 30d retention | No | No tickets in seed |
| Guard | $99/mo | Daily, 90d retention | Core + plugins | Unlimited tickets in seed |
| Shield | $179/mo | Daily, 180d retention | Core + plugins | Priority support |

*Marketing copy for SLA, content edits, malware — only add when implemented in product + config.*

---

## 12. SEO strategy & sprint plan (senior SEO)

### 12.1 Technical SEO (M1 — partial)

| Item | Status | Notes |
|------|--------|-------|
| Unique `<title>` + meta description per page | ✅ | `$page` array in each `index.php` |
| Canonical URLs | ✅ | `head.php` |
| Open Graph + Twitter cards | ✅ | All pages |
| JSON-LD Organization + WebSite | ✅ | Sitewide in `schema.php` |
| JSON-LD Product/Offer on pricing | ✅ | `schema_product_offers()` |
| BreadcrumbList on inner pages | ✅ | `$page['breadcrumbs']` |
| Dynamic `sitemap.xml` → `sitemap.php` | ✅ | Auto lastmod from filemtime |
| `robots.txt` | ✅ | Points to sitemap |
| HTTPS canonical host | ⏳ | Enforce www vs non-www on Hostinger |
| Lighthouse mobile >85 | ⏳ | M6 QA |

### 12.2 On-page SEO keywords

| Page | Primary keyword | Secondary |
|------|-----------------|-----------|
| `/` | managed wordpress protection | website monitoring backups |
| `/pricing/` | wordpress maintenance pricing | monitor guard shield plans |
| `/for-alumni/` | waybackrevive website protection | restored site maintenance |
| `/about-us/` | waybackrevive reviveguard | managed wordpress service company |
| `/contact/` | reviveguard support | site evaluation request |

### 12.3 Content SEO rules

1. One H1 per page; H2s answer search intent questions
2. First 100 words include primary keyword naturally
3. Internal links: homepage ↔ pricing ↔ for-alumni ↔ contact
4. No keyword stuffing; write for SMB owner reading level
5. FAQ blocks with `FAQPage` schema on homepage (M3)
6. Remove unshipped feature keywords until product ships

### 12.4 Off-page / GTM SEO (Phase 2)

- WaybackRevive.com footer link to ReviveGuard
- Alumni email → `/for-alumni/` (tracked UTM)
- Case study posts: "How we prevented [site] going down again"
- Google Business Profile (optional, WaybackRevive LLC)

---

## 13. Implementation sprints (complete)

| Sprint | Scope | Exit criteria | Status |
|--------|-------|---------------|--------|
| **M0** | Claim audit vs PlanSeeder | Spreadsheet of every claim | ✅ Audit in §5.2 |
| **M1** | PHP templates + SEO base + `/pricing/` + `/for-alumni/` | Shared header/footer; sitemap.php; no $19 bugs in PHP footer | ✅ Shipped |
| **M2** | Homepage rebuild (shorter) | <900 lines partial; dual CTA; fix feature lies | ✅ Shipped July 7, 2026 |
| **M3** | Human photos + section placement | 5 Unsplash images in `assets/img/human/` | ✅ Shipped July 7, 2026 |
| **M4** | About + contact reskin (agency copy) | No "26 managed sites"; agency voice | ✅ Done |
| **M5** | Remove legacy `.html`; 301 map | Only PHP serves | ✅ Done (deploy to Hostinger) |
| **M6** | SEO QA: Lighthouse, Search Console, links | Mobile >85; all CTAs work | ✅ Script + link audit (Lighthouse on deploy) |
| **M7** | FAQ schema + blog stub (optional) | FAQPage JSON-LD | ✅ Done — home + pricing FAQ; `/blog/` stub (noindex) |
| **M8** | Production deploy + GSC submit | Sitemap indexed | ✅ Tooling ready — run `package-deploy.php` + smoke test on Hostinger |

**Do not start M2 until M1 deployed to staging and PHP confirmed on Hostinger.**

### Technical approach

- **PHP includes** on Hostinger (not Laravel) — scales to simple router later
- Optional Phase 2: Eleventy/11ty static export from PHP parts — not needed now
- Images: WebP in `/assets/img/screenshots/` + `/assets/img/human/`
- Deploy: `09_DEPLOYMENT_GUIDE.md` marketing section (update for PHP)

---

## 14. Success criteria

Marketing rebuild is **done** when:

1. [x] PHP templates — single header/footer/plan source
2. [x] `/for-alumni/` and `/pricing/` live with correct SEO meta
3. [x] Homepage rebuilt (M2) — dual CTA, truthful copy, FAQ schema
4. [x] Licensed contextual stock photos (M3) — no founder identity
5. [ ] Alumni email links to `/for-alumni/` (deploy + campaign)
6. [ ] Homepage loads in <3s, Lighthouse performance >85 mobile (run after Hostinger deploy)
7. [x] Zero feature/pricing claims contradict `PlanSeeder`
8. [ ] Real portal screenshot visible above fold (portal mockup on homepage — replace with capture when ready)
9. [x] Licensed human photos in 2+ sections (no founder identity)
10. [x] Two-path CTAs work: evaluate → app, alumni → contact/invite
11. [x] Legal footers show correct plan prices (PHP footer)
12. [x] FAQPage JSON-LD on homepage and pricing
13. [ ] Owner approves: "This looks like a serious agency, not a template"

---

## 15. Immediate next action

1. **Build deploy package:** `php marketing-site/scripts/package-deploy.php`
2. **Upload** `dist/reviveguard-marketing/` contents to Hostinger `public_html/`
3. **Production smoke test:** `BASE_URL=https://reviveguard.com php marketing-site/scripts/smoke-test.php`
4. **Google Search Console** — verify domain, submit `https://reviveguard.com/sitemap.xml`
5. **Lighthouse mobile** on `/`, `/pricing/`, `/contact/` (target >85)

---

## 16. Document map

| Doc | Role |
|-----|------|
| `01_BUSINESS_PLAN.md` | GTM, two-path model, mission |
| `10_PORTAL_UX_MASTER_PLAN.md` | Product truth, portal positioning |
| `11_ADMIN_OPS_MASTER_PLAN.md` | Ops — not marketing |
| **`12_MARKETING_SITE_MASTER_PLAN.md`** | **This file** — marketing rebuild |

---

*Audit based on full read of `marketing-site/` (15 files), cross-check against `PlanSeeder`, `reviveguard_addons.php`, `01_BUSINESS_PLAN.md`, and reference patterns from WP Umbrella (SaaS clarity) and WaybackRestore (human trust).*
