<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Jobs\OnboardClientJob;
use App\Models\AddonOrder;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Support\MonitorSettings;
use App\Support\PlanCatalog;
use App\Support\PlanStripePriceSync;
use App\Support\StripeConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

/**
 * Stripe billing: Checkout Sessions, Customer Portal, and webhook lifecycle.
 */
class StripeBillingService
{
    private string $tenantId;

    public function __construct(private readonly InvoiceService $invoiceService)
    {
        $this->tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
        Stripe::setApiKey($this->secretKey());
    }

    public function secretKey(): string
    {
        return StripeConfig::secretKey();
    }

    public function isTestMode(): bool
    {
        return StripeConfig::isTestMode();
    }

    public function getOrCreateCustomer(Client $client): string
    {
        $column = $client->stripeCustomerIdColumn();

        if ($client->{$column}) {
            return $client->{$column};
        }

        $customer = Customer::create([
            'email'    => $client->email,
            'name'     => $client->name,
            'metadata' => [
                'client_id' => $client->id,
                'tenant_id' => $client->tenant_id,
                'mode'      => StripeConfig::modeLabel(),
            ],
        ]);

        $client->update([$column => $customer->id]);

        return $customer->id;
    }

    public function createCheckoutSession(Client $client, Site $site, Plan $plan): string
    {
        PlanStripePriceSync::syncFromConfig();
        $plan->refresh();

        $priceId = $plan->resolvedStripePriceId();

        if (empty($priceId)) {
            throw new \RuntimeException("Stripe price not configured for plan: {$plan->slug} (" . StripeConfig::modeLabel() . ' mode)');
        }

        if ($reason = $plan->checkoutUnavailableReason()) {
            throw new \RuntimeException($reason);
        }

        $customerId = $this->getOrCreateCustomer($client);

        $session = Session::create([
            'mode'     => 'subscription',
            'customer' => $customerId,
            'line_items' => [[
                'price'    => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => route('portal.checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url'  => route('portal.sites', ['list' => 1]),
            'client_reference_id' => $client->id,
            'metadata' => [
                'client_id' => $client->id,
                'site_id'   => $site->id,
                'plan_id'   => $plan->id,
                'tenant_id' => $this->tenantId,
            ],
            'subscription_data' => [
                'metadata' => [
                    'client_id' => $client->id,
                    'site_id'   => $site->id,
                    'plan_id'   => $plan->id,
                    'tenant_id' => $this->tenantId,
                ],
            ],
        ]);

        return $session->url;
    }

    /**
     * Upgrade an active per-site subscription to a higher plan (Stripe proration).
     */
    public function upgradeSitePlan(Client $client, Site $site, Plan $newPlan): Subscription
    {
        PlanStripePriceSync::syncFromConfig();
        $newPlan->refresh();

        if ($site->client_id !== $client->id) {
            throw new \RuntimeException('Site not found.');
        }

        $subscription = $site->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            throw new \RuntimeException('No active subscription found for this site.');
        }

        if (empty($subscription->stripe_subscription_id)) {
            throw new \RuntimeException('Plan changes for this subscription must be done by support. Please open a ticket.');
        }

        $currentPlan = $site->plan;

        if (! PlanCatalog::isUpgrade($currentPlan, $newPlan)) {
            throw new \RuntimeException('Please select a higher plan to upgrade.');
        }

        $newPriceId = $newPlan->resolvedStripePriceId();

        if (empty($newPriceId)) {
            throw new \RuntimeException($newPlan->checkoutUnavailableReason() ?? 'This plan is not available for checkout yet.');
        }

        $stripeSub = StripeSubscription::retrieve($subscription->stripe_subscription_id);
        $itemId    = $stripeSub->items->data[0]->id ?? null;

        if (! $itemId) {
            throw new \RuntimeException('Could not read your subscription from Stripe. Please contact support.');
        }

        $metadata = array_merge(
            (array) ($stripeSub->metadata ?? []),
            [
                'client_id' => $client->id,
                'site_id'   => $site->id,
                'plan_id'   => $newPlan->id,
                'tenant_id' => $this->tenantId,
            ],
        );

        $updated = StripeSubscription::update($subscription->stripe_subscription_id, [
            'items' => [
                ['id' => $itemId, 'price' => $newPriceId],
            ],
            'proration_behavior' => 'create_prorations',
            'metadata'           => $metadata,
        ]);

        $this->applyStripeSubscriptionState($subscription, $updated, $newPlan);

        $defaults = MonitorSettings::defaultsForPlan($newPlan);

        $site->update([
            'plan_id'                  => $newPlan->id,
            'monitor_interval_minutes' => MonitorSettings::normalizeInterval(
                $site->fresh(['plan']),
                (int) ($site->monitor_interval_minutes ?? $defaults['monitor_interval_minutes']),
            ),
            'monitor_region' => MonitorSettings::normalizeRegion(
                $site,
                (string) ($site->monitor_region ?? $defaults['monitor_region']),
            ),
        ]);

        return $subscription->fresh(['plan', 'site']);
    }

    public function createBillingPortalSession(Client $client): string
    {
        $customerId = $client->stripeCustomerId();

        if (! $customerId) {
            throw new \RuntimeException('No billing account on file. Complete a checkout first.');
        }

        $session = PortalSession::create([
            'customer'   => $customerId,
            'return_url' => route('portal.billing'),
        ]);

        return $session->url;
    }

    public function createAddonCheckoutSession(Client $client, AddonOrder $order): string
    {
        if ($order->client_id !== $client->id) {
            throw new \RuntimeException('Order does not belong to this account.');
        }

        if (! $order->isAwaitingPayment()) {
            throw new \RuntimeException('This order is not awaiting payment.');
        }

        if (empty($order->amount_cents) || $order->amount_cents < 50) {
            throw new \RuntimeException('Invalid order amount.');
        }

        if (empty(StripeConfig::secretKey())) {
            throw new \RuntimeException('Payment system is not configured yet.');
        }

        $customerId = $this->getOrCreateCustomer($client);

        $session = Session::create([
            'mode'       => 'payment',
            'customer'   => $customerId,
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $order->amount_cents,
                    'product_data' => [
                        'name'        => $order->addon_name,
                        'description' => 'ReviveGuard add-on · Qty ' . $order->quantity,
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('portal.addons.checkout.success', ['session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url'  => route('portal.addons'),
            'client_reference_id' => $client->id,
            'metadata' => [
                'type'           => 'addon_order',
                'addon_order_id' => $order->id,
                'client_id'      => $client->id,
                'tenant_id'      => $this->tenantId,
            ],
        ]);

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return $session->url;
    }

    public function handleCheckoutSessionCompleted(object $session): void
    {
        if (($session->metadata->type ?? null) === 'addon_order') {
            $this->handleAddonCheckoutCompleted($session);

            return;
        }

        $clientId = $session->metadata->client_id ?? null;
        $siteId   = $session->metadata->site_id ?? null;
        $planId   = $session->metadata->plan_id ?? null;

        if (! $clientId || ! $siteId || ! $session->subscription) {
            Log::warning('StripeBillingService: checkout.session.completed missing data', [
                'client_id'      => $clientId,
                'site_id'        => $siteId,
                'subscription'   => $session->subscription ?? null,
            ]);

            return;
        }

        $client = Client::find($clientId);
        $site   = Site::find($siteId);

        if (! $client || ! $site) {
            return;
        }

        $stripeSub = StripeSubscription::retrieve($session->subscription);
        $this->upsertSubscriptionFromStripe($client, $site, $stripeSub, $planId);
    }

    public function handleAddonCheckoutCompleted(object $session): bool
    {
        $orderId = $session->metadata->addon_order_id ?? null;

        if (! $orderId) {
            Log::warning('StripeBillingService: addon checkout missing order id');

            return false;
        }

        if (! in_array($session->payment_status ?? null, ['paid', 'no_payment_required'], true)
            && ($session->status ?? null) !== 'complete') {
            return false;
        }

        $order = AddonOrder::find($orderId);

        if (! $order || ! $order->isAwaitingPayment()) {
            return false;
        }

        $order->update([
            'status'  => 'in_progress',
            'paid_at' => now(),
        ]);

        return true;
    }

    public function handleSubscriptionEvent(object $stripeSub): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();

        if ($subscription) {
            $this->applyStripeSubscriptionState($subscription, $stripeSub);

            return;
        }

        $clientId = $stripeSub->metadata->client_id ?? null;
        $siteId   = $stripeSub->metadata->site_id ?? null;
        $planId   = $stripeSub->metadata->plan_id ?? null;

        if (! $clientId || ! $siteId) {
            Log::info('StripeBillingService: subscription event with no local record', [
                'stripe_subscription_id' => $stripeSub->id,
            ]);

            return;
        }

        $client = Client::find($clientId);
        $site   = Site::find($siteId);

        if (! $client || ! $site) {
            return;
        }

        $this->upsertSubscriptionFromStripe($client, $site, $stripeSub, $planId);
    }

    public function handleSubscriptionDeleted(object $stripeSub): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => 'canceled',
            'cancelled_at'  => now(),
        ]);

        $this->deactivateClientIfNoActiveSubscriptions($subscription->client_id, $subscription->id);

        if ($subscription->site_id) {
            Site::where('id', $subscription->site_id)->update(['status' => SiteStatus::SUSPENDED]);
        }
    }

    public function handleInvoicePaid(object $invoice): void
    {
        if ($invoice->status !== 'paid') {
            return;
        }

        try {
            $this->invoiceService->createFromStripeInvoice($invoice);
        } catch (\Throwable $e) {
            Log::error('StripeBillingService: invoice.paid failed', [
                'invoice_id' => $invoice->id ?? null,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function upsertSubscriptionFromStripe(Client $client, Site $site, object $stripeSub, ?string $planId): Subscription
    {
        $planId = $planId ?? ($stripeSub->metadata->plan_id ?? null);
        $plan   = $planId ? Plan::find($planId) : null;

        if (! $plan && ! empty($stripeSub->items->data[0]->price->id)) {
            $plan = $this->resolvePlanFromStripePrice($stripeSub->items->data[0]->price->id);
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSub->id)->first();

        $isNew = ! $subscription;

        if ($subscription) {
            $this->applyStripeSubscriptionState($subscription, $stripeSub, $plan);
        } else {
            $subscription = Subscription::create([
                'tenant_id'                => $this->tenantId,
                'client_id'                => $client->id,
                'site_id'                  => $site->id,
                'plan_id'                  => $plan?->id,
                'stripe_subscription_id' => $stripeSub->id,
                'stripe_status'            => $stripeSub->status,
                'stripe_current_period_end'=> $this->periodEnd($stripeSub),
                'activated_at'             => $this->isStripeActive($stripeSub->status) ? now() : null,
            ]);
        }

        if ($this->isStripeActive($stripeSub->status)) {
            $client->update(['is_active' => true]);

            $site->update([
                'plan_id'         => $plan?->id ?? $site->plan_id,
                'subscription_id' => $subscription->id,
                'status'          => SiteStatus::ACTIVE,
            ]);

            if ($isNew) {
                OnboardClientJob::dispatch($client->id, $subscription->id, false);
            }
        }

        return $subscription;
    }

    private function applyStripeSubscriptionState(Subscription $subscription, object $stripeSub, ?Plan $plan = null): void
    {
        $plan ??= ! empty($stripeSub->items->data[0]->price->id)
            ? $this->resolvePlanFromStripePrice($stripeSub->items->data[0]->price->id)
            : null;

        $subscription->update([
            'stripe_status'             => $stripeSub->status,
            'stripe_current_period_end' => $this->periodEnd($stripeSub),
            'plan_id'                   => $plan?->id ?? $subscription->plan_id,
            'suspended_at'              => in_array($stripeSub->status, ['past_due', 'unpaid'], true) ? now() : null,
            'cancelled_at'              => $stripeSub->status === 'canceled' ? now() : null,
        ]);

        if ($this->isStripeActive($stripeSub->status)) {
            $subscription->client?->update(['is_active' => true]);

            if ($subscription->site_id) {
                Site::where('id', $subscription->site_id)->update([
                    'plan_id'         => $plan?->id ?? $subscription->plan_id,
                    'subscription_id' => $subscription->id,
                    'status'          => SiteStatus::ACTIVE,
                ]);
            }
        } elseif (in_array($stripeSub->status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            $this->deactivateClientIfNoActiveSubscriptions($subscription->client_id, $subscription->id);

            if ($subscription->site_id) {
                Site::where('id', $subscription->site_id)->update(['status' => SiteStatus::SUSPENDED]);
            }
        }
    }

    private function resolvePlanFromStripePrice(string $priceId): ?Plan
    {
        return Plan::where('stripe_price_id', $priceId)
            ->orWhere('stripe_test_price_id', $priceId)
            ->first();
    }

    private function deactivateClientIfNoActiveSubscriptions(string $clientId, string $exceptId): void
    {
        $hasActive = Subscription::where('client_id', $clientId)
            ->where('id', '!=', $exceptId)
            ->where(function ($q) {
                $q->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            })
            ->exists();

        if (! $hasActive) {
            Client::where('id', $clientId)->update(['is_active' => false]);
        }
    }

    private function isStripeActive(string $status): bool
    {
        return in_array($status, ['active', 'trialing'], true);
    }

    private function periodEnd(object $stripeSub): ?Carbon
    {
        $end = $stripeSub->current_period_end ?? null;

        return $end ? Carbon::createFromTimestamp((int) $end) : null;
    }
}
