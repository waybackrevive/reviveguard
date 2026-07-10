<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Models\Event;
use App\Models\Report;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates the monthly maintenance PDF report for a single site.
 *
 * Flow:
 *  1. Collect all report data from the DB
 *  2. Render Blade template to HTML
 *  3. POST HTML to Puppeteer microservice → receive PDF binary
 *  4. Upload PDF to Backblaze B2
 *  5. Create Report record
 *  6. Send ReportReadyMail with PDF attached
 */
class ReportService
{
    private string $tenantId;
    private string $puppeteerUrl;

    public function __construct(
        private readonly BackblazeService    $b2,
        private readonly NotificationService $notifications,
    ) {
        $this->tenantId      = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
        $this->puppeteerUrl  = rtrim((string) config('services.puppeteer.url', 'http://127.0.0.1:3002'), '/');
    }

    /**
     * Generate and deliver the monthly report for one site.
     *
     * @param  string  $siteId  Site UUID
     * @param  string  $period  YYYY-MM format (e.g. "2025-06")
     */
    public function generateForSite(string $siteId, string $period): void
    {
        $site = Site::with('client')->find($siteId);
        if (! $site || ! $site->client) {
            Log::warning('ReportService: site or client not found', ['site_id' => $siteId]);
            return;
        }

        $client = $site->client;

        $viewData = $this->gatherViewData($site, $period);
        if (! $viewData) {
            Log::warning('ReportService: could not gather report data', ['site_id' => $siteId]);

            return;
        }

        // ── 2. Render HTML ────────────────────────────────────────────────────
        $html = view('reports.monthly', $viewData)->render();

        // ── 3. Convert to PDF via Puppeteer microservice ──────────────────────
        $pdfContent = $this->renderPdf($html);
        if (! $pdfContent) {
            Log::error('ReportService: PDF generation failed', ['site_id' => $siteId, 'period' => $period]);
            $this->createReportRecord($site, $client, $period, 'failed', null, null, null, 'PDF generation failed');
            return;
        }

        // ── 4. Upload PDF to B2 ───────────────────────────────────────────────
        $fileKey = "reports/{$period}/{$site->id}.pdf";
        $b2Key   = $this->b2->uploadFile($fileKey, $pdfContent, 'application/pdf');

        // ── 5. Create Report record ───────────────────────────────────────────
        $report = $this->createReportRecord(
            site: $site,
            client: $client,
            period: $period,
            status: 'completed',
            b2FileKey: $b2Key,
            b2Bucket: $b2Key ? config('services.backblaze.bucket_name') : null,
            sizeBytes: strlen($pdfContent),
        );

        // ── 6. Email report (with PDF attached) ───────────────────────────────
        try {
            $tempPath = sys_get_temp_dir() . '/rg_report_' . $site->id . '_' . $period . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            $this->notifications->sendReportReady($report, $tempPath);

            @unlink($tempPath);

            $report->update(['email_sent' => true, 'email_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('ReportService: report email failed', ['error' => $e->getMessage()]);
        }

        Log::info('ReportService: report generated', ['site_id' => $siteId, 'period' => $period]);
    }

    /**
     * Render monthly report HTML without PDF generation (dry-run / QA).
     *
     * @return array{html: string, sections: list<string>}|null
     */
    public function renderPreview(string $siteId, string $period): ?array
    {
        $site = Site::with('client')->find($siteId);
        if (! $site || ! $site->client) {
            return null;
        }

        $viewData = $this->gatherViewData($site, $period);
        if (! $viewData) {
            return null;
        }

        $html = view('reports.monthly', $viewData)->render();

        return [
            'html'     => $html,
            'sections' => $this->detectSections($html),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>|null
     */
    private function gatherViewData(Site $site, string $period): ?array
    {
        $client = $site->client;
        if (! $client) {
            return null;
        }

        $periodStart = Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd   = Carbon::parse($period . '-01')->endOfMonth();
        $periodLabel = $periodStart->format('F Y');

        $events = Event::where('site_id', $site->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $backupsCompleted = \App\Models\Backup::where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $backupsFailed = \App\Models\Backup::where('site_id', $site->id)
            ->where('status', BackupStatus::FAILED)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $latestBackup = \App\Models\Backup::where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->orderBy('completed_at', 'desc')
            ->first();

        $updateEvents = Event::where('site_id', $site->id)
            ->where('type', 'update_complete')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $rollbackEvents = Event::where('site_id', $site->id)
            ->whereIn('type', ['rollback_complete', 'rollback_failed', 'rollback_queued'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $malwareScanEvents = Event::where('site_id', $site->id)
            ->whereIn('type', ['malware_scan_complete', 'malware_scan_alert', 'malware_scan_failed'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $linkAuditEvents = Event::where('site_id', $site->id)
            ->whereIn('type', ['broken_link_audit_complete', 'broken_link_audit_failed'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        $malwareAlerts = $malwareScanEvents->where('type', 'malware_scan_alert')->count();
        $brokenLinksTotal = $linkAuditEvents
            ->where('type', 'broken_link_audit_complete')
            ->sum(fn ($e) => (int) ($e->metadata['broken_count'] ?? 0));

        $quarterlySecurity = Event::where('site_id', $site->id)
            ->where('type', 'quarterly_security_audit')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderByDesc('created_at')
            ->first();

        $quarterlySeo = Event::where('site_id', $site->id)
            ->where('type', 'quarterly_seo_snapshot')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderByDesc('created_at')
            ->first();

        $updatedPlugins = Event::where('site_id', $site->id)
            ->where('type', 'plugin_updated')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->pluck('title')
            ->toArray();

        return [
            'period'            => $periodLabel,
            'generatedAt'       => now()->format('F j, Y'),
            'clientName'        => $client->name,
            'siteUrl'           => $site->url,
            'uptime30d'         => $site->uptime_30d,
            'sslValid'          => $site->ssl_valid ?? false,
            'sslDaysLeft'       => $site->sslExpiresInDays(),
            'sslExpires'        => $site->ssl_expires_at?->format('F j, Y'),
            'sslIssuer'         => $site->ssl_issuer ?? null,
            'domainExpires'     => $site->domain_expires_at?->format('F j, Y'),
            'domainDaysLeft'    => $site->domainExpiresInDays(),
            'registrar'         => $site->registrar ?? null,
            'wpVersion'         => $site->wp_version,
            'phpVersion'        => $site->php_version,
            'pluginCount'       => $site->plugin_count,
            'themeName'         => $site->theme_name,
            'diskUsageMb'       => $site->disk_usage_mb,
            'eventCount'        => $events->count(),
            'events'            => $events,
            'updateEvents'      => $updateEvents,
            'rollbackEvents'    => $rollbackEvents,
            'malwareScanEvents' => $malwareScanEvents,
            'linkAuditEvents'   => $linkAuditEvents,
            'malwareAlerts'     => $malwareAlerts,
            'brokenLinksTotal'  => $brokenLinksTotal,
            'quarterlySecurity' => $quarterlySecurity,
            'quarterlySeo'      => $quarterlySeo,
            'updatedPlugins'    => $updatedPlugins,
            'backupsCompleted'  => $backupsCompleted,
            'backupsFailed'     => $backupsFailed,
            'latestBackupDate'  => $latestBackup?->completed_at?->format('F j, Y'),
        ];
    }

    /**
     * @return list<string>
     */
    private function detectSections(string $html): array
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $headings = [
            'SSL & Domain Status',
            'Site Information',
            'Events This Month',
            'WordPress Updates Applied',
            'Rollback Activity',
            'Malware Scans',
            'Broken Link Audits',
            'Quarterly Security Audit',
            'Quarterly SEO Snapshot',
            'Plugin Updates Applied',
            'Backups',
        ];

        $found = [];
        foreach ($headings as $heading) {
            if (str_contains($html, $heading)) {
                $found[] = $heading;
            }
        }

        return $found;
    }

    private function renderPdf(string $html): ?string
    {
        try {
            $response = Http::timeout(60)
                ->post("{$this->puppeteerUrl}/render", [
                    'html' => $html,
                ]);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('ReportService: Puppeteer /render failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('ReportService: Puppeteer exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function createReportRecord(
        Site    $site,
        \App\Models\Client $client,
        string  $period,
        string  $status,
        ?string $b2FileKey,
        ?string $b2Bucket,
        ?int    $sizeBytes,
        ?string $errorMessage = null,
    ): Report {
        return Report::create([
            'tenant_id'     => $this->tenantId,
            'site_id'       => $site->id,
            'client_id'     => $client->id,
            'type'          => 'monthly',
            'period'        => $period,
            'status'        => $status,
            'b2_file_key'   => $b2FileKey,
            'b2_bucket'     => $b2Bucket,
            'size_bytes'    => $sizeBytes,
            'email_sent'    => false,
            'error_message' => $errorMessage,
        ]);
    }
}
