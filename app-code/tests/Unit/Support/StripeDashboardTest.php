<?php

namespace Tests\Unit\Support;

use App\Support\StripeConfig;
use App\Support\StripeDashboard;
use Tests\TestCase;

class StripeDashboardTest extends TestCase
{
    public function test_subscription_and_invoice_urls_include_test_prefix_in_test_mode(): void
    {
        config(['services.stripe.test_mode' => true]);

        $prefix = StripeDashboard::prefix();

        $this->assertStringContainsString('test/', $prefix);
        $this->assertStringContainsString('test/', StripeDashboard::subscriptionUrl('sub_123'));
        $this->assertStringContainsString('subscriptions/sub_123', StripeDashboard::subscriptionUrl('sub_123'));
        $this->assertStringContainsString('invoices/in_123', StripeDashboard::invoiceUrl('in_123'));
    }
}
