<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\AlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessHeartbeat implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $siteId,
        private readonly array $payload
    ) {}

    public function handle(AlertService $alertService): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $previousStatus = $site->status;

        $site->update(array_filter([
            'last_seen_at'  => now(),
            'agent_version' => $this->payload['agent_version'] ?? null,
            'wp_version'    => $this->payload['wp_version'] ?? null,
            'php_version'   => $this->payload['php_version'] ?? null,
            'disk_usage_mb' => $this->payload['disk_usage_mb'] ?? null,
            'debug_mode'    => $this->payload['debug_mode'] ?? null,
            'plugin_count'  => $this->payload['plugin_count'] ?? null,
            'theme_name'    => $this->payload['theme_name'] ?? null,
            'status'        => SiteStatus::ACTIVE,
        ], fn ($v) => $v !== null));

        // Always update status and last_seen_at regardless of other nulls
        $site->update([
            'last_seen_at' => now(),
            'status'       => SiteStatus::ACTIVE,
        ]);

        // Notify if site was previously down or warning and has recovered
        if (in_array($previousStatus, [SiteStatus::DOWN, SiteStatus::WARNING])) {
            $alertService->siteRecovered($site);
        }
    }
}
