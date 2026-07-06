<?php

namespace App\Livewire\Portal;

use App\Enums\SiteStatus;
use App\Models\Plan;
use App\Models\Site;
use App\Services\StripeBillingService;
use App\Support\StripeConfig;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * MyWebsites — shows all sites belonging to the logged-in client
 * and serves as the entry point for the site onboarding wizard.
 */
class MyWebsites extends Component
{
    public string $search       = '';
    public string $filterStatus = '';

    public function mount(): void
    {
        if (request()->query('open') === '1') {
            $this->redirect(route('portal.sites.add'), navigate: true);
        }
    }

    public function openWizard(): void
    {
        $this->redirect(route('portal.sites.add'), navigate: true);
    }

    /**
     * Delete a pending (unpaid) site owned by the current client.
     */
    public function deletePendingSite(string $siteId): void
    {
        $client = Auth::guard('client')->user();

        Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->where('status', SiteStatus::PENDING)
            ->delete();

        session()->flash('success', 'Site removed.');
    }

    /**
     * Start Stripe Checkout for a pending site.
     */
    public function resumeCheckout(string $siteId, StripeBillingService $billing)
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->where('status', SiteStatus::PENDING)
            ->with('plan')
            ->first();

        if (! $site || ! $site->plan) {
            session()->flash('error', 'Cannot resume checkout: site or plan not found.');
            return;
        }

        if (empty(StripeConfig::secretKey())) {
            session()->flash('error', 'Payment system is not configured yet. Stripe secret key is missing on the server.');
            return;
        }

        if (! $site->plan->hasStripeCheckout()) {
            $mode = StripeConfig::isTestMode() ? 'test' : 'live';
            session()->flash('error', "Stripe {$mode} price is not set for {$site->plan->name}. Add price IDs in .env or Admin → Platform Settings, then run: php artisan db:seed --class=PlanSeeder");
            return;
        }

        try {
            $checkoutUrl = $billing->createCheckoutSession($client, $site, $site->plan);
        } catch (\Throwable $e) {
            session()->flash('error', 'Unable to start checkout. Please contact support.');
            report($e);
            return;
        }

        return redirect()->away($checkoutUrl);
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        $query = Site::where('client_id', $client->id)->with(['plan', 'subscription']);

        if ($this->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$this->search}%")
                ->orWhere('client_label', 'ilike', "%{$this->search}%")
                ->orWhere('url',  'ilike', "%{$this->search}%"));
        }

        if ($this->filterStatus && $this->filterStatus !== 'all') {
            match ($this->filterStatus) {
                'setup'     => $query->where(fn ($q) => $q->where('status', SiteStatus::PENDING)->orWhereNull('last_seen_at')),
                'protected' => $query->where('status', SiteStatus::ACTIVE)->whereNotNull('last_seen_at'),
                'issue'     => $query->where('status', SiteStatus::DOWN)->whereNotNull('last_seen_at'),
                'warning'   => $query->where('status', SiteStatus::WARNING),
                'checkout'  => $query->where('status', SiteStatus::PENDING),
                default     => $query->where('status', $this->filterStatus),
            };
        }

        $sites = $query->orderBy('created_at')->get();

        $summary = [
            'total'     => $sites->count(),
            'protected' => $sites->filter(fn ($s) => $s->portalStatusKey() === 'protected')->count(),
            'setup'     => $sites->filter(fn ($s) => in_array($s->portalStatusKey(), ['setup', 'checkout']))->count(),
            'issues'    => $sites->filter(fn ($s) => in_array($s->portalStatusKey(), ['issue', 'warning']))->count(),
        ];

        return view('livewire.portal.my-websites', compact('sites', 'client', 'summary'))
            ->layout('portal.layouts.app');
    }
}
