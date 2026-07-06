<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use Tests\TestCase;

class PlanStripeCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.test_mode' => true]);
    }
    public function test_product_id_in_db_blocks_checkout_when_no_config_fallback(): void
    {
        config(['services.stripe.prices.guard.test' => null]);

        $plan = new Plan([
            'name'                 => 'Guard',
            'slug'                 => 'guard',
            'stripe_test_price_id' => 'prod_Upl40H24iBHE0E',
        ]);

        $this->assertFalse($plan->hasStripeCheckout());

        $reason = $plan->checkoutUnavailableReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('not set', strtolower($reason));
    }

    public function test_product_id_in_db_uses_config_fallback_when_available(): void
    {
        config(['services.stripe.prices.guard.test' => 'price_1GuardConfigOk']);

        $plan = new Plan([
            'name'                 => 'Guard',
            'slug'                 => 'guard',
            'stripe_test_price_id' => 'prod_Upl40H24iBHE0E',
        ]);

        $this->assertTrue($plan->hasStripeCheckout());
        $this->assertSame('price_1GuardConfigOk', $plan->resolvedStripePriceId());
    }

    public function test_valid_price_id_allows_checkout(): void
    {
        $plan = new Plan([
            'name'                 => 'Monitor',
            'slug'                 => 'monitor',
            'stripe_test_price_id' => 'price_1TestMonitorPlan',
        ]);

        $this->assertTrue($plan->hasStripeCheckout());
        $this->assertNull($plan->checkoutUnavailableReason());
    }
}
