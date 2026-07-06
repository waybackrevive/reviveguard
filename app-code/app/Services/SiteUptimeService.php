<?php

namespace App\Services;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Models\SiteUptimeProbe;
use App\Support\MonitorSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Built-in HTTP uptime probes — works without Uptime Kuma (KISS default).
 *
 * Uptime Kuma remains optional for advanced alerting when self-hosted on the VPS.
 */
class SiteUptimeService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function probe(Site $site): void
    {
        if (! $site->url || $site->isMonitoringPaused()) {
            return;
        }

        $interval = MonitorSettings::normalizeInterval(
            $site,
            (int) ($site->monitor_interval_minutes ?? MonitorSettings::fastestInterval($site)),
        );

        if ($site->last_uptime_probe_at && $site->last_uptime_probe_at->gt(now()->subMinutes($interval))) {
            return;
        }

        $wasUp = $this->lastProbeWasUp($site);

        $started = microtime(true);
        $isUp    = false;
        $status  = null;

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'ReviveGuard-Uptime/1.0'])
                ->get($site->url);

            $status = $response->status();
            $isUp   = $response->successful();
        } catch (\Throwable $e) {
            Log::debug('SiteUptimeService: probe failed', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $checkedAt = now();

        SiteUptimeProbe::create([
            'site_id'     => $site->id,
            'is_up'       => $isUp,
            'status_code' => $status,
            'response_ms' => (int) round((microtime(true) - $started) * 1000),
            'checked_at'  => $checkedAt,
        ]);

        $this->handleProbeTransition($site, $wasUp, $isUp, $status, $checkedAt);

        $this->recalculate($site);
        $this->pruneOld($site);

        $site->update(['last_uptime_probe_at' => $checkedAt]);
    }

    public function recalculate(Site $site): void
    {
        $since = now()->subDays(30);

        $total = SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return;
        }

        $up = SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '>=', $since)
            ->where('is_up', true)
            ->count();

        $since7 = now()->subDays(7);
        $total7 = SiteUptimeProbe::where('site_id', $site->id)->where('checked_at', '>=', $since7)->count();
        $up7    = SiteUptimeProbe::where('site_id', $site->id)->where('checked_at', '>=', $since7)->where('is_up', true)->count();

        $site->update([
            'uptime_30d' => round(($up / $total) * 100, 2),
            'uptime_7d'  => $total7 > 0 ? round(($up7 / $total7) * 100, 2) : null,
        ]);
    }

    private function lastProbeWasUp(Site $site): ?bool
    {
        $last = SiteUptimeProbe::where('site_id', $site->id)
            ->orderByDesc('checked_at')
            ->value('is_up');

        return $last === null ? null : (bool) $last;
    }

    private function handleProbeTransition(
        Site $site,
        ?bool $wasUp,
        bool $isUp,
        ?int $status,
        \Carbon\Carbon $checkedAt,
    ): void {
        $label = $site->displayName();

        if (! $isUp && $wasUp !== false) {
            $site->update(['status' => SiteStatus::DOWN]);

            Event::create([
                'tenant_id' => $site->tenant_id,
                'site_id'   => $site->id,
                'type'      => 'uptime_probe',
                'severity'  => EventSeverity::CRITICAL->value,
                'title'     => "Site offline: {$label}",
                'message'   => 'HTTP check failed'
                    . ($status ? " (status {$status})" : '')
                    . ' at ' . $checkedAt->format('M j, Y g:i A') . '.',
                'metadata'  => [
                    'source'      => 'uptime_probe',
                    'direction'   => 'down',
                    'checked_at'  => $checkedAt->toIso8601String(),
                    'status_code' => $status,
                ],
                'resolved'  => false,
            ]);

            try {
                $this->notifications->sendSiteDown($site);
            } catch (\Throwable $e) {
                Log::error('SiteUptimeService: sendSiteDown failed', ['error' => $e->getMessage()]);
            }

            return;
        }

        if ($isUp && $wasUp === false) {
            Event::where('site_id', $site->id)
                ->whereIn('type', ['uptime_probe', 'uptime_kuma_alert'])
                ->where('resolved', false)
                ->update([
                    'resolved'    => true,
                    'resolved_at' => $checkedAt,
                ]);

            if ($site->status === SiteStatus::DOWN) {
                $site->update(['status' => SiteStatus::ACTIVE]);

                Event::create([
                    'tenant_id' => $site->tenant_id,
                    'site_id'   => $site->id,
                    'type'      => 'uptime_probe',
                    'severity'  => EventSeverity::INFO->value,
                    'title'     => "Site back online: {$label}",
                    'message'   => 'HTTP check succeeded at ' . $checkedAt->format('M j, Y g:i A') . '.',
                    'metadata'  => [
                        'source'     => 'uptime_probe',
                        'direction'  => 'up',
                        'checked_at' => $checkedAt->toIso8601String(),
                    ],
                    'resolved'  => true,
                    'resolved_at' => $checkedAt,
                ]);
            }
        }
    }

    private function pruneOld(Site $site): void
    {
        SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '<', now()->subDays(35))
            ->delete();
    }
}
