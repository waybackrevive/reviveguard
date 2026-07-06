<?php

namespace Tests\Unit\Support;

use App\Models\Plan;
use App\Support\PlanStripePriceSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanStripePriceSyncTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function resolved_price_falls_back_to_config_when_db_empty(): void
    {
        config([
            'services.stripe.test_mode'           => true,
            'services.stripe.prices.shield.test'  => 'price_1ShieldFromConfig',
        ]);

        $plan = new Plan(['slug' => 'shield', 'name' => 'Shield', 'stripe_test_price_id' => null]);

        $this->assertSame('price_1ShieldFromConfig', $plan->resolvedStripePriceId());
        $this->assertTrue($plan->hasStripeCheckout());
    }

    /** @test */
    public function invalid_product_id_in_db_falls_back_to_config(): void
    {
        config([
            'services.stripe.test_mode'          => true,
            'services.stripe.prices.guard.test'  => 'price_1GuardFromConfig',
        ]);

        $plan = new Plan([
            'slug'                 => 'guard',
            'name'                 => 'Guard',
            'stripe_test_price_id' => 'prod_InvalidProductId',
        ]);

        $this->assertSame('price_1GuardFromConfig', $plan->resolvedStripePriceId());
    }

    /** @test */
    public function sync_writes_config_prices_to_database(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        config([
            'services.stripe.prices.monitor.test' => 'price_1MonitorSync',
            'services.stripe.prices.guard.test'   => 'price_1GuardSync',
            'services.stripe.prices.shield.test'  => 'price_1ShieldSync',
        ]);

        $updated = PlanStripePriceSync::syncFromConfig();

        $this->assertSame(3, $updated);
        $this->assertSame('price_1ShieldSync', Plan::where('slug', 'shield')->value('stripe_test_price_id'));
    }
}
