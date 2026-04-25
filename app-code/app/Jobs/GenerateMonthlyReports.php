<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Runs on the 1st of every month at 09:00 UTC.
 * Dispatches one GenerateSiteReport job per active site.
 * Reports cover the previous calendar month.
 */
final class GenerateMonthlyReports implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        // Previous month in YYYY-MM format
        $period = Carbon::now()->subMonthNoOverflow()->format('Y-m');

        $count = 0;

        Site::where('status', '!=', SiteStatus::SUSPENDED->value)
            ->where('is_active', true)
            ->whereNotNull('client_id')
            ->chunk(100, function ($sites) use ($period, &$count): void {
                foreach ($sites as $site) {
                    GenerateSiteReport::dispatch($site->id, $period);
                    $count++;
                }
            });

        Log::info("GenerateMonthlyReports: dispatched {$count} report jobs for {$period}");
    }
}
