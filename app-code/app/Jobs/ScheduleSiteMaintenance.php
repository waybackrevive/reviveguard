<?php

namespace App\Jobs;

use App\Services\MaintenanceScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Daily maintenance pass — queues due backups and (on Sundays) WP updates.
 */
final class ScheduleSiteMaintenance implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(MaintenanceScheduler $scheduler): void
    {
        $backups = $scheduler->queueDueBackups();
        $updates = $scheduler->queueDueWpUpdates();
        $scans   = $scheduler->queueDueMalwareScans();
        $links   = $scheduler->queueDueBrokenLinkAudits();
        $quarterly = app(\App\Services\QuarterlyAuditService::class)->queueDueAudits();

        Log::info('ScheduleSiteMaintenance: queued maintenance commands', [
            'backups'      => $backups,
            'updates'      => $updates,
            'malware'      => $scans,
            'broken_links' => $links,
            'quarterly'    => $quarterly,
        ]);
    }
}
