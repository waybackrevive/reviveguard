# ReviveGuard Marketing Site — V2 Final Plan

> **Date:** July 8, 2026  
> **Status:** Planning — owner direction: **light theme only**, live site as content source  
> **Content source:** `marketing-site/marketing-site-current-live/` (your live site export)  
> **Stack:** Static HTML + CSS + JS (KISS) · `contact/send.php` only  
> **Goal:** Premium light-theme SaaS **with** the depth, clarity, and conversion architecture of the live site — ReviveGuard branding, not generic dark Framer templates.

---

## 0. Owner direction (locked July 8, 2026)

| Decision | Rule |
|----------|------|
| **Theme** | **Light only** — white / soft gray sections. **No dark hero. No dark CTA bands.** |
| **Branding** | ReviveGuard own identity — emerald green, origin bar, live-dot, WaybackRevive link |
| **Content** | Migrate from `marketing-site-current-live/` — sections, copy intent, flows (fix pricing bugs) |
| **Layout inspiration** | Sellor, Saazai, Optimize, Biotix — **structure & UX only**, not their colors or dark modes |
| **Stack** | HTML + CSS + JS (KISS) |

---

## 1. Executive diagnosis — what went wrong & what to keep

### What you liked (keep)

| Element | Source | Keep in V2 |
|---------|--------|------------|
| **Light theme** — white base, soft gray sections | Live site `marketing-site-current-live/` | ✅ **Locked** |
| Premium spacing, floating nav, product screenshots | Sellor, Optimize references | ✅ (light treatment) |
| DM Sans or Inter | Live uses Inter; V2 can use DM Sans if preferred | Inter default (matches live + portal) |
| Emerald green `#059669` | Live site + portal/admin | ✅ Primary brand color |
| Lucide icons, no emoji | V2 standard | ✅ |
| Origin bar + live-dot | Live site | ✅ ReviveGuard signature |
| Problem-first copy + **full section depth** | Live `index.html` | ✅ Migrate all sections |
| Two-path model, journey tabs, quality cap | Live site | ✅ |

### What failed in the minimal rebuild (fix)

| Problem | Why it hurts conversion |
|---------|-------------------------|
| **Dark theme** | Not your brand — live site is light; conflicts with trust/calm positioning |
| **Site too short** (~340 lines vs live ~1,580) | High-consideration buyers need proof loops before they evaluate |
| **Content feels AI-generated** | Generic SaaS sections without ReviveGuard-specific scenarios |
| **No real visuals** | CSS mockup ≠ product; reads like a template, not shipped software |
| **Missing live-site sections** | Two-path journeys, portal preview, comparison, quality cap, add-ons — all gone |
| **Pages too thin** | `/for-alumni/`, `/about/`, `/pricing/` are stubs, not decision pages |
| **Same IA as every Framer template** | Hero → 3 cards → pricing → FAQ — no unique ReviveGuard story |

### Strategic principle for V2

> **Structure like Sellor. Content from `marketing-site-current-live/`. Light ReviveGuard branding. Truth like PlanSeeder.**

We merge:
- **Premium light SaaS layout** (references — structure only)
- **Full content architecture** from your live site export
- **Verified product facts only** (fix $19, backup frequency, unshipped features)

### Live site content source

```
marketing-site/marketing-site-current-live/
├── index.html              ← 1,580 lines — PRIMARY copy/section source
├── about-us/index.html
├── contact/index.html + send.php
├── privacy/ terms/ refund/
└── assets/css/style.css    ← Light theme tokens (#059669, #f7f9fc)
```

**W0 task:** Section-by-section inventory from live `index.html` → V2 pages. Fix trust bugs per `12_MARKETING_SITE_MASTER_PLAN.md` §5.2 while migrating.

---

## 2. Reference design synthesis

Patterns to borrow — mapped to ReviveGuard, not copied blindly.

### From [Sellor](https://sellor.framer.website/)

