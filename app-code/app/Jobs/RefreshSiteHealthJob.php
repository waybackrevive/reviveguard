<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteHealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Immediate health scan after a site is activated (post-checkout).
 */
class RefreshSiteHealthJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly string $siteId) {}

    public function handle(SiteHealthService $health): void
    {
        $site = Site::with('subscription')->find($this->siteId);

        if (! $site) {
            Log::warning('RefreshSiteHealthJob: site not found', ['site_id' => $this->siteId]);

            return;
        }

        $health->refresh($site);

        Log::info('RefreshSiteHealthJob: completed', ['site_id' => $site->id]);
    }
}
