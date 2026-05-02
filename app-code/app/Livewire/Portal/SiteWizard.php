<?php

namespace App\Livewire\Portal;

use App\Enums\SiteStatus;
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
    public string $agentKey      = '';  // raw token generated at step 1, shown to user once
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

        // Require an active plan before creating a site
        if (! $client->activeSubscription()->exists()) {
            $this->addError('siteUrl', 'You need an active plan to add a website. Please purchase a plan first.');
            return;
        }
        $existing = Site::where('client_id', $client->id)
            ->where('url', rtrim($this->siteUrl, '/'))
            ->first();

        if ($existing) {
            $this->addError('siteUrl', 'This site URL is already added to your account.');
            return;
        }

        // Create the pending site record with a generated agent token
        $rawToken          = bin2hex(random_bytes(32)); // 64 hex chars
        $this->agentKey    = $rawToken;
        $this->pendingSite = Site::create([
            'tenant_id'         => $client->tenant_id,
            'client_id'         => $client->id,
            'name'              => $this->siteLabel ?: parse_url($this->siteUrl, PHP_URL_HOST),
            'url'               => rtrim($this->siteUrl, '/'),
            'status'            => \App\Enums\SiteStatus::PENDING,
            'agent_token'       => hash('sha256', $rawToken),
            'agent_token_last4' => substr($rawToken, -4),
            'is_active'         => true,
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

        // Check user confirmed the correct token (proves they copied it into the plugin)
        if ($this->agentKeyInput !== $this->agentKey) {
            $this->addError('agentKeyInput', 'Token does not match. Copy the exact token shown above and paste it here.');
            return;
        }

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
        if ($this->pendingSite->last_seen_at !== null) {
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
        ]);
    }
}