| Pattern | ReviveGuard use |
|---------|-----------------|
| Hero + **large product screenshot** below headline | Real portal fleet view or site workspace — dominant visual |
| Stat strip (11k teams, uptime, revenue) | **500+ recoveries · 5-min checks · 48h evaluation · 26/mo cap** |
| Feature section with **embedded UI cards** | Monitoring chart, backup list, monthly PDF preview |
| Pricing with “Most Popular” badge | Guard plan |
| Final CTA band repeating hero promise | Evaluation + Alumni dual CTA |

### From [Saazai](https://saazai.framer.website/)

| Pattern | ReviveGuard use |
|---------|-----------------|
| Logo / trust bar under hero | WaybackRevive link + “Operated by WaybackRevive LLC” |
| Bento feature grid with mini UI inside cards | Watch · Backup · Update · Report tabs |
| Testimonial carousel | **Only real quotes** — or “What alumni say after a restore” placeholder until collected |
| Pricing section with clear tier contrast | Monitor / Guard / Shield |
| Generous section padding, one message per block | Apply globally |

### From [AgenAI](https://agen-ai.framer.website/)

| Pattern | ReviveGuard use |
|---------|-----------------|
| Eyebrow badge above H1 | “Managed by WaybackRevive LLC” / “Invite-only” |
| Numbered process (01 / 02 / 03) | Evaluation → Connect → Protected |
| Floating pill nav on scroll | **White** nav bar with blur + soft shadow |
| ~~Dark hero~~ | **Skip** — use light hero like live site + Sellor |

### From [Optimize](https://optimize.framer.website/home-1) + [Biotix](https://biotiix.framer.website/features)

| Pattern | ReviveGuard use |
|---------|-----------------|
| **Light hero** + trust logo/stats row | Primary light-theme reference |
| Dedicated **Features** page with deep sections | `/features/` |
| Pricing page with **comparison table** | Monitor vs Guard vs Shield |
| About page with stats + values grid | `/about/` |
| Soft section alternation (`#fff` / `#f7f9fc`) | Match live site rhythm |

### From [Marketo pricing](https://marketo.framer.wiki/pricing-v1)

| Pattern | ReviveGuard use |
|---------|-----------------|
| “Compare plans” table below cards | Feature-by-feature checkmarks |
| Decision-focused FAQ | “Do I need evaluation?” “Can alumni skip?” |

### What we explicitly reject from templates

- Fake testimonial carousels with stock headshots
- “Trusted by 10,000+ companies” with logo walls we can’t verify
- Gradient blob heroes
- Generic “AI-powered” / “supercharge” copy
- Self-serve “Start free trial” (we are evaluation/invite gated)
- Features not in `PlanSeeder` (malware scan, SEO audit, etc.)

---

## 3. Brand & design system (locked — light theme)

### Visual identity (from live site + premium polish)

| Token | Value | Notes |
|-------|-------|-------|
| Primary green | `#059669` | Matches live site, portal, admin |
| Green hover | `#047857` | `--green-dim` |
| Green soft bg | `#d1fae5` / `#f0fdf4` | Badges, CTA section tint |
| Page background | `#ffffff` | Default body |
| Section alt | `#f7f9fc` | `--bg-2` — live site sections |
| Section alt 2 | `#eef2f7` | Optional depth |
| Text primary | `#111827` | Headings |
| Text body | `#374151` | Body copy |
| Text muted | `#6b7280` | Secondary |
| Border | `#e5e7eb` | Cards, dividers |
| Font | **Inter** 400–800 | Live site standard (portal-aligned) |
| Mono | JetBrains Mono | URLs, status codes only |
| Radius | Cards 12px · Buttons 8px | Live site `--radius` |
| Shadow | `--shadow`, `--shadow-lg` | Soft elevation — Sellor-style depth on light |
| Max width | 1140–1200px | Live uses 1140px |
| Icons | Lucide 20px | Replace any remaining emoji in migrated copy |

### Light-theme rules (no dark sections)

