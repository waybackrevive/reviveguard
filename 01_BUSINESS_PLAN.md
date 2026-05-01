# ReviveGuard — Business Plan

---

## 0. Mission & Why We Exist

**ReviveGuard exists because losing your website is one of the most stressful things that can happen to a small business owner.**

We know this because we've seen it happen hundreds of times through WaybackRevive — the site goes dark, the phone stops ringing, years of content and customer trust disappear in an instant. Most of the time it was completely preventable: an expired domain, an outdated plugin, a missed backup.

**Our purpose is to permanently close that gap.** Not with an automated tool that sends you alerts and leaves you to figure out what to do, but with a real human-backed service — one where our team proactively manages your website's health so you can focus entirely on running your business.

We care deeply about quality. We will not onboard every website we can find. We will onboard the websites we are confident we can protect well.

**Who we serve:**
1. Small business owners whose websites we've already restored — we know their stack, we know their pain, we know what to protect.
2. Carefully vetted new clients who want the same level of hands-on care, evaluated before they join so both sides know the engagement will be successful.

---

## 1. Problem Statement

Small business owners lose their websites constantly — domain expiry, hosting failure, hacked, accidentally deleted. WaybackRevive already solves the crisis (restore the lost site). ReviveGuard solves the prevention: keep the site alive, updated, and backed up so the crisis never repeats.

The customer already exists in your database. They've already felt the pain. They will pay to never feel it again.

---

## 2. Positioning

**ReviveGuard: Expert-managed website protection, with a dashboard you actually own.**

Not a tool. Not a plugin. A done-for-you service backed by a system the client can see, control, and trust at any time — with our team working behind it.

**Core emotional sell:** Peace of mind. "Your site is being watched by people who've already saved it once. You don't have to think about it."

**Differentiator vs competitors (WP Buffs, WPMaintenance, Maintainn, GoWP):**
- They sell to any WordPress site owner globally — we serve clients we already know
- You own the full stack — no third-party dashboard the client sees
- Your branding on everything — client never sees MainWP, Uptime Kuma, etc.
- Clients self-manage their dashboard (add sites, choose plans, view invoices, manage add-ons) — full ownership, zero confusion
- You can bundle restoration credits into maintenance plans (no competitor can do this)
- Quality cap: max 26 new clients onboarded per month — this is a feature, not a limitation

---

## 3. Customer Segments & Acquisition Model

### The Two-Path System

ReviveGuard does not have one generic "sign up" button. There are two clearly differentiated paths, each serving a different customer type with a different trust level and onboarding flow.

---

### Path A — WaybackRevive Alumni (Existing Restored Clients)

**Who:** Every client whose website WaybackRevive has ever restored.

**Why they're the hottest leads:**
- Already paid money for your service
- Already experienced the worst outcome (site lost)
- Already trust you and your team personally
- You already know their website's tech stack, hosting, and vulnerabilities
- Conversion rate estimate: 25-40% with a single personal email

**Critical: Path A is admin-controlled. Users cannot self-declare they are alumni.**

The system identifies a client as an alumni because **we** created the invite — not because they said so. The flow is:

```
Admin exports WaybackRevive restored-clients list
→ Admin bulk-imports into ReviveGuard (name, email, site URL, restore date)
→ Admin triggers "Send Invite" for selected clients
→ System generates a unique signed token per client
→ System sends personalised email: "Your site was restored — want it protected permanently?"
→ Email contains a unique link: app.reviveguard.com/portal/accept-invite?token=UNIQUE_TOKEN
→ Client clicks → token validated → client account activated → logged in
→ Onboarding wizard: confirm site URL, choose plan, done
→ Dashboard live within minutes
```

If someone visits the site without a valid invite token, **they cannot access Path A**. There is no "I'm an existing client" button on the public site. The portal has no public sign-up page.

**Portal experience:** Fully self-serve. They can:
- Select and change plans at any time
- Add multiple sites
- Enable/disable add-ons
- View invoices and billing history
- Submit support tickets
- See live status of every monitored item

**Onboarding effort:** Near-zero. We already know their setup. They just confirm and choose a plan.

---

### Path B — New Clients (Evaluation-Gated Onboarding)

**Who:** Small business owners who find us via search, referral, or word of mouth.

**Why evaluation-gated?**
- Not every website is a good fit — some sites are built so poorly that we cannot guarantee quality outcomes
- Quality-gated onboarding protects our reputation and the client's expectations
- "We only take clients we're confident we can protect" is a legitimate competitive advantage
- It creates perceived exclusivity and serious intent — clients who go through an evaluation are more committed

