# SKILL: Whop Billing Integration

> Load this skill before building any billing or subscription logic.
> References: `05_MVP_FEATURE_SPEC.md`, `01_BUSINESS_PLAN.md`

---

## What This Covers
Whop webhook handling for subscription lifecycle, BillingService, and the OnboardClientJob.
No Cashier package. No Stripe. Pure webhook-driven activation — KISS.

---

## Why Whop (KISS Rationale)

| Factor | Whop |
|---|---|
| Setup | Create products in dashboard, share payment link — done |
| SDK required | None — just validate webhook + parse JSON |
| Checkout page | Whop-hosted (zero code on your side) |
| Billing portal | Whop-hosted (client manages on whop.com) |
| Fees | ~3% per transaction, no monthly platform fee |
| Composer packages | None needed |

---

## Phase 1 Billing Flow (Zero Code for Checkout)

1. Create 3 products on `whop.com/dashboard`: Monitor ($19), Guard ($49), Shield ($99)
2. Each product gets a **Payment Link** — paste on pricing page
3. Client pays on Whop-hosted checkout page
4. Whop fires webhook → your app activates the client
5. For billing changes (cancel, update card) → client visits their Whop account

That's it. No in-app checkout. No credit card forms.

---

## Whop Webhook Events We Handle

| Event | Action |
|---|---|
| `membership.went_valid` | Activate client (new signup OR renewal) |
| `membership.went_invalid` | Suspend client (payment lapsed/expired) |
| `membership.cancelled` | Suspend client (voluntarily cancelled) |
| `payment.succeeded` | Reactivate if suspended (successful retry) |
| `payment.failed` | Send payment failed email to client |

All other events: return 200 and ignore.

---

## DB Column Changes (vs old Stripe schema)

Replace these columns in the `clients` table migration:
```
stripe_customer_id  →  whop_member_id   (varchar, nullable)
```

Replace these columns in the `subscriptions` table migration:
```
stripe_id      →  whop_membership_id   (varchar, unique)
stripe_status  →  whop_status          (varchar)
stripe_price   →  whop_plan_id         (varchar)
```

Remove Cashier columns entirely: `pm_type`, `pm_last_four`, `trial_ends_at`.

---

## `.env` Variables

```
WHOP_API_KEY=your_api_key_here
WHOP_WEBHOOK_SECRET=your_webhook_secret_here

# Whop Product IDs (from dashboard → your product → settings)
WHOP_PLAN_MONITOR_ID=prod_xxx
WHOP_PLAN_GUARD_ID=prod_xxx
WHOP_PLAN_SHIELD_ID=prod_xxx
```

---

## `config/services.php`

```php
'whop' => [
    'api_key'        => env('WHOP_API_KEY'),
    'webhook_secret' => env('WHOP_WEBHOOK_SECRET'),
    'plan_ids'       => [
        'monitor' => env('WHOP_PLAN_MONITOR_ID'),
        'guard'   => env('WHOP_PLAN_GUARD_ID'),
        'shield'  => env('WHOP_PLAN_SHIELD_ID'),
    ],
],
```

---

## Route

```php
// routes/api.php — outside auth middleware
Route::post('/whop/webhook', WhopWebhookController::class)
     ->middleware('whop.webhook');
```

---

## `VerifyWhopWebhook` Middleware

```php
final class VerifyWhopWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret    = config('services.whop.webhook_secret');
        $signature = $request->header('X-Whop-Signature-256');
        $payload   = $request->getContent();

        if (!$signature || !$secret) {
            abort(401, 'Missing webhook signature');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
```

Register alias in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'whop.webhook' => VerifyWhopWebhook::class,
    ]);
})
```

---

## `WhopWebhookController`

```php
final class WhopWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data  = $request->input('data', []);

        match ($event) {
            'membership.went_valid'   => $this->handleMembershipValid($data),
            'membership.went_invalid' => $this->handleMembershipInvalid($data),
            'membership.cancelled'    => $this->handleMembershipCancelled($data),
            'payment.succeeded'       => $this->handlePaymentSucceeded($data),
            'payment.failed'          => $this->handlePaymentFailed($data),
            default                   => null,
        };

        // Always return 200 — processing is async
        return response()->json(['status' => 'ok']);
    }

    private function handleMembershipValid(array $data): void
    {
        $membershipId = $data['id']            ?? null;
        $email        = $data['user']['email'] ?? null;
        $productId    = $data['product_id']    ?? null;
        $whopUserId   = $data['user']['id']    ?? null;

        if (!$membershipId || !$email) {
            Log::warning('Whop membership.went_valid missing required fields', $data);
            return;
        }

        $plan = Plan::where('whop_plan_id', $productId)->first();

        if (!$plan) {
            Log::warning("Whop product {$productId} not found in plans table");
            return;
        }

        $client = Client::firstOrCreate(
            ['email' => $email],
            [
                'tenant_id'      => config('tenancy.default_tenant_id'),
                'name'           => $data['user']['name'] ?? $email,
                'status'         => 'pending',
                'whop_member_id' => $whopUserId,
            ]
        );

        $client->update(['whop_member_id' => $whopUserId]);

        app(BillingService::class)->activateClient($client, $plan, $membershipId);
    }

    private function handleMembershipInvalid(array $data): void
    {
        $membershipId = $data['id'] ?? null;
        if (!$membershipId) return;

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if ($subscription) {
            app(BillingService::class)->suspendSubscription($subscription, 'lapsed');
        }
    }

    private function handleMembershipCancelled(array $data): void
    {
        $membershipId = $data['id'] ?? null;
        if (!$membershipId) return;

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if ($subscription) {
            app(BillingService::class)->cancelSubscription($subscription);
        }
    }

    private function handlePaymentSucceeded(array $data): void
    {
        $membershipId = $data['membership_id'] ?? null;
        if (!$membershipId) return;

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if ($subscription) {
            app(BillingService::class)->onPaymentSucceeded($subscription->client);
        }
    }

    private function handlePaymentFailed(array $data): void
    {
        $membershipId = $data['membership_id'] ?? null;
        if (!$membershipId) return;

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if ($subscription) {
            SendAlert::dispatch($subscription->client, 'payment_failed')->onQueue('default');
        }
    }
}
```

---

## `BillingService`

```php
final class BillingService
{
    public function activateClient(Client $client, Plan $plan, string $whopMembershipId): void
    {
        Subscription::updateOrCreate(
            ['whop_membership_id' => $whopMembershipId],
            [
                'tenant_id'    => $client->tenant_id,
                'client_id'    => $client->id,
                'plan_id'      => $plan->id,
                'whop_status'  => 'active',
                'whop_plan_id' => $plan->whop_plan_id,
                'name'         => 'default',
            ]
        );

        $client->update(['status' => 'active']);

        OnboardClient::dispatch($client, $plan);

        Log::info("Client {$client->id} activated on plan {$plan->name}");
    }