| Element | Treatment |
|---------|-----------|
| Hero | White or very soft green tint `#f0fdf4` — **not** `#0D1117` |
| Nav | White floating bar, border `#e5e7eb`, blur backdrop |
| CTA bands | Soft green background `#ecfdf5` or white with green border — **not** black |
| Feature sections | Alternate `#fff` ↔ `#f7f9fc` |
| Footer | `#f7f9fc` or white with top border — **not** dark navy |
| Product screenshots | White browser chrome frame, soft shadow |

### ReviveGuard signature elements (keep from live)

1. **Origin bar** — WaybackRevive link → ReviveGuard prevention story  
2. **Live dot** — pulsing green on logo + status badges  
3. **Logo** — `Revive` + green `Guard`  
4. **Invite-only / 26 clients** badge in hero  
5. **Dual CTA** — Evaluation + WaybackRevive alumni path  

### Layout rules (anti-template)

1. **Alternate rhythm:** white → soft gray → white (never dark blocks)  
2. **One H2 per section, one job per section** — full depth from live site  
3. **Product proof every 2 scrolls** — real portal screenshots  
4. **Human photo max 3 placements** — contextual stock, light/bright photography  
5. **Reference layouts, not reference colors** — Sellor structure on ReviveGuard palette  

### Component library (build once, reuse)

```
components/
  nav.html          (or JS inject)
  footer.html
  cta-band.html
  stat-strip.html
  plan-card.html
  faq-accordion.html
  portal-frame.html  (screenshot wrapper with browser chrome)
  section-head.html
```

**KISS approach:** Shared `assets/css/site.css` + optional `assets/js/components.js` for nav/footer injection — no build tooling required.

---

## 4. Content strategy — sound human, not AI

### Voice rules (from your brief + business plan)

| Do | Don't |
|----|-------|
| Lead with the **Sunday phone call** scenario | “Robust monitoring solution” |
| Name **intervals, retention days, alert windows** | “Powerful backups” |
| Say **our operations team** / **WaybackRevive LLC** | Named founder hero |
| Use **evaluation** and **invite** language | “Sign up free” |
| Explain **why 26/mo cap** is a benefit | “World-class service” |

### Banned words

seamlessly · empower · leverage · cutting-edge · next-generation · world-class · game-changing · revolutionize · unlock · supercharge · transform · robust · innovative solution

### Proof hierarchy (trust stack)

1. **WaybackRevive track record** — 500+ recoveries (verifiable narrative)
2. **Live product screenshots** — portal fleet, monitoring, backups, PDF report
3. **Specific plan facts** — from `PlanSeeder.php`
4. **Process transparency** — evaluation steps, what happens in 48h
5. **Quality cap** — 26 new clients/month (honest scarcity)
6. **Legal/financial** — 7-day refund, Stripe billing, cancel anytime
7. **Real testimonials** — only when collected; until then use **scenario quotes** labeled as illustrative or omit

### Image plan

| Asset | Type | Placement |
|-------|------|-----------|
| Portal fleet view | **Real screenshot** | Hero + `/features/` |
| Site monitoring tab + 7-day chart | **Real screenshot** | Product section |
| Backup history list | **Real screenshot** | Backup feature block |
| Monthly PDF report (redacted) | **Real export** | Report feature block |
| Stressed owner / calm at desk | Licensed stock | Problem section |
| Operations desk (wide, no faces) | Licensed stock | About page |
| Alumni relief moment | Licensed stock | `/for-alumni/` |

Store in `assets/img/product/` and `assets/img/human/`. WebP + lazy load.

---

## 5. Information architecture (final)

```
reviveguard.com/
├── /                          Conversion hub (full funnel — see §6)
├── /how-it-works/             Path A + Path B journeys (from live site)
├── /features/                 Product depth — portal capabilities
├── /pricing/                  Plans + comparison table + add-ons + FAQ
├── /for-alumni/               Path A landing (email campaign destination)
├── /about/                    Origin story, values, stats, quality cap
├── /contact/                  Topic routing + form
├── /privacy/  /terms/  /refund/
└── /sitemap.xml
```

