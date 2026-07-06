<?php

namespace Tests\Unit\Support;

use App\Models\Plan;
use App\Support\PlanCatalog;
use Tests\TestCase;

class PlanCatalogTest extends TestCase
{
    /** @test */
    public function comparison_rows_include_all_plan_slugs(): void
    {
        foreach (PlanCatalog::comparisonRows() as $row) {
            $this->assertArrayHasKey('label', $row);
            $this->assertArrayHasKey('monitor', $row);
            $this->assertArrayHasKey('guard', $row);
            $this->assertArrayHasKey('shield', $row);
        }
    }

    /** @test */
    public function is_upgrade_detects_higher_tier(): void
    {
        $monitor = new Plan(['slug' => 'monitor']);
        $guard   = new Plan(['slug' => 'guard']);
        $shield  = new Plan(['slug' => 'shield']);

        $this->assertTrue(PlanCatalog::isUpgrade($monitor, $guard));
        $this->assertTrue(PlanCatalog::isUpgrade($guard, $shield));
        $this->assertFalse(PlanCatalog::isUpgrade($shield, $guard));
        $this->assertFalse(PlanCatalog::isUpgrade($guard, $monitor));
    }

    /** @test */
    public function included_and_not_included_return_features_for_known_plans(): void
    {
        foreach (['monitor', 'guard', 'shield'] as $slug) {
            $plan = new Plan([
                'slug'     => $slug,
                'features' => [
                    'backup_frequency'      => $slug === 'monitor' ? 'monthly' : 'daily',
                    'backup_retention_days' => 30,
                ],
            ]);

            $this->assertNotEmpty(PlanCatalog::included($plan));
            $this->assertNotEmpty(PlanCatalog::notIncluded($plan));
        }
    }

    /** @test */
    public function upgrade_gains_lists_differences(): void
    {
        $monitor = new Plan(['slug' => 'monitor']);
        $guard   = new Plan(['slug' => 'guard']);

        $gains = PlanCatalog::upgradeGains($monitor, $guard);

        $this->assertNotEmpty($gains);
        $this->assertStringContainsString('Daily backups', implode(' ', $gains));
    }

    /** @test */
    public function is_downgrade_detects_lower_tier(): void
    {
        $monitor = new Plan(['slug' => 'monitor']);
        $guard   = new Plan(['slug' => 'guard']);
        $shield  = new Plan(['slug' => 'shield']);

        $this->assertTrue(PlanCatalog::isDowngrade($shield, $guard));
        $this->assertTrue(PlanCatalog::isDowngrade($guard, $monitor));
        $this->assertFalse(PlanCatalog::isDowngrade($monitor, $guard));
    }

    /** @test */
    public function can_change_plan_requires_different_tier(): void
    {
        $monitor = tap(new Plan(['slug' => 'monitor']), fn (Plan $p) => $p->id = '11111111-1111-1111-1111-111111111111');
        $guard   = tap(new Plan(['slug' => 'guard']), fn (Plan $p) => $p->id = '22222222-2222-2222-2222-222222222222');

        $this->assertTrue(PlanCatalog::canChangePlan($monitor, $guard));
        $this->assertFalse(PlanCatalog::canChangePlan($monitor, $monitor));
    }

    /** @test */
    public function downgrade_confirm_message_mentions_credit(): void
    {
        $shield = new Plan(['slug' => 'shield', 'name' => 'Shield', 'price_monthly' => 179]);
        $guard  = new Plan(['slug' => 'guard', 'name' => 'Guard', 'price_monthly' => 99]);

        $msg = PlanCatalog::downgradeConfirmMessage($shield, $guard);

        $this->assertStringContainsString('credit', strtolower($msg));
        $this->assertStringContainsString('Guard', $msg);
    }

    /** @test */
    public function plan_change_modal_data_includes_billing_context(): void
    {
        $shield = new Plan(['slug' => 'shield', 'name' => 'Shield', 'price_monthly' => 179]);
        $guard  = new Plan(['slug' => 'guard', 'name' => 'Guard', 'price_monthly' => 99]);

        $modal = PlanCatalog::planChangeModalData($shield, $guard, 'example.com');

        $this->assertFalse($modal['is_upgrade']);
        $this->assertSame('Switch to Guard', $modal['title']);
        $this->assertSame('example.com', $modal['site_name']);
        $this->assertStringContainsString('credit', strtolower($modal['billing_note']));
    }

    /** @test */
    public function upgrade_confirm_message_includes_plan_names(): void
    {
        $monitor = new Plan(['slug' => 'monitor', 'name' => 'Monitor', 'price_monthly' => 49]);
        $guard   = new Plan(['slug' => 'guard', 'name' => 'Guard', 'price_monthly' => 99]);

        $msg = PlanCatalog::upgradeConfirmMessage($monitor, $guard);

        $this->assertStringContainsString('Monitor', $msg);
        $this->assertStringContainsString('Guard', $msg);
        $this->assertStringContainsString('prorated', strtolower($msg));
    }
}
