<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Site;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dispatched when a Stripe subscription is activated (checkout or webhook).
 */
class OnboardClientJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $clientId,
        public readonly string $subscriptionId,
        public readonly bool   $isNewClient = false,
    ) {}

    public function handle(NotificationService $notifications): void
    {
        $client = Client::find($this->clientId);

        if (! $client) {
            Log::warning('OnboardClientJob: client not found', ['client_id' => $this->clientId]);

            return;
        }

        $subscription = Subscription::with(['plan', 'site'])->find($this->subscriptionId);

        if (! $subscription) {
            Log::warning('OnboardClientJob: subscription not found', ['subscription_id' => $this->subscriptionId]);

            return;
        }

        $site = $subscription->site;

        if (! $site) {
            $rawToken = Str::random(64);
            $site = Site::create([
                'tenant_id'         => $client->tenant_id,
                'client_id'         => $client->id,
                'plan_id'           => $subscription->plan_id,
                'subscription_id'   => $subscription->id,
                'name'              => $client->name . "'s Website",
                'url'               => '',
                'status'            => SiteStatus::PENDING,
                'agent_token'       => hash('sha256', $rawToken),
                'agent_token_last4' => substr($rawToken, -4),
                'is_active'         => true,
            ]);

            $subscription->update(['site_id' => $site->id]);
            Log::info('OnboardClientJob: site record created', ['site_id' => $site->id]);
        }

        RefreshSiteHealthJob::dispatch($site->id);

        try {
            if ($this->isNewClient) {
                $rawActivationToken = Str::random(48);
                $client->update([
                    'activation_token'        => Hash::make($rawActivationToken),
                    'activation_expires_at' => now()->addHours(72),
                ]);

                $activationUrl = route('portal.activate', [
                    'client' => $client->id,
                    'token'  => $rawActivationToken,
                ]);

                $notifications->sendWelcome($client, $subscription->plan, $activationUrl);
            } else {
                $notifications->sendPlanUpdated($client, $subscription->plan, $subscription);
            }
        } catch (\Throwable $e) {
            Log::error('OnboardClientJob: notification failed', ['error' => $e->getMessage()]);
        }

        Log::info('OnboardClientJob: completed', [
            'client_id' => $client->id,
            'site_id'   => $site->id,
        ]);
    }
}
