<?php

namespace App\Support;

use App\Models\PlatformSetting;

/**
 * Resolves Stripe credentials for live vs test mode.
 *
 * Test mode can be toggled in Admin → Platform Settings without redeploying.
 * Falls back to STRIPE_TEST_MODE in .env when no platform setting exists.
 */
class StripeConfig
{
    public static function isTestMode(): bool
    {
        return PlatformSetting::getBool('stripe_test_mode', (bool) config('services.stripe.test_mode', false));
    }

    public static function secretKey(): string
    {
        if (self::isTestMode()) {
            return (string) (
                PlatformSetting::get('stripe_test_secret_key')
                ?: config('services.stripe.test_secret', '')
            );
        }

        return (string) (
            PlatformSetting::get('stripe_secret_key')
            ?: config('services.stripe.secret', '')
        );
    }

    public static function publishableKey(): string
    {
        if (self::isTestMode()) {
            return (string) config('services.stripe.test_key', '');
        }

        return (string) config('services.stripe.key', '');
    }

    public static function webhookSecret(): string
    {
        if (self::isTestMode()) {
            return (string) (
                PlatformSetting::get('stripe_test_webhook_secret')
                ?: config('services.stripe.test_webhook_secret', '')
            );
        }

        return (string) (
            PlatformSetting::get('stripe_webhook_secret')
            ?: config('services.stripe.webhook_secret', '')
        );
    }

    public static function modeLabel(): string
    {
        return self::isTestMode() ? 'Test' : 'Live';
    }
}
