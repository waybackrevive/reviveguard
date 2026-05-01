<?php

namespace App\Livewire\Portal;

use App\Jobs\OnboardClientJob;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * SiteWizard — 3-step onboarding wizard for adding a new site.
 *
 * Step 1: Enter site URL + confirm credentials
 * Step 2: Download + install WP plugin, enter agent key
 * Step 3: Confirmation (first heartbeat check)
 */
class SiteWizard extends Component
{
    // Wizard state
    public int $step = 1;

    // Step 1 fields
    public string $siteUrl        = '';
    public string $siteLabel      = '';
    public string $confirmMessage = '';

    // Step 2: show plugin download link + manual agent key entry
    public string $agentKeyInput = '';
    public ?Site  $pendingSite   = null;

    // Step 3: status
    public bool   $heartbeatReceived = false;
    public string $statusMessage     = '';

    protected function rules(): array
    {
        return [
            'siteUrl'   => ['required', 'url', 'max:500'],
            'siteLabel' => ['nullable', 'string', 'max:100'],
        ];
    }

    // ── Step 1: submit URL ────────────────────────────────────────────────────

    public function submitStep1(): void
    {
        $this->validateOnly('siteUrl');
        $this->validateOnly('siteLabel');

        $client = Auth::guard('client')->user();

        // Prevent duplicate sites
        $existing = Site::where('client_id', $client->id)
            ->where('url', rtrim($this->siteUrl, '/'))
            ->first();

        if ($existing) {
            $this->addError('siteUrl', 'This site URL is already added to your account.');
            return;
        }

        // Create the pending site record
        $this->pendingSite = Site::create([
            'tenant_id' => $client->tenant_id,
            'client_id' => $client->id,
            'url'       => rtrim($this->siteUrl, '/'),
            'label'     => $this->siteLabel ?: parse_url($this->siteUrl, PHP_URL_HOST),
            'status'    => 'pending', // No heartbeat yet
        ]);

        $this->step = 2;
    }

    // ── Step 2: verify agent key ──────────────────────────────────────────────

    public function submitStep2(): void
    {
        if (! $this->pendingSite) {
            $this->step = 1;
            return;
        }

        $this->validate(['agentKeyInput' => ['required', 'string', 'size:64']]);

        // Check agent key matches the site record
        if ($this->agentKeyInput !== $this->pendingSite->agent_key) {
            $this->addError('agentKeyInput', 'Agent key does not match. Double-check the value in your plugin settings.');
            return;
        }

        // Dispatch onboard job — creates Uptime Kuma monitor, sends confirmation email
        OnboardClientJob::dispatch(
            Auth::guard('client')->user()->id,
            null,
            false
        );

        $this->statusMessage = 'Plugin connected! Waiting for the first heartbeat...';
        $this->step = 3;
    }

    // ── Step 3: poll for heartbeat ────────────────────────────────────────────

    public function checkHeartbeat(): void
    {
        if (! $this->pendingSite) {
            return;
        }

        $this->pendingSite->refresh();
        if ($this->pendingSite->last_heartbeat_at !== null) {
            $this->heartbeatReceived = true;
            $this->statusMessage     = 'Your site is live and being monitored!';

            // Mark client onboarding complete
            $client = Auth::guard('client')->user();
            if (! $client->onboarding_completed_at) {
                $client->update(['onboarding_completed_at' => now()]);
            }

            $this->dispatch('wizard-completed');
        }
    }

    public function cancel(): void
    {
        // Clean up pending site if wizard cancelled before heartbeat
        if ($this->pendingSite && $this->pendingSite->last_heartbeat_at === null) {
            $this->pendingSite->delete();
        }

        $this->dispatch('wizard-completed');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.site-wizard', [
            'pluginDownloadUrl' => config('app.url') . '/downloads/reviveguard-agent.zip',
            'agentKey'          => $this->pendingSite?->agent_key,
        ]);
    }
}
