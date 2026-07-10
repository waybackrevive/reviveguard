<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\DispatchesPortalToast;
use App\Enums\SiteStatus;
use App\Models\Plan;
use App\Models\Site;
use App\Services\StripeBillingService;
use App\Services\WordPressSsoService;
use App\Support\StripeConfig;
use App\Services\ContentHoursService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * MyWebsites — shows all sites belonging to the logged-in client
 * and serves as the entry point for the site onboarding wizard.
 */
class MyWebsites extends Component
{
    use DispatchesPortalToast;

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
            ->with('subscription')
            ->get()
            ->filter(fn (Site $site) => ! $site->hasPaidSubscription())
            ->each->delete();

        $this->toastSuccess('Site removed.');
    }

    /**
     * Start Stripe Checkout for a pending site.
     */
    public function resumeCheckout(string $siteId, StripeBillingService $billing)
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->with(['plan', 'subscription'])
            ->first();

        if (! $site || $site->hasPaidSubscription()) {
            $this->toastError('Cannot resume checkout: site not found or already subscribed.');
            return;
        }

        if (! $site->plan) {
            $this->toastError('Please choose a plan first.');
            return;
        }

        if (empty(StripeConfig::secretKey())) {
            $this->toastError('Payment system is not configured yet. Stripe secret key is missing on the server.');
            return;
        }

        if ($reason = $site->plan->checkoutUnavailableReason()) {
            $this->toastError($reason);
            return;
        }

        try {
            $checkoutUrl = $billing->createCheckoutSession($client, $site, $site->plan);
        } catch (\Throwable $e) {
            $this->toastError('Unable to start checkout. Please contact support.');
            report($e);
            return;
        }

        return redirect()->away($checkoutUrl);
    }

    public function openWordPressAdmin(string $siteId, WordPressSsoService $sso)
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)->where('client_id', $client->id)->first();

        if (! $site) {
            $this->toastError('Site not found.');

            return;
        }

        try {
            $url = $sso->createLoginUrl($site, $client->id);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        return redirect()->away($url);
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        $query = Site::where('client_id', $client->id)
            ->with(['plan', 'subscription', 'latestBackup']);

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

        $client->loadMissing('accountManager');
        $shieldPlan = $client->bestSupportPlan();
        $isShield   = optional($shieldPlan)->slug === 'shield';
        $contentHours = $isShield ? app(ContentHoursService::class)->remainingMinutes($client) : null;

        return view('livewire.portal.my-websites', compact('sites', 'client', 'summary', 'isShield', 'contentHours'))
            ->layout('portal.layouts.app');
    }
}
