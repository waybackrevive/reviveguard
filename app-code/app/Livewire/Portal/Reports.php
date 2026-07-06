<?php

namespace App\Livewire\Portal;

use App\Models\Report;
use App\Services\BackblazeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Reports extends Component
{
    /**
     * Generate a short-lived signed URL for a report PDF (1 hour expiry).
     * Called via wire:click on the Download button.
     * Redirects the browser to the signed URL in a new tab.
     */
    public function downloadReport(string $reportId): mixed
    {
        $client = Auth::guard('client')->user();

        $report = Report::where('id', $reportId)
            ->where('client_id', $client->id)
            ->where('status', 'completed')
            ->firstOrFail();

        /** @var BackblazeService $b2 */
        $b2  = app(BackblazeService::class);
        $url = $b2->getSignedUrl($report->b2_bucket, $report->b2_file_key, 3600);

        return $this->redirect($url);
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        $reports = Report::where('client_id', $client->id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.portal.reports', [
            'reports' => $reports,
        ])->layout('portal.layouts.app');
    }
}
