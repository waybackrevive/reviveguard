<?php

namespace App\Support;

use App\Models\Plan;

/**
 * Keep plan Stripe price IDs in sync with config (survives config:cache on production).
 */
final class PlanStripePriceSync
{
    /** @var list<string> */
    private const SLUGS = ['monitor', 'guard', 'shield'];

    public static function syncFromConfig(): int
    {
        $updated = 0;

        foreach (self::SLUGS as $slug) {
            $plan = Plan::where('slug', $slug)->first();

            if (! $plan) {
                continue;
            }

            $changes = [];

            $testPrice = self::configPrice($slug, true);
            $livePrice = self::configPrice($slug, false);

            if (StripePriceId::isValid($testPrice) && $plan->stripe_test_price_id !== $testPrice) {
                $changes['stripe_test_price_id'] = $testPrice;
            }

            if (StripePriceId::isValid($livePrice) && $plan->stripe_price_id !== $livePrice) {
                $changes['stripe_price_id'] = $livePrice;
            }

            if ($changes !== []) {
                $plan->update($changes);
                $updated++;
            }
        }

        return $updated;
    }

    public static function configPrice(string $slug, bool $test): ?string
    {
        $mode = $test ? 'test' : 'live';
        $value = config("services.stripe.prices.{$slug}.{$mode}");

        return is_string($value) && $value !== '' ? $value : null;
    }
}
