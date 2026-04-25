<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\AlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMissedHeartbeats implements ShouldQueue
{
    use Queueable;

    // Sites with no heartbeat for this many minutes are considered down
    private const MISSED_THRESHOLD_MINUTES = 15;

    public function handle(AlertService $alertService): void
    {
        $cutoff = now()->subMinutes(self::MISSED_THRESHOLD_MINUTES);

        // Find active sites that haven't checked in
        $missedSites = Site::where('is_active', true)
            ->where('status', '!=', SiteStatus::DOWN->value)
            ->where('status', '!=', SiteStatus::SUSPENDED->value)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $cutoff);
            })
            ->get();

        foreach ($missedSites as $site) {
            $previousStatus = $site->status;

            $site->update(['status' => SiteStatus::DOWN]);

            // Only alert on first transition to DOWN (not if already marked down)
            if ($previousStatus !== SiteStatus::DOWN) {
                $alertService->siteDown($site);
            }
        }
    }
}