**301 redirects:** `/about-us/` → `/about/` · legacy `.html` → clean URLs

**Phase 2 (post-launch):** `/case-studies/`, `/blog/` — not in V2 scope

---

## 6. Page-by-page specification

### 6.1 Homepage `/` — full funnel (not minimal)

**Job:** In 60 seconds, a site manager thinks: *“This is my problem”* → *“This team has done this before”* → *“I should request an evaluation”* or *“I'm alumni.”*

| # | Section | Source | Design |
|---|---------|--------|--------|
| 0 | **Origin bar** | Live site | WaybackRevive → ReviveGuard |
| 1 | **Nav** | Live + Sellor floating pill | White, sticky, blur |
| 2 | **Hero (light)** | Live `index.html` hero | Badge, dual CTA, trust chips — soft bg |
| 3 | **Product screenshot** | NEW — real portal | Full-width below hero, light shadow |
| 4 | **Stat strip** | Live + Optimize | 500+ · 5-min · 48h · 26/mo |
| 5 | **Problem (3 cards)** | Live §problem | White cards on `#f7f9fc` |
| 6 | **Two paths** | Live §who-its-for | Alumni + New client cards |
| 7 | **How it works** | Live §how-it-works + journey tabs | Preview → link `/how-it-works/` |
| 8 | **Portal preview** | Live §portal | Screenshot + copy |
| 9 | **Features** | Live §features | Light section, Lucide icons |
| 10 | **Comparison** | Live §compare | Without vs With — fix pricing in table |
| 11 | **Pricing preview** | Live §pricing | 3 cards — **$49/$99/$179** truth |
| 12 | **Add-ons preview** | Live §addons | Link to `/pricing/#addons` |
| 13 | **Quality promise** | Live §quality-promise | 26/mo cap |
| 14 | **Social proof** | Live §testimonials | Real only — or remove |
| 15 | **FAQ** | Live §faq | 8+ questions |
| 16 | **Final CTA** | Live §cta-banner | **Light green band** `#ecfdf5`, not dark |
| 17 | **Footer** | Live footer | Plan links from truth source |

**Remove from homepage** (move to subpages): full add-ons grid, long competitor table, duplicate journey tab content.

---

### 6.2 `/how-it-works/` — NEW (restore live site depth)

**Job:** Remove anxiety about evaluation and onboarding.

| Section | Alumni path | New client path |
|---------|-------------|-----------------|
| Hero | “Already restored? Skip the queue.” | “New here? Start with a free evaluation.” |
| Step 1 | Open invite email | Submit evaluation form |
| Step 2 | Accept invite in portal | We review in 48h |
| Step 3 | Choose plan + install plugin | Receive proposal + invite |
| Step 4 | Monitoring live | Same — protected |

**UI:** Tab switcher or two columns (from live site journey tabs).  
**Visual:** Plugin install screenshot + portal onboarding wizard crop.

---

### 6.3 `/features/` — NEW (product proof page)

**Job:** Show the portal is real and deep — not a brochure.

| Section | Proof |
|---------|-------|
| Hero | “One portal. Every site. The truth about what's happening.” |
| Fleet table | Screenshot — sites, status, last backup |
| Monitoring | 7-day chart, interval by plan (5 min Monitor/Guard, 2 min Shield) |
| Backups | Schedule + retention by plan |
| Updates | Guard/Shield only — pre-update backup note |
| Reports | PDF monthly report sample |
| SSL & domain | Alert timeline 60/30/7 days |
| Support | Tickets — Guard/Shield |
| CTA | Evaluation |

