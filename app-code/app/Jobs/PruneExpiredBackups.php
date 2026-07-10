<?php

namespace App\Jobs;

use App\Enums\BackupStatus;
use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Marks backups past their retention date as expired.
 */
final class PruneExpiredBackups implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(): void
    {
        $count = Backup::query()
            ->where('status', BackupStatus::SUCCESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => BackupStatus::EXPIRED]);

        if ($count > 0) {
            Log::info("PruneExpiredBackups: marked {$count} backup(s) as expired");
        }
    }
}
