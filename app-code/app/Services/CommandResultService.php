<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\EventSeverity;
use App\Models\Backup;
use App\Models\Event;
use App\Models\PlatformSetting;
use App\Models\Site;
use App\Models\SiteCommand;
use App\Support\PlanFeatures;
use Illuminate\Support\Facades\Log;

final class CommandResultService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly SiteCommandQueueService $commands,
        private readonly UpdateSafetyService $updateSafety,
        private readonly MalwareScanService $malwareScans,
    ) {}

    /**
     * @param  array<string, mixed>|null  $result
     */
    public function handle(
        SiteCommand $command,
        Site $site,
        string $status,
        ?array $result = null,
        ?string $error = null,
    ): void {
        $command->update([
            'status'        => $status === 'success'
                ? CommandStatus::SUCCESS
                : CommandStatus::FAILED,
            'result'        => $result,
            'error_message' => $error,
            'completed_at'  => now(),
        ]);

        match ($command->type) {
            CommandType::RUN_BACKUP        => $this->handleBackupResult($command, $site, $status, $result, $error),
            CommandType::RUN_WP_UPDATES    => $this->handleUpdateResult($command, $site, $status, $result, $error),
            CommandType::ROLLBACK_RESTORE  => $this->handleRollbackResult($command, $site, $status, $result, $error),
            CommandType::RUN_MALWARE_SCAN  => $this->handleMalwareScanResult($command, $site, $status, $result, $error),
            default                        => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function handleBackupResult(
        SiteCommand $command,
        Site $site,
        string $status,
        ?array $result,
        ?string $error,
    ): void {
        $trigger  = (string) ($command->params['trigger'] ?? 'manual');
        $features = PlanFeatures::forSite($site);
        $started  = $command->sent_at ?? $command->queued_at ?? now();

        if ($status !== 'success') {
            $backup = Backup::create([
                'tenant_id'     => $site->tenant_id,
                'site_id'       => $site->id,
                'status'        => BackupStatus::FAILED,
                'type'          => $trigger,
                'error_message' => $error ? substr($error, 0, 500) : 'Backup failed',
                'started_at'    => $started,
                'completed_at'  => now(),
            ]);

            $this->recordEvent(
                $site,
                'backup_failed',
                EventSeverity::CRITICAL,
                'Backup failed',
                $error ?? 'The backup could not be completed.',
                ['command_id' => $command->id, 'backup_id' => $backup->id],
            );

            if (! empty($command->params['chain_wp_update'])) {
                $this->recordEvent(
                    $site,
                    'update_deferred',
                    EventSeverity::WARNING,
                    'Updates deferred — pre-update backup failed',
                    'WordPress updates were not started because the safety backup failed.',
                    ['command_id' => $command->id],
                );
            }

            try {
                $this->notifications->sendBackupFailed($backup->load('site.client'));
            } catch (\Throwable $e) {
                Log::error('CommandResultService: backup failed notification error', [
                    'site_id' => $site->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return;
        }

        $b2Path    = (string) ($result['b2_path'] ?? $result['backup_file'] ?? '');
        $checksum  = $this->normalizeChecksum($result['checksum'] ?? null);
        $sizeMb    = isset($result['file_size_mb']) ? (float) $result['file_size_mb'] : null;
        $sizeBytes = $sizeMb !== null ? (int) round($sizeMb * 1024 * 1024) : null;

        $backup = Backup::create([
            'tenant_id'       => $site->tenant_id,
            'site_id'         => $site->id,
            'status'          => BackupStatus::SUCCESS,
            'type'            => $trigger,
            'b2_file_key'     => $b2Path !== '' ? $b2Path : null,
            'b2_bucket'       => $this->defaultBucketName(),
            'size_bytes'      => $sizeBytes,
            'checksum_sha256' => $checksum,
            'started_at'      => $started,
            'completed_at'    => now(),
            'expires_at'      => now()->addDays($features->retentionDays()),
        ]);

        $sizeLabel = $sizeMb !== null ? number_format($sizeMb, 1).' MB' : 'completed';

        $this->recordEvent(
            $site,
            'backup_complete',
            EventSeverity::SUCCESS,
            $trigger === 'pre_update' ? 'Pre-update backup completed' : 'Backup completed successfully',
            "Cloud backup verified ({$sizeLabel}).",
            [
                'command_id' => $command->id,
                'backup_id'  => $backup->id,
                'b2_path'    => $b2Path,
            ],
        );

        if (! empty($command->params['chain_wp_update'])) {
            $updateTrigger = (string) ($command->params['wp_update_trigger'] ?? 'scheduled');
            $this->commands->queueWpUpdates($site->fresh(['plan', 'subscription']), $updateTrigger);
        }
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function handleUpdateResult(
        SiteCommand $command,
        Site $site,
        string $status,
        ?array $result,
        ?string $error,
    ): void {
        if ($status !== 'success') {
            $this->handleUpdateFailure($site, $command, $error ?? 'Updates could not be completed.');

            return;
        }

        if (($result['status'] ?? '') === 'deferred') {
            $this->recordEvent(
                $site,
                'update_deferred',
                EventSeverity::WARNING,
                'Updates deferred — backup required first',
                (string) ($result['reason'] ?? 'No recent backup found.'),
                ['command_id' => $command->id],
            );

            if (! $this->updateSafety->hasRecentSuccessfulBackup($site)
                && ! $this->commands->hasPending($site, CommandType::RUN_BACKUP)) {
                $this->commands->queueBackup($site, 'pre_update', [
                    'chain_wp_update'   => true,
                    'wp_update_trigger' => (string) ($command->params['trigger'] ?? 'scheduled'),
                ]);
            }

            return;
        }

        if (($result['status'] ?? '') === 'failed') {
            $this->handleUpdateFailure(
                $site,
                $command,
                (string) ($result['error'] ?? 'One or more updates failed.'),
            );

            return;
        }

        $summary = $this->summarizeUpdateResult($result ?? []);

        $this->recordEvent(
            $site,
            'update_complete',
            EventSeverity::SUCCESS,
            'WordPress updates applied',
            $summary['message'],
            array_merge(['command_id' => $command->id], $summary),
        );

        try {
            $this->notifications->sendUpdateComplete($site->load('client'), $summary);
        } catch (\Throwable $e) {
            Log::error('CommandResultService: update complete notification error', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function handleUpdateFailure(Site $site, SiteCommand $command, string $error): void
    {
        $this->recordEvent(
            $site,
            'update_failed',
            EventSeverity::CRITICAL,
            'WordPress update failed',
            $error,
            ['command_id' => $command->id],
        );

        try {
            $this->notifications->sendUpdateFailed($site->load('client'), $error);
        } catch (\Throwable $e) {
            Log::error('CommandResultService: update failed notification error', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $rollback = $this->commands->queueRollbackRestore($site->fresh(['plan', 'subscription']), 'auto');

        if ($rollback) {
            $this->recordEvent(
                $site,
                'rollback_queued',
                EventSeverity::WARNING,
                'Automatic rollback queued',
                'We are restoring your site from the pre-update backup.',
                [
                    'command_id'          => $command->id,
                    'rollback_command_id' => $rollback->id,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function handleRollbackResult(
        SiteCommand $command,
        Site $site,
        string $status,
        ?array $result,
        ?string $error,
    ): void {
        $trigger = (string) ($command->params['trigger'] ?? 'manual');

        if ($status !== 'success') {
            $this->recordEvent(
                $site,
                'rollback_failed',
                EventSeverity::CRITICAL,
                'Rollback restore failed',
                $error ?? 'The site could not be restored automatically.',
                ['command_id' => $command->id],
            );

            try {
                $this->notifications->sendRollbackFailed($site->load('client'), $error ?? 'Rollback failed');
            } catch (\Throwable $e) {
                Log::error('CommandResultService: rollback failed notification error', [
                    'site_id' => $site->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return;
        }

        $this->recordEvent(
            $site,
            'rollback_complete',
            EventSeverity::SUCCESS,
            'Site restored from backup',
            (string) ($result['message'] ?? 'Your site was rolled back to the pre-update snapshot.'),
            [
                'command_id' => $command->id,
                'backup_id'  => $command->params['backup_id'] ?? null,
                'trigger'    => $trigger,
            ],
        );

        try {
            $this->notifications->sendRollbackComplete($site->load('client'));
        } catch (\Throwable $e) {
            Log::error('CommandResultService: rollback complete notification error', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $result
     */
    private function handleMalwareScanResult(
        SiteCommand $command,
        Site $site,
        string $status,
        ?array $result,
        ?string $error,
    ): void {
        $trigger = (string) ($command->params['trigger'] ?? 'scheduled');

        $this->malwareScans->recordResult($site, $status, $result, $error, $trigger);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{message: string, wp_core: bool, plugins: list<string>, errors: list<string>}
     */
    private function summarizeUpdateResult(array $result): array
    {
        $plugins     = [];
        $errors      = [];
        $coreUpdated = false;

        if (isset($result['core']['exit_code'])) {
            $coreUpdated = (int) $result['core']['exit_code'] === 0;
        }

        if (is_array($result['plugins'] ?? null)) {
            foreach ($result['plugins'] as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $plugins[] = (string) $item['name'];
                }
            }
        }

        $parts = [];
        if ($coreUpdated) {
            $parts[] = 'WordPress core';
        }
        if ($plugins !== []) {
            $parts[] = count($plugins).' plugin(s)';
        }
        if ($parts === []) {
            $parts[] = 'Your site was checked — everything was already up to date';
        }

        return [
            'message'  => 'Updated: '.implode(', ', $parts).'.',
            'wp_core'  => $coreUpdated,
            'plugins'  => $plugins,
            'errors'   => $errors,
        ];
    }

    private function normalizeChecksum(mixed $checksum): ?string
    {
        if (! is_string($checksum) || $checksum === '') {
            return null;
        }

        return str_starts_with($checksum, 'sha256:')
            ? substr($checksum, 7)
            : $checksum;
    }

    private function defaultBucketName(): ?string
    {
        $name = PlatformSetting::get('b2_bucket_name', config('services.backblaze.bucket_name', ''));

        return filled($name) ? (string) $name : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordEvent(
        Site $site,
        string $type,
        EventSeverity $severity,
        string $title,
        ?string $message,
        array $metadata = [],
    ): void {
        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => $type,
            'severity'  => $severity,
            'title'     => $title,
            'message'   => $message,
            'metadata'  => $metadata,
        ]);
    }
}