Reference: [Biotix features](https://biotiix.framer.website/features) depth, Optimize feature blocks.

---

### 6.4 `/pricing/` — full decision page

| Section | Detail |
|---------|--------|
| Hero | “Per site. Per month. No surprises.” |
| Plan cards | Monitor $49 · Guard $99 · Shield $179 — features from PlanSeeder |
| **Comparison table** | Row per feature — checkmarks per plan | Marketo-style |
| Add-ons grid | From `reviveguard_addons.php` — correct prices ($99 emergency restore) |
| Pricing FAQ | Evaluation, refund, cancel, alumni |
| CTA | Evaluate · Alumni contact |

---

### 6.5 `/for-alumni/` — Path A landing

| Section | Detail |
|---------|--------|
| Hero (light) | “You trusted us to restore your site. Now let us keep it safe.” |
| Human photo | Relief / calm at desk (stock) |
| 3 proof bullets | We know your stack · Skip evaluation · Minutes to protected |
| What you get | By plan summary |
| Email CTA | Check hello@reviveguard.com |
| Secondary | `/contact/?topic=alumni` |
| **No evaluation form** | Alumni ≠ Path B |

---

### 6.6 `/about/` — trust & origin

| Section | Detail |
|---------|--------|
| Hero | Recovery → prevention story |
| Stats | 500+ recoveries · 26/mo cap · 5-min checks |
| Values grid | Quality over volume · Transparency · Prevention |
| Operations photo | Team-at-work stock (no founder) |
| WaybackRevive link | Still active recovery service |
| CTA | Evaluate · Alumni |

Reference: [Optimize about](https://optimize.framer.website/about), [Biotix about](https://biotiix.framer.website/about-us).

---

### 6.7 `/contact/` — intent routing

Keep topic cards: evaluation · alumni · pricing · support · emergency · partnership.  
Reskin to match V2. Form → `contact/send.php`.

---

## 7. Conversion funnel map

```
                    ┌─────────────────┐
                    │  Google / Referral │
                    │  WaybackRevive link│
                    └────────┬────────┘
                             ▼
                    ┌─────────────────┐
         ┌─────────│    HOMEPAGE      │─────────┐
         │         └────────┬────────┘         │
         │                  │                  │
         ▼                  ▼                  ▼
   /for-alumni/      /pricing/          /features/
   (email traffic)   (price shoppers)   (skeptics)
         │                  │                  │
         │                  └────────┬─────────┘
         │                           ▼
         │                  /how-it-works/
         │                           │
         ▼                           ▼
   Invite email              app.reviveguard.com/evaluate
   accept-invite             (48h → proposal → portal)
         │                           │
         └───────────┬───────────────┘
                     ▼
              Portal → Plan → Plugin → Protected
```

**Micro-conversions to track:** scroll depth on product screenshot · pricing page visits · alumni contact · evaluation clicks.

---

## 8. Single source of truth (non-negotiable)

| Claim type | Source file |
|------------|-------------|
| Plan prices, backup freq, retention | `app-code/database/seeders/PlanSeeder.php` |
| Add-on prices | `app-code/config/reviveguard_addons.php` |
| Monitor intervals | `10_PORTAL_UX_MASTER_PLAN.md` §8 |
| Positioning, two-path rules | `01_BUSINESS_PLAN.md` |
| Portal capabilities | Live app screenshots only |

**Pre-publish checklist:** No $19 · No unshipped features · No fake testimonials · No “26 total sites” (it's **26 new clients/month**).

---

## 9. Implementation sprints

### Sprint W0 — Audit & assets (2–3 days)

| Task | Output |
|------|--------|
| Inventory all sections from `marketing-site-current-live/index.html` | Section map doc |
| Cross-check copy vs `PlanSeeder` — fix $19, backup freq, fake features | Corrected claim list |
| Export 6–8 portal screenshots | `assets/img/product/*.webp` |
| License 3–4 bright/contextual stock photos | `assets/img/human/*.webp` |

**Exit:** Live site audited · assets ready · pricing bugs listed

---

### Sprint W1 — Design system & shared components (3–4 days)

| Task | Output |
|------|--------|
| `site.css` — **light tokens** from live `style.css` + Sellor elevation | No dark variables |
| Origin bar, nav pill, buttons, cards, FAQ, portal-frame | Component library |
| Lucide replaces any emoji in migrated copy | |
| Mobile nav + responsive pricing table | |

**Exit:** Light-theme component demo · matches ReviveGuard brand

---

### Sprint W2 — Homepage rebuild (4–5 days)

| Task | Output |
|------|--------|
| All 15 homepage sections (§6.1) | `index.html` |
| Real portal screenshot hero | Not CSS mockup |
| Product tab section with screenshots | 4 tabs |
| Two-path + comparison + quality cap | From live site |
| FAQ + JSON-LD FAQPage schema | SEO |

**Exit:** Homepage scroll > 4 viewport heights · reads human · dual CTA works

---

### Sprint W3 — Product & process pages (3–4 days)

| Task | Output |
|------|--------|
| `/how-it-works/` with dual journeys | Tab UI |
| `/features/` with 6 capability sections | Screenshot-led |

**Exit:** Skeptic can verify product depth without logging in

---

### Sprint W4 — Pricing & alumni (3 days)

| Task | Output |
|------|--------|
| `/pricing/` full page + comparison table | PlanSeeder-accurate |
| `/for-alumni/` expanded landing | Email-campaign ready |
| Add-ons grid with correct prices | |

**Exit:** Price shopper can decide without contacting sales

---

### Sprint W5 — About, contact, legal (2–3 days)

| Task | Output |
|------|--------|
| `/about/` origin + values + stats | |
| `/contact/` topic routing reskin | |
| Legal pages styled consistently | privacy · terms · refund |

**Exit:** Full site navigable · all forms work

---

### Sprint W6 — SEO, QA, launch (2–3 days)

| Task | Output |
|------|--------|
| `sitemap.xml` · meta · OG tags per page | |
| Link audit script (no broken CTAs) | |
| Lighthouse mobile > 85 on `/`, `/pricing/` | |
| Hostinger deploy + smoke test | |
| Google Search Console sitemap submit | |

**Exit:** Production live · owner sign-off on “feels premium + honest”

---

## 10. Success criteria (how we know V2 worked)

You should be able to say yes to all of these:

1. **Within 10 seconds:** Visitor understands the problem (client finds out before you do)
2. **Within 60 seconds:** Visitor sees real product UI, not a CSS fake
3. **Homepage feels as detailed as live site** but looks like Sellor/AgenAI quality
4. **Two-path model is obvious** above the fold and on dedicated pages
5. **Every plan claim matches PlanSeeder**
6. **No fake social proof**
7. **Stock photos feel natural** — same person/context style, not random corporate
8. **Pricing page answers “which plan?”** without a sales call
9. **Alumni email can link to `/for-alumni/`** and stand alone
10. **You would show this site to a paying client** without apologizing for the design

---

## 11. What we are NOT building in V2

- Blog / case studies (Phase 2)
- Agency reseller landing (Phase 2)
- Multi-language
- WordPress theme / page builder
- PHP template layer (KISS HTML unless you later want includes)
- Fake review widgets or unverifiable logo walls
- Features not shipped (malware scan, SEO audit, dedicated account manager)

---

## 12. Recommended immediate next step

**Approve this plan** → execute **W0 + W1 + W2 (homepage)**:

1. Migrate homepage sections from `marketing-site-current-live/index.html`
2. Apply **light ReviveGuard branding** + Sellor-style product screenshot layout
3. Fix PlanSeeder pricing/backup bugs during migration
4. You review homepage before remaining pages

---

## 13. Document map

| Doc | Role |
|-----|------|
| `01_BUSINESS_PLAN.md` | Mission, two-path model, quality cap |
| `10_PORTAL_UX_MASTER_PLAN.md` | Product truth, intervals |
| `12_MARKETING_SITE_MASTER_PLAN.md` | V1 audit + PHP era (historical) |
| **`13_MARKETING_SITE_V2_PLAN.md`** | **This file — V2 build authority** |

---

*Plan synthesized from: live site section audit (git), business plan, portal master plan, current HTML rebuild, and reference sites Saazai, Sellor, AgenAI, Optimize, Biotix, Marketo.*
