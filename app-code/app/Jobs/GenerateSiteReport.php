<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Generates the monthly report for a single site.
 * Dispatched by GenerateMonthlyReports for each active site.
 */
final class GenerateSiteReport implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $backoff = 300; // 5 minutes between retries

    public function __construct(
        public readonly string $siteId,
        public readonly string $period,
    ) {}

    public function handle(ReportService $reportService): void
    {
        Log::info("GenerateSiteReport: starting", ['site_id' => $this->siteId, 'period' => $this->period]);
        $reportService->generateForSite($this->siteId, $this->period);
    }
}
