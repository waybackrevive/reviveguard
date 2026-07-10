<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\Site;

/**
 * Pre-update backup checks and rollback target resolution.
 */
final class UpdateSafetyService
{
    public function hasRecentSuccessfulBackup(Site $site, int $hours = 24): bool
    {
        return Backup::query()
            ->where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->where('completed_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /** Latest snapshot taken immediately before an update run. */
    public function latestPreUpdateBackup(Site $site): ?Backup
    {
        return Backup::query()
            ->where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->where('type', 'pre_update')
            ->whereNotNull('b2_file_key')
            ->orderByDesc('completed_at')
            ->first();
    }

    /** Fallback when no pre_update snapshot exists (manual rollback). */
    public function latestRestorableBackup(Site $site): ?Backup
    {
        return Backup::query()
            ->where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->whereNotNull('b2_file_key')
            ->orderByDesc('completed_at')
            ->first();
    }

    public function rollbackTarget(Site $site, bool $preferPreUpdate = true): ?Backup
    {
        if ($preferPreUpdate) {
            return $this->latestPreUpdateBackup($site) ?? $this->latestRestorableBackup($site);
        }

        return $this->latestRestorableBackup($site);
    }
}
