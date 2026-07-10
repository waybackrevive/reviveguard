<?php

namespace App\Services;

use App\Enums\CommandType;
use App\Models\Site;
use App\Models\SiteCommand;

/**
 * Queues agent commands — used by schedulers and admin actions share the same path.
 */
final class SiteCommandQueueService
{
    public function __construct(
        private readonly UpdateSafetyService $updateSafety,
    ) {}

    public function hasPending(Site $site, CommandType $type): bool
    {
        return SiteCommand::query()
            ->where('site_id', $site->id)
            ->where('type', $type)
            ->whereIn('status', [
                \App\Enums\CommandStatus::PENDING->value,
                \App\Enums\CommandStatus::SENT->value,
                \App\Enums\CommandStatus::EXECUTING->value,
            ])
            ->exists();
    }

    public function hasAnyPending(Site $site): bool
    {
        return SiteCommand::query()
            ->where('site_id', $site->id)
            ->whereIn('status', [
                \App\Enums\CommandStatus::PENDING->value,
                \App\Enums\CommandStatus::SENT->value,
                \App\Enums\CommandStatus::EXECUTING->value,
            ])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function queue(Site $site, CommandType $type, array $params = []): ?SiteCommand
    {
        if ($this->hasPending($site, $type)) {
            return null;
        }

        return SiteCommand::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => $type,
            'status'    => \App\Enums\CommandStatus::PENDING,
            'params'    => $params,
            'queued_at' => now(),
        ]);
    }

    public function queueBackup(Site $site, string $trigger = 'scheduled', array $extra = []): ?SiteCommand
    {
        return $this->queue($site, CommandType::RUN_BACKUP, array_merge(['trigger' => $trigger], $extra));
    }

    /**
     * Queue WP updates — auto-chains a pre-update backup when none exists in the last 24 hours.
     */
    public function queueWpUpdates(Site $site, string $trigger = 'scheduled'): ?SiteCommand
    {
        if ($this->hasPending($site, CommandType::RUN_WP_UPDATES)) {
            return null;
        }

        if (! $this->updateSafety->hasRecentSuccessfulBackup($site)) {
            if ($this->hasPending($site, CommandType::RUN_BACKUP)) {
                return null;
            }

            return $this->queueBackup($site, 'pre_update', [
                'chain_wp_update'    => true,
                'wp_update_trigger'  => $trigger,
            ]);
        }

        return $this->queue($site, CommandType::RUN_WP_UPDATES, ['trigger' => $trigger]);
    }

    public function queueRollbackRestore(Site $site, string $trigger = 'manual'): ?SiteCommand
    {
        if ($this->hasPending($site, CommandType::ROLLBACK_RESTORE)) {
            return null;
        }

        $backup = $this->updateSafety->rollbackTarget($site);

        if (! $backup?->b2_file_key) {
            return null;
        }

        return $this->queue($site, CommandType::ROLLBACK_RESTORE, [
            'trigger'   => $trigger,
            'backup_id' => $backup->id,
            'b2_path'   => $backup->b2_file_key,
            'b2_bucket' => $backup->b2_bucket,
        ]);
    }

    public function queueMalwareScan(Site $site, string $trigger = 'scheduled'): ?SiteCommand
    {
        return $this->queue($site, CommandType::RUN_MALWARE_SCAN, ['trigger' => $trigger]);
    }
}