    public function suspendSubscription(Subscription $subscription, string $reason = 'lapsed'): void
    {
        $subscription->update(['whop_status' => $reason]);

        $client = $subscription->client;
        $client->update(['status' => 'suspended']);
        $client->sites()->update(['status' => SiteStatus::SUSPENDED]);

        foreach ($client->sites as $site) {
            Event::create([
                'tenant_id'   => $site->tenant_id,
                'site_id'     => $site->id,
                'type'        => 'subscription_suspended',
                'severity'    => EventSeverity::WARNING,
                'title'       => 'Monitoring paused — payment issue',
                'description' => 'Subscription payment lapsed. Monitoring has been paused.',
                'occurred_at' => now(),
            ]);
        }
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->update(['whop_status' => 'cancelled']);

        $client = $subscription->client;
        $client->update(['status' => 'suspended']);
        $client->sites()->update(['status' => SiteStatus::SUSPENDED]);

        foreach ($client->sites as $site) {
            Event::create([
                'tenant_id'   => $site->tenant_id,
                'site_id'     => $site->id,
                'type'        => 'subscription_cancelled',
                'severity'    => EventSeverity::WARNING,
                'title'       => 'Monitoring paused — subscription ended',
                'description' => 'Subscription was cancelled. Monitoring has been paused.',
                'occurred_at' => now(),
            ]);
        }
    }

    public function onPaymentSucceeded(Client $client): void
    {
        if ($client->status === 'suspended') {
            $client->update(['status' => 'active']);
            $client->sites()->where('status', SiteStatus::SUSPENDED)
                            ->update(['status' => SiteStatus::ACTIVE]);
        }
    }
}
```

---

## `OnboardClientJob`

```php
final class OnboardClient implements ShouldQueue
{
    public int $tries    = 3;
    public array $backoff = [120, 300, 600];
    public string $queue  = 'default';

    public function __construct(
        private readonly Client $client,
        private readonly Plan $plan,
    ) {}

    public function handle(
        UptimeKumaService $kumaService,
        NotificationService $notificationService,
    ): void {
        $client = $this->client->fresh(['sites']);

        foreach ($client->sites as $site) {
            if (!$site->uptime_kuma_monitor_id) {
                $kumaService->login();
                $monitorId = $kumaService->addMonitor($site);
                $site->update(['uptime_kuma_monitor_id' => $monitorId]);
            }
        }

        $notificationService->sendWelcomeEmail($client, $this->plan);
    }
}
```

---

## Billing Management for Clients (Phase 1)

No in-app billing portal. Client manages their subscription on **whop.com**.

In Account Settings Livewire component:
```blade
<a href="https://whop.com/manage" target="_blank" rel="noopener noreferrer"
   class="btn btn-outline">
    Manage Billing on Whop
</a>
```

That's one `<a>` tag. No API call, no redirect generation, no Cashier method.

---

## Packages NOT Needed (Remove from Composer)

```
❌ laravel/cashier          — not needed, Whop has no Cashier equivalent
❌ stripe/stripe-php        — not needed
```

---

## Test Mode → Live Checklist

```
[ ] Create 3 products on whop.com/dashboard with correct prices ($19/$49/$99)
[ ] Get WHOP_WEBHOOK_SECRET from Whop dashboard → Webhooks section
[ ] Store WHOP_WEBHOOK_SECRET and WHOP_PLAN_*_ID values in .env
[ ] Register webhook URL in Whop: https://app.reviveguard.com/api/whop/webhook
[ ] Select events: membership.went_valid, membership.went_invalid,
    membership.cancelled, payment.succeeded, payment.failed
[ ] Use Whop webhook test tool → confirm client activates in DB
[ ] Update plans table: set whop_plan_id for Monitor / Guard / Shield rows
```

---

## Definition of Done

```
[ ] Webhook signature validated with hash_equals (timing-safe, constant-time)
[ ] membership.went_valid → client activated + OnboardClient job queued
[ ] membership.went_invalid → client suspended + sites paused
[ ] membership.cancelled → client suspended + event logged on all sites
[ ] payment.succeeded → suspended client reactivated
[ ] payment.failed → payment failure email dispatched to client
[ ] Duplicate webhooks handled idempotently (updateOrCreate pattern)
[ ] Webhook always returns 200 immediately (async processing)
[ ] No Cashier package in composer.json
[ ] No stripe_* columns in any migration
```
