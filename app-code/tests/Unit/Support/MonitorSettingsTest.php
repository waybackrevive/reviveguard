<?php

namespace Tests\Unit\Support;

use App\Models\Plan;
use App\Models\Site;
use App\Support\MonitorSettings;
use Tests\TestCase;

class MonitorSettingsTest extends TestCase
{
    private function siteWithPlan(string $slug): Site
    {
        $site = new Site();
        $site->setRelation('plan', new Plan(['slug' => $slug]));

        return $site;
    }

    /** @test */
    public function monitor_plan_allows_10_and_30_minute_intervals_only(): void
    {
        $site = $this->siteWithPlan('monitor');

        $this->assertSame([10, 30], MonitorSettings::allowedIntervals($site));
        $this->assertSame(10, MonitorSettings::fastestInterval($site));
        $this->assertSame(30, MonitorSettings::slowestInterval($site));
    }

    /** @test */
    public function guard_plan_minimum_is_five_minutes(): void
    {
        $site = $this->siteWithPlan('guard');

        $this->assertSame([5, 10, 30], MonitorSettings::allowedIntervals($site));
        $this->assertSame(5, MonitorSettings::fastestInterval($site));
    }

    /** @test */
    public function shield_plan_minimum_is_two_minutes(): void
    {
        $site = $this->siteWithPlan('shield');

        $this->assertContains(2, MonitorSettings::allowedIntervals($site));
        $this->assertSame(2, MonitorSettings::fastestInterval($site));
    }

    /** @test */
    public function normalize_interval_clamps_invalid_values_to_plan_default(): void
    {
        $site = $this->siteWithPlan('monitor');

        $this->assertSame(10, MonitorSettings::normalizeInterval($site, 5));
        $this->assertSame(30, MonitorSettings::normalizeInterval($site, 30));
    }

    /** @test */
    public function defaults_for_plan_match_minimum_interval(): void
    {
        $this->assertSame(10, MonitorSettings::defaultsForPlan(new Plan(['slug' => 'monitor']))['monitor_interval_minutes']);
        $this->assertSame(5, MonitorSettings::defaultsForPlan(new Plan(['slug' => 'guard']))['monitor_interval_minutes']);
        $this->assertSame(2, MonitorSettings::defaultsForPlan(new Plan(['slug' => 'shield']))['monitor_interval_minutes']);
    }
}
