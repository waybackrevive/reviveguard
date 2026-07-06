<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;

/**
 * Stripe subscription metadata must be flat string key => string value only.
 */
final class StripeSubscriptionMetadata
{
    /** @return array<string, string> */
    public static function forSitePlan(Client $client, Site $site, Plan $plan, string $tenantId): array
    {
        return self::stringify([
            'client_id' => $client->id,
            'site_id'   => $site->id,
            'plan_id'   => $plan->id,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, string>
     */
    public static function stringify(array $metadata): array
    {
        $out = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_string($value) || is_int($value) || is_float($value)) {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }
}
