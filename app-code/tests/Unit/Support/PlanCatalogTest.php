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
    public function bullets_return_non_empty_for_known_plans(): void
    {
        foreach (['monitor', 'guard', 'shield'] as $slug) {
            $plan = new Plan(['slug' => $slug, 'features' => []]);
            $this->assertNotEmpty(PlanCatalog::bullets($plan));
        }
    }
}
