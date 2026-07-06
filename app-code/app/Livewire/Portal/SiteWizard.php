<?php

namespace App\Livewire\Portal;

use App\Enums\SiteStatus;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * SiteWizard — 3-step site onboarding wizard (matches portal spec).
 *
 * Step 1: Domain name + company + WP access method (authorize or manual)
 * Step 2: Package selection (Monitor/Guard/Shield) + add-ons
 * Step 3: Order summary → Whop hosted checkout redirect
 */
class SiteWizard extends Component
{
    // ── Wizard state ──────────────────────────────────────────────────────────
    public int $step = 1;

    // ── Step 1 ────────────────────────────────────────────────────────────────
    public string $clientLabel   = '';
    public string $companyName   = '';
    public string $siteUrl       = '';
    public string $accessMethod  = 'authorize'; // 'authorize' | 'manual'
    public string $wpAdminUrl    = '';
    public string $wpAppPassword = '';

    // ── Step 2 ────────────────────────────────────────────────────────────────
    public string $selectedPlan      = 'guard';
    public bool   $addonExtraStorage = false;
    public bool   $addonSpeedAudit   = false;
    public bool   $showComparison    = false;

    // Plans loaded from DB in mount()
    public array $plans = [];

    // ── Step 3 ────────────────────────────────────────────────────────────────
    public string $checkoutUrl = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->plans = Plan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'slug', 'price_monthly', 'whop_plan_id'])
            ->toArray();
    }

    // ── Step 1 actions ────────────────────────────────────────────────────────

    public function selectAccessMethod(string $method): void
    {
        $this->accessMethod = in_array($method, ['authorize', 'manual']) ? $method : 'authorize';
    }

    public function goToStep2(): void
    {
        $rules = [
            'clientLabel' => ['nullable', 'string', 'max:150'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'siteUrl'     => ['required', 'url', 'max:500'],
        ];

        if ($this->accessMethod === 'manual') {
            $rules['wpAdminUrl']    = ['required', 'url', 'max:500'];
            $rules['wpAppPassword'] = ['required', 'string', 'min:8', 'max:500'];
        }

        $this->validate($rules);

        $client = Auth::guard('client')->user();
        $exists = Site::where('client_id', $client->id)
            ->where('url', rtrim($this->siteUrl, '/'))
            ->exists();

        if ($exists) {
            $this->addError('siteUrl', 'This site is already in your account.');
            return;
        }

        $this->step = 2;
    }

    // ── Step 2 actions ────────────────────────────────────────────────────────

    public function selectPlan(string $slug): void
    {
        $this->selectedPlan = $slug;
    }

    public function toggleComparison(): void
    {
        $this->showComparison = ! $this->showComparison;
    }

    public function goToStep3(): void
    {
        $plan = collect($this->plans)->firstWhere('slug', $this->selectedPlan);

        if (! $plan) {
            $this->addError('selectedPlan', 'Please choose a plan to continue.');
            return;
        }

        $this->buildCheckoutUrl($plan);

        // Don't advance if checkout URL couldn't be built (e.g. plan ID not configured)
        if (empty($this->checkoutUrl)) {
            return;
        }

        $this->step = 3;
    }

    // ── Step 3 actions ────────────────────────────────────────────────────────

    public function proceedToCheckout(): void
    {
        if (empty($this->checkoutUrl)) {
            $this->addError('selectedPlan', 'Checkout URL is missing. Please contact support.');
            return;
        }

        // Create a pending site record so admin can see it and link post-payment
        $client   = Auth::guard('client')->user();
        $rawToken = bin2hex(random_bytes(32));
        $plan     = collect($this->plans)->firstWhere('slug', $this->selectedPlan);

        Site::firstOrCreate(
            ['client_id' => $client->id, 'url' => rtrim($this->siteUrl, '/')],
            [
                'tenant_id'         => $client->tenant_id,
                'name'              => $this->companyName ?: (parse_url($this->siteUrl, PHP_URL_HOST) ?: $this->siteUrl),
                'client_label'      => $this->clientLabel ?: null,
                'status'            => SiteStatus::PENDING,
                'plan_id'           => $plan['id'] ?? null,
                'agent_token'       => hash('sha256', $rawToken),
                'agent_token_last4' => substr($rawToken, -4),
                'is_active'         => true,
            ]
        );

        $this->redirect($this->checkoutUrl, navigate: false);
    }

    public function goBackTo(int $targetStep): void
    {
        $this->step = max(1, min(3, $targetStep));
    }

    public function cancel(): void
    {
        $this->dispatch('wizard-completed');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildCheckoutUrl(array $plan): void
    {
        // Prefer DB value, fall back to env config (in case seeder ran before env was set)
        $planId = $plan['whop_plan_id'] ?? null;

        if (empty($planId)) {
            $sandbox = PlatformSetting::getBool('whop_sandbox', config('services.whop.sandbox', false));
            $pfx     = $sandbox ? 'whop_sandbox_' : 'whop_';
            $planId  = match($plan['slug'] ?? '') {
                'monitor' => PlatformSetting::get("{$pfx}plan_monitor_id", config('services.whop.plan_monitor_id')),
                'guard'   => PlatformSetting::get("{$pfx}plan_guard_id",   config('services.whop.plan_guard_id')),
                'shield'  => PlatformSetting::get("{$pfx}plan_shield_id",  config('services.whop.plan_shield_id')),
                default   => null,
            };
        }

        if (empty($planId)) {
            $this->checkoutUrl = '';
            $this->addError('selectedPlan', 'Checkout is not configured for this plan. Please contact support.');
            return;
        }

        $sandbox = PlatformSetting::getBool('whop_sandbox', config('services.whop.sandbox', false));
        $pfx     = $sandbox ? 'whop_sandbox_' : 'whop_';
        $base    = rtrim(PlatformSetting::get("{$pfx}checkout_base", config('services.whop.checkout_base', 'https://whop.com/checkout')), '/');
        $params = http_build_query([
            'redirect_url' => url('/portal/welcome'),
            'd'            => parse_url($this->siteUrl, PHP_URL_HOST),
        ]);

        $this->checkoutUrl = "{$base}/{$planId}?{$params}";
    }

    public function getSelectedPlanData(): ?array
    {
        return collect($this->plans)->firstWhere('slug', $this->selectedPlan);
    }

    public function getTotal(): float
    {
        $plan = $this->getSelectedPlanData();
        $base = (float) ($plan['price_monthly'] ?? 0);

        if ($this->addonExtraStorage) {
            $base += 5.00;
        }

        return $base;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.site-wizard', [
            'selectedPlanData' => $this->getSelectedPlanData(),
            'total'            => $this->getTotal(),
            'domain'           => parse_url($this->siteUrl, PHP_URL_HOST) ?: $this->siteUrl,
        ]);
    }
}
