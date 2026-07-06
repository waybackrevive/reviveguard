<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\SiteUptimeProbe;
use App\Services\AlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMissedHeartbeats implements ShouldQueue
{
    use Queueable;

    /** WP Cron can drift — allow 3× the scheduled heartbeat interval. */
    private const MISSED_THRESHOLD_MINUTES = 30;

    public function handle(AlertService $alertService): void
    {
        $cutoff = now()->subMinutes(self::MISSED_THRESHOLD_MINUTES);

        $missedSites = Site::protected()
            ->monitoringActive()
            ->where('is_active', true)
            ->where('status', '!=', SiteStatus::DOWN->value)
            ->where('status', '!=', SiteStatus::SUSPENDED->value)
            ->where('status', '!=', SiteStatus::PENDING->value)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $cutoff)
            ->get();

        foreach ($missedSites as $site) {
            if ($this->httpProbeShowsUp($site)) {
                continue;
            }

            $previousStatus = $site->status;

            $site->update(['status' => SiteStatus::DOWN]);

            if ($previousStatus !== SiteStatus::DOWN) {
                $alertService->siteDown($site);
            }
        }
    }

    private function httpProbeShowsUp(Site $site): bool
    {
        return SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '>=', now()->subMinutes(15))
            ->where('is_up', true)
            ->exists();
    }
}
