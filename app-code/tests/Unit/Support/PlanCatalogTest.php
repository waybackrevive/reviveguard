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
