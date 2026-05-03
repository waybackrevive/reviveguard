<?php

namespace App\Jobs;

use App\Models\SiteEvaluation;
use App\Services\ExternalScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RunExternalSiteScan — queued job that runs all external checks on a prospect's site.
 *
 * Dispatched automatically when a new evaluation is submitted.
 * Results are stored in site_evaluations.scan_results (JSON).
 * scan_status transitions: pending → running → done | failed
 */
class RunExternalSiteScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 90; // seconds — scans can take a while

    public function __construct(private readonly string $evaluationId) {}

    public function handle(ExternalScanService $scanner): void
    {
        $evaluation = SiteEvaluation::find($this->evaluationId);

        if (! $evaluation) {
            Log::warning('RunExternalSiteScan: evaluation not found', ['id' => $this->evaluationId]);
            return;
        }

        $evaluation->update(['scan_status' => 'running']);

        try {
            $results = $scanner->scan($evaluation->site_url);

            $evaluation->update([
                'scan_status'  => 'done',
                'scan_results' => $results,
                'scan_ran_at'  => now(),
            ]);

            Log::info('RunExternalSiteScan: completed', [
                'evaluation_id' => $this->evaluationId,
                'risk_level'    => $results['risk_level'] ?? 'unknown',
            ]);

        } catch (\Throwable $e) {
            $evaluation->update([
                'scan_status'  => 'failed',
                'scan_results' => ['error' => $e->getMessage()],
                'scan_ran_at'  => now(),
            ]);

            Log::error('RunExternalSiteScan: failed', [
                'evaluation_id' => $this->evaluationId,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
