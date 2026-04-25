# ReviveGuard — Master Documentation Index

> **Working product name:** ReviveGuard (change anytime — all docs use this placeholder)
> **Parent brand:** WaybackRevive LLC
> **Phase covered:** MVP — 0 to 10 paying clients

---

## Document Map

| # | File | What it covers |
|---|---|---|
| 01 | `01_BUSINESS_PLAN.md` | Model, pricing, GTM, revenue projections |
| 02 | `02_SYSTEM_ARCHITECTURE.md` | Full stack, components, infrastructure, deployment |
| 03 | `03_DATABASE_SCHEMA.md` | All tables, relationships, multi-tenancy model |
| 04 | `04_API_DESIGN.md` | All API endpoints — agent, portal, admin, webhooks |
| 05 | `05_MVP_FEATURE_SPEC.md` | Exact features in/out of MVP scope |
| 06 | `06_AGENT_PLUGIN_SPEC.md` | WordPress plugin + HTML site agent full spec |
| 07 | `07_CLIENT_PORTAL_SPEC.md` | Portal screens, UX flows, what client sees |
| 08 | `08_DEV_ROADMAP.md` | Phase-by-phase build plan with time estimates |

---

## Core Principles (read before anything else)

1. **KISS always wins** — if two solutions solve the same problem, always pick the simpler one
2. **Revenue before perfection** — Phase 1 ships with rough edges, not missing features
3. **Agent is the moat** — the WordPress plugin is your unfair advantage, protect its quality
4. **Client trust is the product** — the portal must feel calm, reliable, and professional
5. **Automate what repeats, manual what doesn't** — no automation for edge cases in MVP

---

## Naming Conventions Used Across Docs

| Term | Meaning |
|---|---|
| **Tenant** | Your agency (WaybackRevive) or future reseller accounts |
| **Client** | End customer who pays for a maintenance plan |
| **Site** | A website being monitored under a client account |
| **Agent** | The plugin/script installed on the client's site |
| **Heartbeat** | Periodic ping from agent to your platform confirming site is alive |
| **Event** | Any notable occurrence: downtime, update, backup, expiry alert |
| **Plan** | Subscription tier: Monitor / Guard / Shield |

---

## Tech Stack Quick Reference

| Layer | Technology | Why |
|---|---|---|
| Backend | Laravel 11 (PHP) | Mature, WP-compatible ecosystem, Cashier for billing |
| Admin panel | Filament v3 | 90% of admin UI without custom frontend work |
| Client portal | Laravel Livewire | No separate frontend app needed for MVP |
| Database | PostgreSQL 16 | JSON support, row-level security, robust for SaaS |
| Queue | Laravel Horizon + Redis | Job processing for checks, reports, alerts |
| Monitoring | Uptime Kuma (API-only, headless) | Battle-tested, free, full REST API |
| Reports | Puppeteer (Node.js microservice) | HTML-to-PDF, fully branded |
| Multi-tenancy | stancl/tenancy v3 | Open source, production-grade, fits Laravel perfectly |
| Billing | Stripe + Laravel Cashier | Industry standard, easy webhook handling |
| Email | Resend | Modern API, great deliverability, generous free tier |
| Notifications | WhatsApp Cloud API (Meta) | Free for first 1000 msgs/month, direct to client |
| Infra | Single Hetzner CX31 VPS | €12/mo, handles 100+ clients comfortably |
| Agent (WP) | Custom PHP Plugin | Your moat — built from scratch |
| Agent (HTML) | Bash script + cron | rsync backup + curl heartbeat |
