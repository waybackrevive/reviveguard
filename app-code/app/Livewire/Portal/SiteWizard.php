<?php

namespace App\Livewire\Portal;

use App\Enums\SiteStatus;
use App\Models\Plan;
use App\Models\Site;
use App\Services\StripeBillingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Add-site wizard: URL → Connection → Plan → Payment.
 */
class SiteWizard extends Component
{
    public int $step = 1;

    public string $clientLabel = '';
    public string $siteUrl     = '';

    public ?string $siteId           = null;
    public string  $connectionToken = '';

    public string $selectedPlan   = 'guard';
    public bool   $showComparison = false;

    public array $plans = [];

    public function mount(): void
    {
        $this->plans = Plan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'slug', 'price_monthly', 'stripe_price_id', 'stripe_test_price_id'])
            ->map(fn (Plan $p) => array_merge($p->toArray(), [
                'resolved_stripe_price_id' => $p->resolvedStripePriceId(),
            ]))
            ->toArray();
    }

    /** Step 1 → create pending site and show connection guide */
    public function goToConnection(): void
    {
        $this->validate([
            'clientLabel' => ['nullable', 'string', 'max:150'],
            'siteUrl'     => ['required', 'url', 'max:500'],
        ]);

        $client = Auth::guard('client')->user();
        $url    = rtrim($this->siteUrl, '/');

        $exists = Site::where('client_id', $client->id)->where('url', $url)->exists();
        if ($exists) {
            $this->addError('siteUrl', 'This site is already in your account.');
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $host     = parse_url($url, PHP_URL_HOST) ?: $url;

        $site = Site::create([
            'tenant_id'         => $client->tenant_id,
            'client_id'         => $client->id,
            'name'              => $this->clientLabel ?: $host,
            'client_label'      => $this->clientLabel ?: null,
            'url'               => $url,
            'status'            => SiteStatus::PENDING,
            'agent_token'       => hash('sha256', $rawToken),
            'agent_token_last4' => substr($rawToken, -4),
            'is_active'         => true,
        ]);

        $this->siteId           = $site->id;
        $this->connectionToken  = $rawToken;
        $this->step             = 2;
    }

    public function goToPlan(): void
    {
        $this->step = 3;
    }

    public function selectPlan(string $slug): void
    {
        $this->selectedPlan = $slug;
    }

    public function toggleComparison(): void
    {
        $this->showComparison = ! $this->showComparison;
    }

    public function goToCheckout(): void
    {
        $plan = collect($this->plans)->firstWhere('slug', $this->selectedPlan);

        if (! $plan || empty($plan['resolved_stripe_price_id'])) {
            $this->addError('selectedPlan', 'Checkout is not configured for this plan. Please contact support.');
            return;
        }

        if ($this->siteId) {
            Site::where('id', $this->siteId)->update(['plan_id' => $plan['id']]);
        }

        $this->step = 4;
    }

    public function proceedToCheckout(StripeBillingService $billing): void
    {
        $plan = Plan::where('slug', $this->selectedPlan)->where('is_active', true)->first();

        if (! $plan || empty($plan->resolvedStripePriceId())) {
            $this->addError('selectedPlan', 'Checkout is not configured. Please contact support.');
            return;
        }

        $client = Auth::guard('client')->user();
        $site   = Site::where('id', $this->siteId)->where('client_id', $client->id)->first();

        if (! $site) {
            $this->addError('siteUrl', 'Site not found. Please start again.');
            $this->step = 1;
            return;
        }

        $site->update(['plan_id' => $plan->id]);

        try {
            $checkoutUrl = $billing->createCheckoutSession($client, $site, $plan);
        } catch (\Throwable $e) {
            $this->addError('selectedPlan', 'Unable to start checkout. Please contact support.');
            report($e);
            return;
        }

        $this->redirect($checkoutUrl, navigate: false);
    }

    public function goBackTo(int $targetStep): void
    {
        $this->step = max(1, min(4, $targetStep));
    }

    public function cancel(): void
    {
        $this->redirect(route('portal.sites'), navigate: true);
    }

    public function getSelectedPlanData(): ?array
    {
        return collect($this->plans)->firstWhere('slug', $this->selectedPlan);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.site-wizard', [
            'selectedPlanData' => $this->getSelectedPlanData(),
            'domain'           => parse_url($this->siteUrl, PHP_URL_HOST) ?: $this->siteUrl,
            'stripeTestMode'   => \App\Support\StripeConfig::isTestMode(),
        ]);
    }
}
