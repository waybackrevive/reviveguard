<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\UptimeKumaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs every 6 hours — pulls uptime percentage from Uptime Kuma for all active sites
 * and updates uptime_30d and uptime_7d columns.
 */
final class UpdateUptimeStats implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(UptimeKumaService $kumaService): void
    {
        Site::whereNotNull('uptime_kuma_monitor_id')
            ->where('status', '!=', SiteStatus::SUSPENDED->value)
            ->chunk(50, function ($sites) use ($kumaService): void {
                foreach ($sites as $site) {
                    try {
                        $uptime30 = $kumaService->getUptimePercent((int) $site->uptime_kuma_monitor_id, 30);
                        $uptime7  = $kumaService->getUptimePercent((int) $site->uptime_kuma_monitor_id, 7);

                        $site->update([
                            'uptime_30d' => $uptime30,
                            'uptime_7d'  => $uptime7,
                        ]);
                    } catch (Throwable $e) {
                        Log::warning("UpdateUptimeStats: failed for site {$site->id}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
