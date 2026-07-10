<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Enums\CommandType;
use App\Enums\SiteType;
use App\Jobs\RunBrokenLinkAudit;
use App\Jobs\RunExternalMalwareScan;
use App\Models\Backup;
use App\Models\Event;
use App\Models\Site;
use App\Support\PlanFeatures;
use Illuminate\Support\Carbon;

/**
 * Decides which protected sites need automated backups or WP updates queued.
 */
final class MaintenanceScheduler
{
    public function __construct(
        private readonly SiteCommandQueueService $commands,
    ) {}

    public function isBackupDue(Site $site): bool
    {
        $features = PlanFeatures::forSite($site);

        if (! $features->includesAutoBackup()) {
            return false;
        }

        if (! $site->hasPaidSubscription() || ! $site->hasAgentConnected()) {
            return false;
        }

        if ($this->commands->hasPending($site, \App\Enums\CommandType::RUN_BACKUP)) {
            return false;
        }

        $monthStart = now()->startOfMonth();

        $successfulThisMonth = Backup::query()
            ->where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->where('type', 'scheduled')
            ->where('completed_at', '>=', $monthStart)
            ->count();

        $perMonth = $features->backupsPerMonth();

        if ($perMonth !== null && $successfulThisMonth >= $perMonth) {
            return false;
        }

        $lastSuccess = Backup::query()
            ->where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->orderByDesc('completed_at')
            ->first();

        if ($lastSuccess?->completed_at) {
            $daysSince = $lastSuccess->completed_at->diffInDays(now());

            if ($daysSince < $features->minDaysBetweenBackups()) {
                return false;
            }
        }

        return true;
    }

    public function isWpUpdateDue(Site $site, ?Carbon $now = null): bool
    {
        $now ??= now();

        if ($now->dayOfWeek !== Carbon::SUNDAY) {
            return false;
        }

        $features = PlanFeatures::forSite($site);

        if (! $features->canAutoUpdate()) {
            return false;
        }

        if ($site->type !== SiteType::WORDPRESS) {
            return false;
        }

        if (! $site->hasPaidSubscription() || ! $site->hasAgentConnected()) {
            return false;
        }

        if ($this->commands->hasPending($site, \App\Enums\CommandType::RUN_WP_UPDATES)) {
            return false;
        }

        $lastUpdate = $site->commands()
            ->where('type', \App\Enums\CommandType::RUN_WP_UPDATES)
            ->where('status', \App\Enums\CommandStatus::SUCCESS)
            ->orderByDesc('completed_at')
            ->first();

        if ($lastUpdate?->completed_at && $lastUpdate->completed_at->greaterThan($now->copy()->subDays(6))) {
            return false;
        }

        return true;
    }

    public function isMalwareScanDue(Site $site): bool
    {
        $features = PlanFeatures::forSite($site);

        if (! $features->canMalwareScan()) {
            return false;
        }

        if (! $site->hasPaidSubscription()) {
            return false;
        }

        if ($site->type === SiteType::WORDPRESS) {
            if (! $site->hasAgentConnected()) {
                return false;
            }

            if ($this->commands->hasPending($site, CommandType::RUN_MALWARE_SCAN)) {
                return false;
            }
        }

        $lastScan = Event::query()
            ->where('site_id', $site->id)
            ->whereIn('type', ['malware_scan_complete', 'malware_scan_alert', 'malware_scan_failed'])
            ->orderByDesc('created_at')
            ->first();

        if ($lastScan?->created_at && $lastScan->created_at->greaterThan(now()->subDays(6))) {
            return false;
        }

        return true;
    }

    public function isBrokenLinkAuditDue(Site $site): bool
    {
        $features = PlanFeatures::forSite($site);

        if (! $features->canBrokenLinkAudit()) {
            return false;
        }

        if (! $site->hasPaidSubscription()) {
            return false;
        }

        $ranThisMonth = Event::query()
            ->where('site_id', $site->id)
            ->whereIn('type', ['broken_link_audit_complete', 'broken_link_audit_failed'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->exists();

        return ! $ranThisMonth;
    }

    public function queueDueMalwareScans(): int
    {
        $queued = 0;

        Site::protected()
            ->where('is_active', true)
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$queued): void {
                foreach ($sites as $site) {
                    if (! $this->isMalwareScanDue($site)) {
                        continue;
                    }

                    if ($site->type === SiteType::WORDPRESS) {
                        if ($this->commands->queueMalwareScan($site, 'scheduled')) {
                            $queued++;
                        }
                    } else {
                        RunExternalMalwareScan::dispatch($site->id, 'scheduled');
                        $queued++;
                    }
                }
            });

        return $queued;
    }

    public function queueDueBrokenLinkAudits(): int
    {
        $queued = 0;

        Site::protected()
            ->where('is_active', true)
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$queued): void {
                foreach ($sites as $site) {
                    if ($this->isBrokenLinkAuditDue($site)) {
                        RunBrokenLinkAudit::dispatch($site->id, 'scheduled');
                        $queued++;
                    }
                }
            });

        return $queued;
    }

    public function queueDueBackups(): int
    {
        $queued = 0;

        Site::protected()
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$queued): void {
                foreach ($sites as $site) {
                    if ($this->isBackupDue($site)) {
                        if ($this->commands->queueBackup($site, 'scheduled')) {
                            $queued++;
                        }
                    }
                }
            });

        return $queued;
    }

    public function queueDueWpUpdates(): int
    {
        $queued = 0;

        Site::protected()
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$queued): void {
                foreach ($sites as $site) {
                    if ($this->isWpUpdateDue($site)) {
                        if ($this->commands->queueWpUpdates($site, 'scheduled')) {
                            $queued++;
                        }
                    }
                }
            });

        return $queued;
    }

    /**
     * Count maintenance tasks due on the next scheduler pass (read-only — for admin dashboard).
     *
     * @return array{backups: int, updates: int, malware_scans: int, broken_links: int, quarterly: int}
     */
    public function dueCounts(): array
    {
        $counts = [
            'backups'       => 0,
            'updates'       => 0,
            'malware_scans' => 0,
            'broken_links'  => 0,
            'quarterly'     => 0,
        ];

        $quarterly = app(QuarterlyAuditService::class);

        Site::protected()
            ->where('is_active', true)
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$counts, $quarterly): void {
                foreach ($sites as $site) {
                    if ($this->isBackupDue($site)) {
                        $counts['backups']++;
                    }
                    if ($this->isWpUpdateDue($site)) {
                        $counts['updates']++;
                    }
                    if ($this->isMalwareScanDue($site)) {
                        $counts['malware_scans']++;
                    }
                    if ($this->isBrokenLinkAuditDue($site)) {
                        $counts['broken_links']++;
                    }
                    if ($quarterly->isDue($site)) {
                        $counts['quarterly']++;
                    }
                }
            });

        return $counts;
    }
}
