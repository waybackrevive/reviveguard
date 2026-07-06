<?php

namespace App\Support;

/**
 * Stripe Checkout requires Price IDs (price_…), not Product IDs (prod_…).
 */
class StripePriceId
{
    public static function isValid(?string $id): bool
    {
        return is_string($id) && str_starts_with($id, 'price_') && strlen($id) > 6;
    }

    /**
     * Human-readable fix when an ID is present but wrong shape.
     */
    public static function describeProblem(?string $id, string $label = 'Stripe price'): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        if (str_starts_with($id, 'prod_')) {
            return "{$label}: \"{$id}\" is a Product ID (prod_…). Checkout needs a Price ID (price_…). "
                . 'In Stripe Dashboard → Products → open the plan → under Pricing, copy the price ID (starts with price_). '
                . 'Update .env STRIPE_TEST_PRICE_*_ID (or live STRIPE_PRICE_*_ID), then run: php artisan db:seed --class=PlanSeeder && php artisan config:clear';
        }

        if (! str_starts_with($id, 'price_')) {
            return "{$label}: \"{$id}\" must start with price_.";
        }

        return null;
    }
}