**Quality cap: 26 new clients per month maximum** (roughly 1 per working day). This is not a technical limitation — it's a deliberate quality control decision. When we exceed capacity, the waitlist opens.

**Their journey:**
```
Homepage: "Request Site Evaluation" (only public entry point)
→ Fill form: name, email, website URL, site type, biggest concern
→ Receive confirmation email: "We'll review your site within 48 hours"
→ Admin panel: evaluation appears in queue with status 'pending'
→ Our team reviews the site manually:
    - CMS type & version
    - Plugin count and update status
    - Hosting quality
    - Backup history (if detectable)
    - Security vulnerabilities
    - Domain and SSL expiry status
→ Admin decision:
    IF approve:
        Admin clicks "Send Proposal" in evaluation queue
        → System generates a unique signed invite token (path = 'evaluation')
        → System sends "Site Evaluation Report" proposal email:
              - What we found
              - Risks identified
              - Recommended plan + why
              - Cost breakdown
              - "Accept this proposal" button (contains the invite token link)
        → Client clicks "Accept" → token validated → account activated → onboarding wizard
    IF decline:
        Admin clicks "Decline" with optional note
        → System sends polite rejection email
        → 30-day follow-up task created automatically
→ Client declines proposal → follow-up after 30 days with a helpful free tip
```

**Key point:** Even after evaluation approval, the client **cannot manually access the portal** without the invite link we send them. Admin generates the token; the link is the key.

**Admin workflow:** All evaluations visible in Filament admin panel. Team can approve, propose, reject with one-click actions and a notes field. Monthly cap enforced at the DB level.

---

### Segment 3 — Freelancers / Agencies (Phase 2)

Reseller accounts — white-label the portal under their own brand for their clients. Drives volume without proportional support cost. Not in Phase 1 scope.

---

## 4. Service Plans

### Plan A — Monitor | $19/month
**Who:** Static/HTML sites, or WordPress owners who just want basic assurance

**Included:**
- 24/7 uptime monitoring (checked every 5 minutes)
- SSL certificate expiry alert (60/30/7 days notice)
- Domain expiry alert (60/30/7 days notice)
- Monthly backup (1 full backup, stored 30 days)
- Monthly health report (email PDF)

**Not included:** WordPress updates, malware scanning, content edits

---

### Plan B — Guard | $49/month
**Who:** Active WordPress sites, small businesses

**Included:**
- Everything in Monitor
- Weekly backups (stored 90 days)
- WordPress core updates (auto, tested)
- Plugin and theme updates (auto, with rollback protection)
- Weekly malware scan (Wordfence CLI or similar)
- Broken link check (monthly)
- 1 support request/month (via portal)

**Not included:** Content edits, design changes, emergency malware cleanup (add-on)

---

### Plan C — Shield | $99/month
**Who:** Revenue-generating websites, established small businesses

**Included:**
- Everything in Guard
- Daily backups (stored 180 days)
- Up to 1 hour of content edits/month (text, images, minor layout)
- Priority support (response within 24 hours)
- Quarterly SEO health snapshot report
- Emergency restore SLA (within 4 hours of reported issue)

**Not included:** Full redesign, new feature development, SEO campaigns

---

### Add-ons (available to all plans)
| Add-on | Price |
|---|---|
| Emergency malware cleanup | $79 flat |
| Extra content edit hours | $35/hour |
| Speed optimization audit | $49 one-time |
| SSL installation | $29 one-time |
| Extra backup storage (per 10GB) | $5/month |
| Emergency restore (for Monitor clients) | $49/incident |

---

## 5. Revenue Projections

### Phase 1 Target: 10 clients

Conservative plan mix assumption:
- 4× Monitor ($19) = $76/mo
- 4× Guard ($49) = $196/mo
- 2× Shield ($99) = $198/mo
- **Total: $470 MRR**

Infrastructure cost at this scale: ~$25/mo (VPS + Backblaze)
Net margin: ~95%

---

### Phase 2 Target: 50 clients

Conservative mix:
- 20× Monitor = $380
- 20× Guard = $980
- 10× Shield = $990
- **Total: $2,350 MRR**

Infrastructure cost: ~$40/mo
Time to serve: ~25-30 hrs/month total
Net margin: ~98%

---

### Phase 3 Target: 200 clients + resellers

- Direct clients: $8,000-12,000 MRR
- Reseller tier (agencies using your platform): separate pricing TBD
- Hire 1 part-time support person at this stage

---

## 6. Cost Structure

