<?php

namespace App\Livewire\Portal;

use App\Models\Plan;
use App\Models\Site;
use App\Models\SiteUptimeProbe;
use App\Services\ClientActivityService;
use App\Services\StripeBillingService;
use App\Services\WordPressSsoService;
use App\Support\MonitorSettings;
use App\Support\SiteUptimeChart;
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

    public int $monitorInterval = 5;

    public string $monitorRegion = 'us-east';

    public bool $monitorSettingsSaved = false;

    public function mount(Site $site): void
    {
        $client = Auth::guard('client')->user();

        if ($site->client_id !== $client->id) {
            abort(404);
        }

        $this->site = $site->load(['plan', 'subscription']);
        $this->selectedPlanSlug = $this->site->plan?->slug ?? 'guard';
        $this->monitorInterval  = (int) ($this->site->monitor_interval_minutes ?? 5);
        $this->monitorRegion    = (string) ($this->site->monitor_region ?? 'us-east');

        if (! request()->has('tab') && in_array($this->site->portalStatusKey(), ['setup', 'checkout'], true)) {
            $this->tab = $this->site->portalStatusKey() === 'checkout' ? 'plan' : 'connection';
        }
    }

    public function setTab(string $tab): void
    {
        $allowed = ['overview', 'monitoring', 'activity', 'backups', 'reports', 'connection', 'plan'];
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

        if ($reason = $plan->checkoutUnavailableReason()) {
            session()->flash('error', $reason);
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

    public function openWordPressAdmin(WordPressSsoService $sso)
    {
        $client = Auth::guard('client')->user();

        if ($this->site->client_id !== $client->id) {
            abort(404);
        }

        try {
            $url = $sso->createLoginUrl($this->site, $client->id);
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        return redirect()->away($url);
    }

    public function saveMonitorSettings(ClientActivityService $activity): void
    {
        if (! $this->site->hasPaidSubscription()) {
            return;
        }

        $client   = Auth::guard('client')->user();
        $interval = MonitorSettings::normalizeInterval($this->site, $this->monitorInterval);
        $region   = MonitorSettings::normalizeRegion($this->site, $this->monitorRegion);

        $this->site->update([
            'monitor_interval_minutes' => $interval,
            'monitor_region'           => $region,
        ]);

        $this->monitorInterval = $interval;
        $this->monitorRegion   = $region;
        $this->monitorSettingsSaved = true;
        $this->site->refresh();

        $activity->log(
            $client,
            'monitor_settings_updated',
            'Monitoring settings updated',
            MonitorSettings::intervalLabel($interval) . ' · ' . MonitorSettings::regionLabel($region),
            $this->site,
            ['interval_minutes' => $interval, 'region' => $region],
        );
    }

    public function toggleMonitoringPause(ClientActivityService $activity): void
    {
        if (! $this->site->hasPaidSubscription()) {
            return;
        }

        $client = Auth::guard('client')->user();
        $paused = ! $this->site->monitoring_paused;

        $this->site->update([
            'monitoring_paused'    => $paused,
            'monitoring_paused_at' => $paused ? now() : null,
        ]);

        $this->site->refresh();

        $activity->log(
            $client,
            $paused ? 'monitoring_paused' : 'monitoring_resumed',
            $paused ? 'Monitoring paused' : 'Monitoring resumed',
            $paused
                ? 'Uptime checks and down alerts are on hold for this site.'
                : 'Uptime checks will run on your saved schedule.',
            $this->site,
        );

        session()->flash('success', $paused
            ? 'Monitoring paused. Uptime checks and down alerts are on hold.'
            : 'Monitoring resumed. Checks will run on your saved schedule.');
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

    public function saveCredentials(ClientActivityService $activity): void
    {
        $client = Auth::guard('client')->user();

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

        $activity->log(
            $client,
            'credentials_updated',
            'Hosting credentials updated',
            'Connection details saved for our team.',
            $this->site,
        );

        $this->credentialsSaved = true;
        $this->showCredentialsModal = false;
    }

    public function render(): \Illuminate\View\View
    {
        $activityTypes = ['heartbeat_missed', 'site_recovered'];

        $this->site->load([
            'events' => function ($q) {
                if ($this->tab === 'activity') {
                    $q->whereNotIn('type', ['heartbeat_missed', 'site_recovered']);
                }

                $q->latest()->limit($this->tab === 'activity' ? 50 : 8);
            },
            'backups' => fn ($q) => $q->latest()->limit(20),
            'reports' => fn ($q) => $q->latest()->limit(20),
        ]);

        $overviewEvents = $this->site->events
            ->whereNotIn('type', $activityTypes)
            ->take(5);

        $uptimeIncidents  = collect();
        $uptimeProbes     = collect();
        $uptimeDailyBars  = [];
        $periodUptimePct  = null;
        $allowedIntervals = MonitorSettings::allowedIntervals($this->site);
        $allowedRegions   = MonitorSettings::allowedRegions($this->site);

        if ($this->tab === 'monitoring' && $this->site->hasPaidSubscription()) {
            $uptimeIncidents = $this->site->events()
                ->where('type', 'uptime_kuma_alert')
                ->latest()
                ->limit(20)
                ->get();

            $uptimeProbes = SiteUptimeProbe::where('site_id', $this->site->id)
                ->where('checked_at', '>=', now()->subDays(14))
                ->orderBy('checked_at')
                ->get();

            $uptimeDailyBars = SiteUptimeChart::dailyBars($this->site->id, 14);
            $periodUptimePct = SiteUptimeChart::periodUptimePercent($uptimeProbes);
        }

        return view('livewire.portal.site-show', [
            'site'             => $this->site,
            'recentEvents'     => $this->site->events,
            'overviewEvents'   => $overviewEvents,
            'backups'          => $this->site->backups,
            'reports'          => $this->site->reports,
            'latestBackup'     => $this->site->latestBackup,
            'plans'            => Plan::where('is_active', true)->orderBy('price_monthly')->get(),
            'stripeTestMode'   => StripeConfig::isTestMode(),
            'canOpenWpAdmin'   => app(WordPressSsoService::class)->canLogin($this->site),
            'uptimeIncidents'  => $uptimeIncidents,
            'uptimeProbes'     => $uptimeProbes,
            'uptimeDailyBars'  => $uptimeDailyBars,
            'periodUptimePct'  => $periodUptimePct,
            'allowedIntervals' => $allowedIntervals,
            'allowedRegions'   => $allowedRegions,
        ])->layout('portal.layouts.app');
    }
}
