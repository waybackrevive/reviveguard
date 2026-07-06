<?php

namespace App\Livewire\Portal;

use App\Models\Plan;
use App\Models\Site;
use App\Services\StripeBillingService;
use App\Support\StripeConfig;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SiteShow extends Component
{
    public Site $site;

    #[Url(as: 'tab')]
    public string $tab = 'overview';

    public bool $showCredentialsModal = false;
    public string $credHostingProvider = '';
    public string $credCpanelUrl = '';
    public string $credCpanelUser = '';
    public string $credCpanelPassword = '';
    public string $credSshHost = '';
    public string $credSshUser = '';
    public string $credSshPassword = '';
    public string $credFtpHost = '';
    public string $credFtpUser = '';
    public string $credFtpPassword = '';
    public string $credNotes = '';
    public bool $credentialsSaved = false;

    public string $selectedPlanSlug = 'guard';

    public function mount(Site $site): void
    {
        $client = Auth::guard('client')->user();

        if ($site->client_id !== $client->id) {
            abort(404);
        }

        $this->site = $site->load(['plan', 'subscription']);
        $this->selectedPlanSlug = $this->site->plan?->slug ?? 'guard';

        if (! request()->has('tab') && in_array($this->site->portalStatusKey(), ['setup', 'checkout'], true)) {
            $this->tab = $this->site->portalStatusKey() === 'checkout' ? 'plan' : 'connection';
        }
    }

    public function setTab(string $tab): void
    {
        $allowed = ['overview', 'activity', 'backups', 'reports', 'connection', 'plan'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'overview';
    }

    public function resumeCheckout(StripeBillingService $billing)
    {
        $client = Auth::guard('client')->user();

        if ($this->site->client_id !== $client->id || $this->site->hasPaidSubscription()) {
            session()->flash('error', 'This site already has an active subscription.');
            return;
        }

        $plan = $this->site->plan ?? Plan::where('slug', $this->selectedPlanSlug)->where('is_active', true)->first();

        if (! $plan) {
            session()->flash('error', 'Please choose a plan first.');
            $this->tab = 'plan';
            return;
        }

        if (empty(StripeConfig::secretKey())) {
            session()->flash('error', 'Payment system is not configured yet. Stripe secret key is missing on the server.');
            return;
        }

        if (! $plan->hasStripeCheckout()) {
            $mode = StripeConfig::isTestMode() ? 'test' : 'live';
            session()->flash('error', "Stripe {$mode} price is not set for the {$plan->name} plan. Add STRIPE" . ($mode === 'test' ? '_TEST' : '') . "_PRICE_" . strtoupper($plan->slug) . "_ID in .env or Admin → Platform Settings.");
            return;
        }

        $this->site->update(['plan_id' => $plan->id]);
        $this->site->load('plan');

        try {
            $url = $billing->createCheckoutSession($client, $this->site, $plan);
        } catch (\Throwable $e) {
            session()->flash('error', 'Unable to start checkout: ' . $e->getMessage());
            report($e);
            return;
        }

        return redirect()->away($url);
    }

    public function selectPlan(string $slug): void
    {
        $this->selectedPlanSlug = $slug;

        if (! $this->site->hasPaidSubscription()) {
            $plan = Plan::where('slug', $slug)->where('is_active', true)->first();
            if ($plan) {
                $this->site->update(['plan_id' => $plan->id]);
                $this->site->load('plan');
            }
        }
    }

    public function removeSite(): void
    {
        $client = Auth::guard('client')->user();

        if ($this->site->client_id !== $client->id || $this->site->hasPaidSubscription()) {
            session()->flash('error', 'Only unpaid sites waiting for checkout can be removed.');
            return;
        }

        $this->site->delete();
        session()->flash('success', 'Site removed.');
        $this->redirect(route('portal.sites'), navigate: true);
    }

    public function openCredentials(): void
    {
        $existing = $this->site->hosting_credentials ?? [];
        $this->credHostingProvider = $existing['hosting_provider'] ?? '';
        $this->credCpanelUrl       = $existing['cpanel_url'] ?? '';
        $this->credCpanelUser      = $existing['cpanel_user'] ?? '';
        $this->credCpanelPassword  = $existing['cpanel_password'] ?? '';
        $this->credSshHost         = $existing['ssh_host'] ?? '';
        $this->credSshUser         = $existing['ssh_user'] ?? '';
        $this->credSshPassword     = $existing['ssh_password'] ?? '';
        $this->credFtpHost         = $existing['ftp_host'] ?? '';
        $this->credFtpUser         = $existing['ftp_user'] ?? '';
        $this->credFtpPassword     = $existing['ftp_password'] ?? '';
        $this->credNotes           = $existing['notes'] ?? '';
        $this->credentialsSaved    = false;
        $this->showCredentialsModal = true;
    }

    public function saveCredentials(): void
    {
        $this->site->update([
            'hosting_credentials' => [
                'hosting_provider' => $this->credHostingProvider,
                'cpanel_url'       => $this->credCpanelUrl,
                'cpanel_user'      => $this->credCpanelUser,
                'cpanel_password'  => $this->credCpanelPassword,
                'ssh_host'         => $this->credSshHost,
                'ssh_user'         => $this->credSshUser,
                'ssh_password'     => $this->credSshPassword,
                'ftp_host'         => $this->credFtpHost,
                'ftp_user'         => $this->credFtpUser,
                'ftp_password'     => $this->credFtpPassword,
                'notes'            => $this->credNotes,
            ],
        ]);

        $this->credentialsSaved = true;
        $this->showCredentialsModal = false;
    }

    public function render(): \Illuminate\View\View
    {
        $this->site->load([
            'events' => fn ($q) => $q->latest()->limit($this->tab === 'activity' ? 50 : 5),
            'backups' => fn ($q) => $q->latest()->limit(20),
            'reports' => fn ($q) => $q->latest()->limit(20),
        ]);

        return view('livewire.portal.site-show', [
            'site'         => $this->site,
            'recentEvents' => $this->site->events,
            'backups'      => $this->site->backups,
            'reports'      => $this->site->reports,
            'latestBackup' => $this->site->latestBackup,
            'plans'        => Plan::where('is_active', true)->orderBy('price_monthly')->get(),
            'stripeTestMode' => StripeConfig::isTestMode(),
        ])->layout('portal.layouts.app');
    }
}
