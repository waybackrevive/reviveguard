<?php

namespace App\Livewire\Portal;

use App\Enums\SiteStatus;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * MyWebsites — shows all sites belonging to the logged-in client
 * and serves as the entry point for the site onboarding wizard.
 */
class MyWebsites extends Component
{
    public bool    $showWizard           = false;
    public bool    $showCredentialsModal = false;
    public ?string $credentialsSiteId   = null;

    // Filters
    public string  $search              = '';
    public string  $filterStatus        = '';

    // Credentials form fields
    public string $credHostingProvider   = '';
    public string $credCpanelUrl         = '';
    public string $credCpanelUser        = '';
    public string $credCpanelPassword    = '';
    public string $credSshHost           = '';
    public string $credSshUser           = '';
    public string $credSshPassword       = '';
    public string $credFtpHost           = '';
    public string $credFtpUser           = '';
    public string $credFtpPassword       = '';
    public string $credNotes             = '';

    public bool   $credentialsSaved      = false;

    public function mount(): void
    {
        if (request()->query('open') === '1') {
            $this->showWizard = true;
            return;
        }

        $client = Auth::guard('client')->user();
        $siteCount = Site::where('client_id', $client->id)->count();

        if ($siteCount === 1 && ! request()->has('list')) {
            $site = Site::where('client_id', $client->id)->first();
            $this->redirect(route('portal.sites.show', $site), navigate: true);
        }
    }

    public function openWizard(): void
    {
        $this->showWizard = true;
    }

    public function closeWizard(): void
    {
        $this->showWizard = false;
    }

    #[\Livewire\Attributes\On('wizard-completed')]
    public function onWizardCompleted(): void
    {
        $this->showWizard = false;
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
    }

    /**
     * Rebuild the Whop checkout URL for a pending site and redirect.
     */
    public function resumeCheckout(string $siteId): void
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->where('status', SiteStatus::PENDING)
            ->with('plan')
            ->first();

        if (! $site) {
            return;
        }

        $plan   = $site->plan;
        $planId = null;

        if ($plan) {
            $sandbox = PlatformSetting::getBool('whop_sandbox', config('services.whop.sandbox', false));
            $pfx     = $sandbox ? 'whop_sandbox_' : 'whop_';
            $planId  = $plan->whop_plan_id ?? match($plan->slug) {
                'monitor' => PlatformSetting::get("{$pfx}plan_monitor_id", config('services.whop.plan_monitor_id')),
                'guard'   => PlatformSetting::get("{$pfx}plan_guard_id",   config('services.whop.plan_guard_id')),
                'shield'  => PlatformSetting::get("{$pfx}plan_shield_id",  config('services.whop.plan_shield_id')),
                default   => null,
            };
        }

        if (empty($planId)) {
            session()->flash('error', 'Cannot resume checkout: plan not configured. Please contact support.');
            return;
        }

        $sandbox = PlatformSetting::getBool('whop_sandbox', config('services.whop.sandbox', false));
        $pfx     = $sandbox ? 'whop_sandbox_' : 'whop_';
        $base    = rtrim(PlatformSetting::get("{$pfx}checkout_base", config('services.whop.checkout_base', 'https://whop.com/checkout')), '/');
        $params = http_build_query([
            'redirect_url' => url('/portal/welcome'),
            'd'            => parse_url($site->url, PHP_URL_HOST),
        ]);

        $this->redirect("{$base}/{$planId}?{$params}", navigate: false);
    }

    /**
     * Open the hosting credentials modal for a specific site.
     */
    public function openCredentials(string $siteId): void
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->first();

        if (! $site) {
            return;
        }

        $this->credentialsSiteId  = $siteId;
        $this->credentialsSaved   = false;

        // Pre-populate existing credentials if stored
        $existing = $site->hosting_credentials ?? [];
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

        $this->showCredentialsModal = true;
    }

    public function saveCredentials(): void
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $this->credentialsSiteId)
            ->where('client_id', $client->id)
            ->first();

        if (! $site) {
            return;
        }

        $site->update([
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
    }

    public function closeCredentials(): void
    {
        $this->showCredentialsModal = false;
        $this->credentialsSiteId    = null;
        $this->credentialsSaved     = false;
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        $query = Site::where('client_id', $client->id)->with('plan');

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
