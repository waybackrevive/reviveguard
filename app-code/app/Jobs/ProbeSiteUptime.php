<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteUptimeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * HTTP uptime probe every 5 minutes for protected sites (no Uptime Kuma required).
 */
final class ProbeSiteUptime implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(SiteUptimeService $uptime): void
    {
        Site::protected()
            ->monitoringActive()
            ->whereNotNull('url')
            ->chunk(30, function ($sites) use ($uptime): void {
                foreach ($sites as $site) {
                    try {
                        $uptime->probe($site);
                    } catch (\Throwable $e) {
                        Log::warning('ProbeSiteUptime failed', [
                            'site_id' => $site->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
