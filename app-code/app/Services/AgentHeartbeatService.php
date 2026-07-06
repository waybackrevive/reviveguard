<?php

namespace App\Services;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Support\Facades\Log;

/**
 * Records agent heartbeats synchronously — connection must never depend on the queue.
 */
class AgentHeartbeatService
{
    public function __construct(private readonly AlertService $alertService) {}

    public function record(Site $site, array $payload): Site
    {
        $site->loadMissing('subscription');
        $previousStatus = $site->status;
        $paid           = $site->hasPaidSubscription();

        $site->update(array_filter([
            'last_seen_at'  => now(),
            'agent_version' => $payload['agent_version'] ?? null,
            'wp_version'    => $payload['wp_version'] ?? null,
            'php_version'   => $payload['php_version'] ?? null,
            'disk_usage_mb' => isset($payload['disk_usage_mb'])
                ? (int) round((float) $payload['disk_usage_mb'])
                : null,
            'debug_mode'    => array_key_exists('debug_mode', $payload) ? (bool) $payload['debug_mode'] : null,
            'plugin_count'  => $payload['plugin_count'] ?? null,
            'theme_name'    => $payload['theme_name'] ?? null,
            'status'        => $paid ? SiteStatus::ACTIVE : SiteStatus::PENDING,
        ], fn ($v) => $v !== null));

        if ($site->last_seen_at === null) {
            $site->update([
                'last_seen_at' => now(),
                'status'       => $paid ? SiteStatus::ACTIVE : SiteStatus::PENDING,
            ]);
        }

        if (in_array($previousStatus, [SiteStatus::DOWN, SiteStatus::WARNING], true) && $paid) {
            try {
                $this->alertService->siteRecovered($site->fresh());
            } catch (\Throwable $e) {
                Log::error('AgentHeartbeatService: siteRecovered failed', [
                    'site_id' => $site->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $site->fresh();
    }
}
