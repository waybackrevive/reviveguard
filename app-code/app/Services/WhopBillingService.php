<?php

namespace App\Services;

use App\Jobs\OnboardClientJob;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles Whop subscription lifecycle events.
 *
 * Events processed:
 *  - membership.went_valid    → activate or create client + subscription
 *  - membership.went_invalid  → pause/deactivate
 *  - membership.was_banned    → cancel + deactivate
 */
class WhopBillingService
{
    private string $tenantId;

    public function __construct()
    {
        $this->tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
    }

    // ── Public event handlers ─────────────────────────────────────────────────

    /**
     * Membership went valid: new purchase OR reactivation after lapse.
     *
     * @param  array<string, mixed>  $data  The `data` object from Whop webhook payload
     */
    public function handleMembershipWentValid(array $data): void
    {
        $membershipId = $data['id'] ?? null;
        $planId       = $data['plan_id'] ?? ($data['plan']['id'] ?? null);
        $email        = $data['user']['email'] ?? null;
        $name         = $data['user']['name'] ?? ($data['user']['username'] ?? 'Client');
        $whopUserId   = $data['user']['id'] ?? null;
        $validUntil   = isset($data['renewal_period_end'])
            ? \Carbon\Carbon::createFromTimestamp((int) $data['renewal_period_end'])
            : null;

        if (! $membershipId || ! $email) {
            Log::warning('WhopBillingService: went_valid missing membership_id or email', ['data' => $data]);
            return;
        }

        $plan = $planId ? Plan::where('whop_plan_id', $planId)->first() : null;

        // Find or create client
        $client = Client::where('whop_member_id', $whopUserId)
            ->orWhere('email', $email)
            ->first();

        if (! $client) {
            $tempPassword = Str::random(16);
            $client = Client::create([
                'tenant_id'       => $this->tenantId,
                'name'            => (string) $name,
                'email'           => strtolower(trim((string) $email)),
                'portal_password' => Hash::make($tempPassword),
                'whop_member_id'  => $whopUserId,
                'is_active'       => true,
            ]);

            Log::info('WhopBillingService: new client created', ['client_id' => $client->id, 'email' => $client->email]);
        } else {
            $client->update([
                'whop_member_id' => $whopUserId ?? $client->whop_member_id,
                'is_active'      => true,
            ]);
        }

        // Create or update subscription record
        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();

        if ($subscription) {
            $subscription->update([
                'whop_status'      => 'active',
                'whop_valid_until' => $validUntil,
                'plan_id'          => $plan?->id ?? $subscription->plan_id,
                'suspended_at'     => null,
                'cancelled_at'     => null,
            ]);
        } else {
            $subscription = Subscription::create([
                'tenant_id'          => $this->tenantId,
                'client_id'          => $client->id,
                'plan_id'            => $plan?->id,
                'whop_membership_id' => $membershipId,
                'whop_plan_id'       => $planId,
                'whop_status'        => 'active',
                'whop_valid_until'   => $validUntil,
                'activated_at'       => now(),
            ]);
        }

        // Dispatch onboarding job (creates site record + Uptime Kuma monitor + welcome email)
        OnboardClientJob::dispatch($client->id, $subscription->id);

        Log::info('WhopBillingService: membership activated', [
            'client_id'      => $client->id,
            'membership_id'  => $membershipId,
            'plan'           => $plan?->slug ?? 'unknown',
        ]);
    }

    /**
     * Membership went invalid: payment failed, cancelled, or expired.
     * Marks subscription inactive but does NOT delete anything.
     *
     * @param  array<string, mixed>  $data
     */
    public function handleMembershipWentInvalid(array $data): void
    {
        $membershipId = $data['id'] ?? null;
        if (! $membershipId) {
            return;
        }

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if (! $subscription) {
            Log::info('WhopBillingService: went_invalid — no subscription found', ['membership_id' => $membershipId]);
            return;
        }

        $subscription->update([
            'whop_status'  => 'inactive',
            'suspended_at' => now(),
        ]);

        // Deactivate client portal access only if they have no other active subscriptions
        $hasActiveOther = Subscription::where('client_id', $subscription->client_id)
            ->where('id', '!=', $subscription->id)
            ->where('whop_status', 'active')
            ->exists();

        if (! $hasActiveOther) {
            $subscription->client?->update(['is_active' => false]);
        }

        Log::info('WhopBillingService: membership went invalid', ['membership_id' => $membershipId]);
    }

    /**
     * Membership was banned — permanent cancellation.
     *
     * @param  array<string, mixed>  $data
     */
    public function handleMembershipWasBanned(array $data): void
    {
        $membershipId = $data['id'] ?? null;
        if (! $membershipId) {
            return;
        }

        $subscription = Subscription::where('whop_membership_id', $membershipId)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'whop_status'  => 'banned',
            'cancelled_at' => now(),
        ]);

        $subscription->client?->update(['is_active' => false]);

        Log::warning('WhopBillingService: membership banned', ['membership_id' => $membershipId]);
    }
}
