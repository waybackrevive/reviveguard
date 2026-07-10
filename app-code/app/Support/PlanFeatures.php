<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Site;

/**
 * Single gate for plan capabilities — aligned with PlanSeeder features JSON.
 */
final class PlanFeatures
{
    private function __construct(private readonly Plan $plan) {}

    public static function for(?Plan $plan): self
    {
        return new self($plan ?? new Plan(['slug' => 'monitor', 'features' => []]));
    }

    public static function forSite(Site $site): self
    {
        $site->loadMissing('plan');

        return self::for($site->plan);
    }

    public function slug(): string
    {
        return $this->plan->slug ?? 'monitor';
    }

    public function retentionDays(): int
    {
        return (int) ($this->plan->features['backup_retention_days'] ?? 30);
    }

    /** @return 'daily'|'weekly'|'twice_monthly' */
    public function backupSchedule(): string
    {
        return match ($this->plan->features['backup_frequency'] ?? 'monthly') {
            'daily'         => 'daily',
            'weekly'        => 'weekly',
            'twice_monthly' => 'twice_monthly',
            default         => 'twice_monthly',
        };
    }

    /**
     * Cap on automated backups per calendar month (Monitor = 2). Null = interval-only.
     */
    public function backupsPerMonth(): ?int
    {
        $value = $this->plan->features['backups_per_month'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    public function minDaysBetweenBackups(): int
    {
        return match ($this->backupSchedule()) {
            'daily'         => 1,
            'weekly'        => 7,
            'twice_monthly' => 14,
            default         => 28,
        };
    }

    public function includesAutoBackup(): bool
    {
        return (bool) ($this->plan->features['uptime_monitoring'] ?? true);
    }

    public function canAutoUpdate(): bool
    {
        return (bool) ($this->plan->features['wp_core_updates'] ?? false)
            || (bool) ($this->plan->features['wp_plugin_updates'] ?? false);
    }

    public function canMalwareScan(): bool
    {
        return (bool) ($this->plan->features['malware_scan'] ?? false);
    }

    public function canBrokenLinkAudit(): bool
    {
        return (bool) ($this->plan->features['broken_link_audit'] ?? false);
    }

    public function isShield(): bool
    {
        return $this->slug() === 'shield';
    }

    public function contentEditMinutesMonthly(): int
    {
        return (int) ($this->plan->features['content_edit_minutes_monthly'] ?? 0);
    }

    public function emergencyRestoreSlaHours(): ?int
    {
        $hours = $this->plan->features['emergency_restore_sla_hours'] ?? null;

        return $hours !== null ? (int) $hours : null;
    }

    public function backupFrequencyLabel(): string
    {
        return match ($this->backupSchedule()) {
            'daily'         => 'Daily',
            'weekly'        => 'Weekly',
            'twice_monthly' => 'Twice monthly',
            default         => 'Monthly',
        };
    }

    public function portalRetentionCopy(): string
    {
        $days = $this->retentionDays();

        return match ($this->slug()) {
            'monitor' => "Your site is backed up twice per month. Files are kept for {$days} days.",
            'guard'   => "Your site is backed up weekly. Files are kept for {$days} days.",
            'shield'  => "Your site is backed up daily. Files are kept for {$days} days.",
            default   => "Backups are retained for {$days} days.",
        };
    }

    /** Days after which a successful backup is considered stale for restore readiness. */
    public function restoreReadinessMaxAgeDays(): int
    {
        return min($this->retentionDays(), match ($this->backupSchedule()) {
            'daily'         => 2,
            'weekly'        => 10,
            'twice_monthly' => 20,
            default         => 35,
        });
    }
}
