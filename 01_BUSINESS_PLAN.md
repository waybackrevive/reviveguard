# ReviveGuard — Business Plan

---

## 1. Problem Statement

Small business owners lose their websites constantly — domain expiry, hosting failure, hacked, accidentally deleted. WaybackRevive already solves the crisis (restore the lost site). ReviveGuard solves the prevention: keep the site alive, updated, and backed up so the crisis never repeats.

The customer already exists in your database. They've already felt the pain. They will pay to never feel it again.

---

## 2. Positioning

**ReviveGuard: Website maintenance that actually runs itself.**

Not a tool. Not a plugin. A done-for-you service backed by a system the client can see and trust at any time.

**Core emotional sell:** Peace of mind. "Your site is being watched. You don't have to think about it."

**Differentiator vs competitors (WP Buffs, Maintainn, GoWP):**
- They sell to any WordPress site owner globally
- You sell to clients who already trust WaybackRevive
- You own the full stack — no third-party dashboard the client sees
- Your branding on everything — client never sees MainWP, Uptime Kuma, etc.
- You can bundle restoration credits into maintenance plans (no competitor can do this)

---

## 3. Target Segments (Priority Order)

### Segment 1 — WaybackRevive Restored Clients (Hottest)
- Already paid you money
- Already trust you
- Already experienced the worst outcome (site lost)
- Conversion rate estimate: 20-30% with a single follow-up email

### Segment 2 — Small Business WordPress Sites
- Broad market, abundant
- Pain point: don't know their WordPress is outdated, don't know when SSL expires
- Acquired via: SEO blog content, referrals from Segment 1

### Segment 3 — Freelancers / Agencies Reselling
- Phase 2 feature (reseller accounts)
- Will drive volume without proportional support cost

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

### Week 1 — Warm Outreach (Zero spend)
Send one personal email to every client you've ever restored a site for. Subject: "Your site is restored — want us to keep it that way?"

Body: 3 sentences. Problem → solution → link to plan page. No fluff.

Expected result: 2-5 paying clients from this email alone.

### Week 2-4 — WaybackRevive Integration
- Add a "maintenance plan" upsell to every restoration invoice going forward
- Add a banner/section to waybackrevive.com landing page
- Add a "What happens after restoration?" blog post targeting SEO

### Month 2+ — Content SEO
- Target keywords: "wordpress maintenance service", "website uptime monitoring small business", "wordpress update service"
- Each service plan gets its own landing page with clear scope
- Case study: "How we prevented [client's] site from going down again"

### Month 3+ — Referral Program
- Existing clients get 1 month free for each referral that converts
- Simple, no tracking software needed at this scale — honor system with a form

---

## 8. Churn Prevention Strategy

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
