<?php

namespace App\Support;

class StripeDashboard
{
    public static function prefix(): string
    {
        return StripeConfig::isTestMode() ? 'test/' : '';
    }

    public static function customerUrl(string $customerId): string
    {
        return 'https://dashboard.stripe.com/'.self::prefix().'customers/'.$customerId;
    }

    public static function subscriptionUrl(string $subscriptionId): string
    {
        return 'https://dashboard.stripe.com/'.self::prefix().'subscriptions/'.$subscriptionId;
    }

    public static function invoiceUrl(string $invoiceId): string
    {
        return 'https://dashboard.stripe.com/'.self::prefix().'invoices/'.$invoiceId;
    }
}
