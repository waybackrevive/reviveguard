<?php

namespace Tests\Unit\Support;

use App\Models\Plan;
use App\Support\PlanFeatures;
use Tests\TestCase;

class PlanFeaturesTest extends TestCase
{
    /** @test */
    public function monitor_plan_has_twice_monthly_backups(): void
    {
        $features = PlanFeatures::for(new Plan([
            'slug'     => 'monitor',
            'features' => [
                'backup_frequency'      => 'twice_monthly',
                'backups_per_month'     => 2,
                'backup_retention_days' => 30,
            ],
        ]));

        $this->assertSame('twice_monthly', $features->backupSchedule());
        $this->assertSame(2, $features->backupsPerMonth());
        $this->assertSame(14, $features->minDaysBetweenBackups());
        $this->assertFalse($features->canAutoUpdate());
    }

    /** @test */
    public function guard_plan_has_weekly_backups_and_updates(): void
    {
        $features = PlanFeatures::for(new Plan([
            'slug'     => 'guard',
            'features' => [
                'backup_frequency'      => 'weekly',
                'backup_retention_days' => 90,
                'wp_core_updates'       => true,
                'malware_scan'          => true,
                'broken_link_audit'     => true,
            ],
        ]));

        $this->assertSame('weekly', $features->backupSchedule());
        $this->assertNull($features->backupsPerMonth());
        $this->assertSame(7, $features->minDaysBetweenBackups());
        $this->assertTrue($features->canAutoUpdate());
        $this->assertTrue($features->canMalwareScan());
        $this->assertTrue($features->canBrokenLinkAudit());
    }

    /** @test */
    public function monitor_plan_excludes_security_features(): void
    {
        $features = PlanFeatures::for(new Plan([
            'slug'     => 'monitor',
            'features' => [
                'malware_scan'      => false,
                'broken_link_audit' => false,
            ],
        ]));

        $this->assertFalse($features->canMalwareScan());
        $this->assertFalse($features->canBrokenLinkAudit());
    }

    /** @test */
    public function shield_plan_has_premium_features(): void
    {
        $features = PlanFeatures::for(new Plan([
            'slug'     => 'shield',
            'features' => [
                'emergency_restore_sla_hours'  => 4,
                'content_edit_minutes_monthly' => 120,
            ],
        ]));

        $this->assertTrue($features->isShield());
        $this->assertSame(4, $features->emergencyRestoreSlaHours());
        $this->assertSame(120, $features->contentEditMinutesMonthly());
    }

    /** @test */
    public function portal_retention_copy_describes_monitor_twice_monthly(): void
    {
        $copy = PlanFeatures::for(new Plan([
            'slug'     => 'monitor',
            'features' => ['backup_retention_days' => 30],
        ]))->portalRetentionCopy();

        $this->assertStringContainsString('twice per month', strtolower($copy));
        $this->assertStringContainsString('30', $copy);
    }
}
