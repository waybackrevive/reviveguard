<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Site;
use App\Models\Subscription;
use App\Services\NotificationService;
use App\Services\UptimeKumaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dispatched when a Stripe subscription is activated (checkout or webhook).
 *
 * Responsibilities:
 *  1. Check if client already has a site record — create one if not
 *  2. Create an Uptime Kuma HTTP monitor (if service is configured)
 *  3. For new clients: generate magic activation link → send welcome email
 *  4. For existing clients (reactivation/upgrade): send plan-updated email
 */
class OnboardClientJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $clientId,
        public readonly string $subscriptionId,
        public readonly bool   $isNewClient = true,
    ) {}

    public function handle(UptimeKumaService $uptimeKuma, NotificationService $notifications): void
    {
        $client = Client::find($this->clientId);
        if (! $client) {
            Log::warning('OnboardClientJob: client not found', ['client_id' => $this->clientId]);
            return;
        }

        $subscription = Subscription::with('plan')->find($this->subscriptionId);

        // ── 1. Create site record if none exists ──────────────────────────────
        $site = Site::where('client_id', $client->id)->first();

        if (! $site) {
            $rawToken = Str::random(64);
            $site = Site::create([
                'tenant_id'         => $client->tenant_id,
                'client_id'         => $client->id,
                'plan_id'           => $subscription?->plan_id,
                'subscription_id'   => $subscription?->id,
                'name'              => $client->name . "'s Website",
                'url'               => '',  // Client must set this via portal or admin
                'status'            => SiteStatus::PENDING,
                'agent_token'       => hash('sha256', $rawToken),
                'agent_token_last4' => substr($rawToken, -4),
                'is_active'         => true,
            ]);

            Log::info('OnboardClientJob: site record created', ['site_id' => $site->id, 'client_id' => $client->id]);
        }

        // ── 2. Create Uptime Kuma monitor (if URL is set and no monitor yet) ──
        if ($site->url && ! $site->uptime_kuma_monitor_id) {
            $monitorId = $uptimeKuma->createMonitor($site->name, $site->url);
            if ($monitorId) {
                $site->update(['uptime_kuma_monitor_id' => $monitorId]);
                Log::info('OnboardClientJob: Uptime Kuma monitor created', [
                    'site_id'    => $site->id,
                    'monitor_id' => $monitorId,
                ]);
            }
        }

        // ── 3. Send appropriate notification ─────────────────────────────────
        try {
            if ($this->isNewClient) {
                // Generate one-time activation token (stored hashed, plain sent in email)
                $rawActivationToken = Str::random(48);
                $client->update([
                    'activation_token'      => Hash::make($rawActivationToken),
                    'activation_expires_at' => now()->addHours(72),
                ]);

                $activationUrl = route('portal.activate', [
                    'client' => $client->id,
                    'token'  => $rawActivationToken,
                ]);

                $notifications->sendWelcome($client, $subscription?->plan, $activationUrl);
            } else {
                // Existing client — reactivation or plan upgrade
                $notifications->sendPlanUpdated($client, $subscription?->plan, $subscription);
            }
        } catch (\Throwable $e) {
            Log::error('OnboardClientJob: notification failed', ['error' => $e->getMessage()]);
            // Non-fatal — don't fail the job over email
        }

        Log::info('OnboardClientJob: completed', [
            'client_id'  => $client->id,
            'is_new'     => $this->isNewClient,
        ]);
    }
}