### Fixed Monthly
| Item | Cost |
|---|---|
| Hetzner CX31 VPS | €12 (~$13) |
| Backblaze B2 backups (50 clients) | ~$8 |
| Resend email (free up to 3k/day) | $0 |
| WhatsApp Cloud API (free up to 1k/mo) | $0 |
| Stripe fees | 2.9% + $0.30 per charge |
| Domain (reviveguard.com or subdomain) | ~$1/mo amortized |
| **Total fixed** | **~$25/mo** |

### Variable
- Puppeteer microservice: runs on same VPS, no extra cost
- Uptime Kuma: self-hosted, same VPS
- Storage scales linearly with clients (Backblaze B2 is cheap)

---

## 7. Go-to-Market Strategy

### Week 1 — Warm Outreach: WaybackRevive Alumni (Zero spend)

Send one personal email to every client you've ever restored a site for. Subject: "Your site is restored — want us to keep it that way?"

Body: 3 sentences. Problem → solution → link to portal. No fluff. Magic link included so signup is one click.

Expected result: 3-6 paying clients from this email alone. These are Path A clients.

### Week 2-4 — Two-Path Homepage Launch

- Marketing site clearly communicates both paths: "Already a WaybackRevive client? → Protect your site (3 minutes)" vs "New here? → Request a Free Site Evaluation"
- The evaluation path replaces the generic "sign up" — this is intentional. It filters serious clients.
- Add a "maintenance plan" upsell to every WaybackRevive restoration invoice going forward

### Month 2+ — Content SEO for Path B Discovery

- Target keywords: "wordpress maintenance service", "website protection small business", "wordpress update service"
- Each service plan gets its own landing page with clear scope
- Case study: "How we prevented [client's] site from going down again"
- Blog post: "What we look for when we evaluate a website before taking it on"

### Month 3+ — Referral Program

- Existing clients get 1 month free for each referral that converts
- Simple — no tracking software needed at this scale. Honor system with a form.

---

## 8. Homepage Design & Flow

The marketing homepage communicates this story clearly and immediately. Inspired by WPMaintenance's approach of giving clients ownership and transparency, adapted to our two-path model.

### Above the fold

```
ReviveGuard
Expert-managed WordPress protection — with a dashboard you own.

[→ I'm an existing WaybackRevive client]   [Request a free site evaluation]

Trusted by businesses we've already restored. We know your website. Let us protect it.
```

### Two-Path Section (after hero)

**Path A card:**
```
"Already worked with us?"
Your restored site deserves permanent protection.
Because we restored it, we already know your setup.
Getting started takes about 3 minutes.
[Protect my site →]
```

**Path B card:**
```
"New to ReviveGuard?"
We don't onboard every website.
Submit yours for a free evaluation. We'll review it, identify risks,
and send you a personalised proposal — within 48 hours.
We accept up to 26 new clients per month to ensure quality.
[Request evaluation →]
```

### Trust Section
- Stats: sites monitored, uptime average, years experience, response time SLA
- Testimonials from restored clients who stayed on for maintenance

---

## 9. Churn Prevention Strategy

The biggest risk in maintenance SaaS is clients cancelling because they "don't see the value." Prevention:

1. **Monthly report must show work done** — even if it was a quiet month, show: "0 downtime events, 3 plugins updated, 1 backup verified." Activity proves value.
2. **Domain and SSL alerts feel like heroics** — when you catch an expiring domain, the client feels genuinely rescued. Make sure these alerts are clear about what would have happened.
3. **Annual plan discount** — offer 2 months free on annual billing. Reduces churn risk for 12 months.
4. **Quarterly check-in email** — not automated. Personal. "How's the site treating you? Anything you'd like us to look at?"

---

## 9. Legal & Compliance

- **Service Agreement:** Required before onboarding. Defines scope, SLAs, what's not covered. Protects you from "but I thought content edits were unlimited" disputes.
- **Privacy Policy:** Required for client portal (collects email, site data).
- **Backup Data:** Inform clients where backups are stored (Backblaze B2, US region by default). Offer EU region if client requests.
- **Stripe:** Handles PCI compliance for billing. You never store card data.
- **GDPR note:** If you have EU clients, add a data processing addendum to your service agreement.

---

## 10. Success Metrics (Phase 1)

| Metric | Target |
|---|---|
| MRR | $470 (10 clients) |
| Churn rate | < 5%/month |
| Avg response time to support ticket | < 24 hours |
| Backup success rate | > 99% |
| Uptime monitoring accuracy | 100% (no false positives/negatives) |
| Monthly report delivery rate | 100% on time |
| Client portal login rate | > 60% of clients log in at least once/month |
