<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteUptimeProbe;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Built-in HTTP uptime probes — works without Uptime Kuma (KISS default).
 *
 * Uptime Kuma remains optional for advanced alerting when self-hosted on the VPS.
 */
class SiteUptimeService
{
    public function probe(Site $site): void
    {
        if (! $site->url) {
            return;
        }

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

        SiteUptimeProbe::create([
            'site_id'     => $site->id,
            'is_up'       => $isUp,
            'status_code' => $status,
            'response_ms' => (int) round((microtime(true) - $started) * 1000),
            'checked_at'  => now(),
        ]);

        $this->recalculate($site);
        $this->pruneOld($site);
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

    private function pruneOld(Site $site): void
    {
        SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '<', now()->subDays(35))
            ->delete();
    }
}
