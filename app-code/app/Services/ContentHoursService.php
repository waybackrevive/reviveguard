<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Plan;
use App\Support\PlanFeatures;

/**
 * Shield content-edit hour allowance — 120 min/month by default.
 */
final class ContentHoursService
{
    public function monthlyAllowance(?Plan $plan): int
    {
        if (! $plan || PlanFeatures::for($plan)->slug() !== 'shield') {
            return 0;
        }

        return (int) ($plan->features['content_edit_minutes_monthly'] ?? 120);
    }

    public function ensureAllocation(Client $client, ?Plan $plan = null): void
    {
        $plan ??= $client->bestSupportPlan();
        $allowance = $this->monthlyAllowance($plan);

        if ($allowance <= 0) {
            return;
        }

        $needsReset = $client->content_minutes_reset_at === null
            || $client->content_minutes_reset_at->lt(now()->startOfMonth());

        if ($needsReset || $client->content_minutes_remaining === null) {
            $client->update([
                'content_minutes_remaining' => $allowance,
                'content_minutes_reset_at'    => now(),
            ]);
        }
    }

    public function resetAllShieldClients(): int
    {
        $reset = 0;

        Client::query()
            ->where('tenant_id', config('app.tenant_id'))
            ->where('is_active', true)
            ->chunkById(50, function ($clients) use (&$reset): void {
                foreach ($clients as $client) {
                    $plan = $client->bestSupportPlan();

                    if ($this->monthlyAllowance($plan) <= 0) {
                        continue;
                    }

                    $this->ensureAllocation($client->fresh(), $plan);
                    $reset++;
                }
            });

        return $reset;
    }

    public function deduct(Client $client, int $minutes, ?Plan $plan = null): bool
    {
        if ($minutes <= 0) {
            return true;
        }

        $plan ??= $client->bestSupportPlan();
        $this->ensureAllocation($client, $plan);

        $remaining = (int) ($client->content_minutes_remaining ?? 0);
        $newValue  = max(0, $remaining - $minutes);

        $client->update(['content_minutes_remaining' => $newValue]);

        return true;
    }

    public function remainingMinutes(Client $client): ?int
    {
        $plan = $client->bestSupportPlan();

        if ($this->monthlyAllowance($plan) <= 0) {
            return null;
        }

        $this->ensureAllocation($client, $plan);

        return (int) ($client->fresh()->content_minutes_remaining ?? 0);
    }
}
