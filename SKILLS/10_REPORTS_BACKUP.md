# SKILL: Reports, Backups & PDF Generation

> Load this skill before building any report, backup, or PDF generation logic.
> References: `05_MVP_FEATURE_SPEC.md` Features 6 & 7, `06_AGENT_PLUGIN_SPEC.md`

---

## What This Covers
Monthly report generation (Puppeteer PDF), B2 backup lifecycle per plan, `GenerateMonthlyReport` job, `TriggerScheduledBackups` job, and the admin manual report/backup actions.

---

## PDF Generation — Architecture

**Approach:** Laravel generates an HTML string using a Blade template, then POSTs it to the Puppeteer microservice (port 3002), which returns a PDF binary. Laravel then stores the PDF on Backblaze B2.

```
Laravel Job
  → render Blade to HTML string
  → POST http://127.0.0.1:3002/render { html: "..." }
  → Puppeteer: HTML → PDF binary (returns in response body)
  → Laravel: stores PDF to B2
  → Creates Report record in DB
  → Dispatches SendAlert (email with PDF attached)
```

**Why this approach:** Puppeteer stays in its own process, can be restarted independently, and never has access to the Laravel app or database — only receives an HTML string.

---

## Puppeteer Microservice (`/opt/reviveguard/pdf-service/index.js`)

Already defined in `SKILLS/01_PHASE0_INFRA.md`. Key points:
- Binds to `127.0.0.1:3002` ONLY (no external access)
- Returns raw PDF binary as response body
- Content-Type: `application/pdf`
- Validates request has `html` field before processing

---

## `PdfService` (Laravel side)

```php
final class PdfService
{
    private string $pdfServiceUrl;
    
    public function __construct()
    {
        $this->pdfServiceUrl = config('services.pdf.url', 'http://127.0.0.1:3002');
    }
    
    public function generateFromHtml(string $html): string
    {
        $response = Http::timeout(60) // PDF generation can take a few seconds
            ->withBody($html, 'application/json')
            ->post("{$this->pdfServiceUrl}/render", ['html' => $html]);
        
        if (!$response->successful()) {
            throw new RuntimeException('PDF generation failed: ' . $response->status());
        }
        
        // Return raw PDF binary string
        return $response->body();
    }
    
    public function generateReport(Report $report, Site $site, Client $client): string
    {
        // Calculate data for the report
        $period       = $report->period; // e.g., "2025-06"
        [$year, $month] = explode('-', $period);
        $periodStart  = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd    = Carbon::create($year, $month, 1)->endOfMonth();
        
        $events = Event::where('site_id', $site->id)
            ->whereBetween('occurred_at', [$periodStart, $periodEnd])
            ->orderBy('occurred_at')
            ->get();
        
        $backups = Backup::where('site_id', $site->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderByDesc('created_at')
            ->get();
        
        $pluginSnapshots = PluginSnapshot::where('site_id', $site->id)
            ->whereBetween('captured_at', [$periodStart, $periodEnd])
            ->orderBy('captured_at')
            ->get();
        
        $html = view('reports.monthly', [
            'site'           => $site,
            'client'         => $client,
            'plan'           => $client->activePlan(),
            'period'         => Carbon::create($year, $month, 1)->format('F Y'),
            'events'         => $events,
            'backups'        => $backups,
            'pluginSnapshots' => $pluginSnapshots,
            'uptime30d'      => $site->uptime_30d,
            'sslExpiresAt'   => $site->ssl_expires_at,
            'domainExpiresAt' => $site->domain_expires_at,
            'generatedAt'    => now()->format('M j, Y'),
        ])->render();
        
        return $this->generateFromHtml($html);
    }
}
```

---

## Monthly Report Blade Template (`resources/views/reports/monthly.blade.php`)

```blade
<!DOCTYPE html>
<html>
<head>
<style>
  body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1a1a2e; margin: 0; padding: 0; }
  .page { padding: 48px; }
  
  /* Header */
  .report-header { display: flex; justify-content: space-between; align-items: center; 
                    border-bottom: 3px solid #1a1a2e; padding-bottom: 24px; margin-bottom: 32px; }
  .report-title { font-size: 24px; font-weight: 700; }
  .report-meta { font-size: 13px; color: #6b7280; text-align: right; }
  
  /* Summary cards */
  .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
  .metric-card { background: #f9fafb; border-radius: 8px; padding: 16px; }
  .metric-value { font-size: 28px; font-weight: 700; color: #1a1a2e; }
  .metric-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
  
  /* Tables */
  table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
  th { background: #1a1a2e; color: white; padding: 10px 12px; text-align: left; font-size: 13px; }
  td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
  
  /* Status badges */
  .badge-success { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 9999px; }
  .badge-warning { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 9999px; }
  .badge-critical { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 9999px; }
  
  /* Footer */
  .report-footer { margin-top: 48px; border-top: 1px solid #e5e7eb; padding-top: 24px; 
                   text-align: center; font-size: 11px; color: #9ca3af; }
  
  /* Page break */
  .page-break { page-break-after: always; }
</style>
</head>
<body>
<div class="page">
  <!-- Header -->
  <div class="report-header">
    <div>
      <div style="font-size: 20px; font-weight: 800; color: #1a1a2e;">ReviveGuard</div>
      <div class="report-title">Monthly Website Report</div>
      <div style="color: #6b7280; margin-top: 4px;">{{ $period }}</div>
    </div>
    <div class="report-meta">
      <div style="font-size: 16px; font-weight: 600;">{{ $site->domain }}</div>
      <div>{{ $client->name }}</div>
      <div>Generated {{ $generatedAt }}</div>
    </div>
  </div>
  
  <!-- Summary Metrics -->
  <div class="summary-grid">
    <div class="metric-card">
      <div class="metric-value">{{ $uptime30d ? number_format($uptime30d, 2) . '%' : 'N/A' }}</div>
      <div class="metric-label">Uptime This Month</div>
    </div>
    <div class="metric-card">
      <div class="metric-value">{{ $backups->where('status', 'success')->count() }}</div>
      <div class="metric-label">Backups Completed</div>
    </div>
    <div class="metric-card">
      <div class="metric-value">
        {{ $pluginSnapshots->last()?->plugins_count ?? '—' }}
      </div>
      <div class="metric-label">Plugins Monitored</div>
    </div>
    <div class="metric-card">
      <div class="metric-value">{{ $sslExpiresAt ? \Carbon\Carbon::parse($sslExpiresAt)->diffInDays(now()) . 'd' : '—' }}</div>
      <div class="metric-label">SSL Days Remaining</div>
    </div>
  </div>
  
  <!-- Uptime & Status -->
  <h2 style="font-size: 16px; margin-bottom: 12px;">Uptime & Availability</h2>
  @php
    $downEvents = $events->where('type', 'site_down');
    $totalDowntime = $downEvents->sum(fn($e) => $e->metadata['downtime_minutes'] ?? 0);
  @endphp
  <p style="font-size: 13px; color: #374151; margin-bottom: 16px;">
    Your website was online for {{ $uptime30d ? number_format($uptime30d, 2) : '—' }}% of this month.
    @if($downEvents->isNotEmpty())
      There {{ $downEvents->count() === 1 ? 'was' : 'were' }} {{ $downEvents->count() }} 
      downtime {{ Str::plural('incident', $downEvents->count()) }} totalling 
      approximately {{ $totalDowntime }} minutes.
    @else
      No downtime was recorded this month. 🎉
    @endif
  </p>
  
  <!-- Recent Activity -->
  @if($events->isNotEmpty())
  <h2 style="font-size: 16px; margin-bottom: 12px;">Activity Log</h2>
  <table>
    <thead>
      <tr>
        <th>Date</th><th>Event</th><th>Severity</th>
      </tr>
    </thead>
    <tbody>
      @foreach($events->take(20) as $event)
      <tr>
        <td>{{ $event->occurred_at->format('M j, g:i a') }}</td>
        <td>{{ $event->title }}</td>
        <td>
          <span class="badge-{{ $event->severity->value }}">
            {{ ucfirst($event->severity->value) }}
          </span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
  
  <!-- Backups -->
  <h2 style="font-size: 16px; margin-bottom: 12px;">Backup Summary</h2>
  @if($backups->isNotEmpty())
  <table>
    <thead><tr><th>Date</th><th>Type</th><th>Size</th><th>Status</th></tr></thead>
    <tbody>
      @foreach($backups as $backup)
      <tr>
        <td>{{ $backup->created_at->format('M j') }}</td>
        <td>{{ ucfirst($backup->type) }}</td>
        <td>{{ $backup->file_size_mb ? number_format($backup->file_size_mb, 1) . ' MB' : '—' }}</td>
        <td><span class="badge-{{ $backup->status === 'success' ? 'success' : 'critical' }}">
            {{ ucfirst($backup->status) }}</span></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @else
  <p style="font-size: 13px; color: #6b7280;">No backups recorded this month.</p>
  @endif
  
  <!-- SSL & Domain -->
  <h2 style="font-size: 16px; margin-bottom: 12px;">SSL & Domain Status</h2>
  <table>
    <tbody>
      <tr>
        <td><strong>SSL Certificate Expiry</strong></td>
        <td>{{ $sslExpiresAt ? \Carbon\Carbon::parse($sslExpiresAt)->format('F j, Y') : 'Unknown' }}</td>
      </tr>
      <tr>
        <td><strong>Domain Expiry</strong></td>
        <td>{{ $domainExpiresAt ? \Carbon\Carbon::parse($domainExpiresAt)->format('F j, Y') : 'Unknown' }}</td>
      </tr>
    </tbody>
  </table>
  
  <!-- Footer -->
  <div class="report-footer">
    <p>ReviveGuard by WaybackRevive LLC — waybackrevive.com</p>
    <p>This report was automatically generated. Contact support@reviveguard.com for questions.</p>
  </div>
</div>
</body>
</html>
```

---

## `GenerateMonthlyReport` Job

```php
final class GenerateMonthlyReport implements ShouldQueue
{
    public int $tries    = 3;
    public array $backoff = [300, 900, 1800];
    public string $queue  = 'default';
    
    public function __construct(
        private readonly Site $site,
        private readonly string $period, // "2025-06"
    ) {}
    
    public function handle(PdfService $pdfService, BackblazeService $b2): void
    {
        $site   = $this->site->fresh(['client.plan']);
        $client = $site->client;
        
        // Generate report record
        $report = Report::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'client_id' => $client->id,
            'period'    => $this->period,
            'status'    => 'generating',
        ]);
        
        try {
            $pdfBinary = $pdfService->generateReport($report, $site, $client);
            
            $b2Path = "reports/{$site->tenant_id}/{$site->id}/{$this->period}.pdf";
            $b2->upload($b2Path, $pdfBinary, 'application/pdf');
            
            $report->update([
                'status'  => 'ready',
                'b2_path' => $b2Path,
            ]);
            
            // Send email with PDF attached
            SendAlert::dispatch($client, 'monthly_report_ready', [
                'report_id' => $report->id,
            ])->onQueue('default');
            
        } catch (Throwable $e) {
            $report->update(['status' => 'failed']);
            Log::error("Report generation failed for site {$site->id}: " . $e->getMessage());
            throw $e; // Allow job to retry
        }
    }
}
```

### Scheduler for monthly reports (first of month, 9am UTC):
```php
// In Kernel or console.php
Schedule::call(function () {
    $period = now()->subMonth()->format('Y-m');
    
    Site::where('status', SiteStatus::ACTIVE)
        ->whereHas('client', fn($q) => $q->where('status', 'active'))
        ->chunk(20, function ($sites) use ($period) {
            foreach ($sites as $site) {
                GenerateMonthlyReport::dispatch($site, $period)
                    ->delay(now()->addSeconds(rand(0, 300))); // Stagger to avoid overloading Puppeteer
            }
        });
})->monthlyOn(1, '09:00');
```

---

## `TriggerScheduledBackups` Job (Hourly)

```php
final class TriggerScheduledBackups implements ShouldQueue
{
    public function handle(): void
    {
        // Get backup frequency per plan from plans.features JSONB:
        // Monitor: weekly, Guard: daily, Shield: daily
        
        Site::where('status', SiteStatus::ACTIVE)
            ->with('client.plan')
            ->chunk(50, function ($sites) {
                foreach ($sites as $site) {
                    if ($this->isDueForBackup($site)) {
                        SiteCommand::create([
                            'tenant_id' => $site->tenant_id,
                            'site_id'   => $site->id,
                            'type'      => CommandType::BACKUP,
                            'status'    => CommandStatus::PENDING,
                            'payload'   => [],
                            'created_by' => 'system',
                        ]);
                    }
                }
            });
    }
    
    private function isDueForBackup(Site $site): bool
    {
        $features        = $site->client->plan?->features ?? [];
        $frequencyDays   = match($features['backup_frequency'] ?? 'weekly') {
            'daily'   => 1,
            'weekly'  => 7,
            default   => 7,
        };
        
        $lastBackup = Backup::where('site_id', $site->id)
            ->where('status', BackupStatus::SUCCESS)
            ->latest('created_at')
            ->first();
        
        if (!$lastBackup) return true; // No backup ever — trigger one now
        
        return $lastBackup->created_at->diffInDays(now()) >= $frequencyDays;
    }
}
```

---

## B2 Backup Lifecycle Rules Per Plan

Set these when a site is created (in `SiteResource` Filament save action or `OnboardClient` job):

```php
// BackblazeService::configureBucketLifecycle()
public function configurePlanLifecycle(Site $site, Plan $plan): void
{
    $features     = $plan->features;
    $retentionDays = $features['backup_retention_days'] ?? 30; // Monitor: 30, Guard: 90, Shield: 180
    
    $prefix = "backups/{$site->tenant_id}/{$site->id}/";
    
    // Backblaze B2 lifecycle rule via API
    Http::withHeaders([
        'Authorization' => $this->authToken,
    ])->post("{$this->apiUrl}/b2api/v2/b2_update_bucket", [
        'accountId' => config('services.backblaze.account_id'),
        'bucketId'  => config('services.backblaze.bucket_id'),
        'lifecycleRules' => [
            [
                'fileNamePrefix'                 => $prefix,
                'daysFromHidingToDeleting'        => 1,
                'daysFromUploadingToHiding'       => $retentionDays,
            ],
        ],
    ]);
}
```

---

## Admin Manual Actions (Filament)

### Manual report generate:
```php
Action::make('generate_report')
    ->label('Generate Monthly Report')
    ->action(function (Site $record) {
        $period = now()->subMonth()->format('Y-m');
        GenerateMonthlyReport::dispatch($record, $period);
        Notification::make()->title('Report generation queued.')->success()->send();
    });
```

### Admin resend report email:
```php
Action::make('resend_report_email')
    ->label('Resend Report Email')
    ->form([
        Select::make('report_id')
            ->options(fn (Site $record) => Report::where('site_id', $record->id)
                ->where('status', 'ready')
                ->pluck('period', 'id'))
            ->required(),
    ])
    ->action(function (array $data, Site $record) {
        $report = Report::find($data['report_id']);
        SendAlert::dispatch($record->client, 'monthly_report_ready', [
            'report_id' => $report->id,
        ]);
        Notification::make()->title('Email queued.')->success()->send();
    });
```

---

## Backup Verification

The WP plugin verifies a backup was stored successfully by downloading the first 1024 bytes of the uploaded file. See `SKILLS/05_WP_PLUGIN.md` — `ReviveGuard_BackupHandler::verify()`. The verification result is sent back in the command result payload:

```php
// Agent sends after upload:
POST /api/agent/command-result
{
  "command_id": "...",
  "status": "success",
  "result": {
    "file_size_bytes": 104857600,
    "verified": true,
    "b2_path": "backups/.../2025-06-01_backup.tar.gz"
  }
}
```

---

## `BackblazeService` — Core Methods

```php
final class BackblazeService
{
    private string $authToken = '';
    private string $apiUrl    = '';
    private string $downloadUrl = '';
    
    public function authorize(): void
    {
        $response = Http::withBasicAuth(
            config('services.backblaze.key_id'),
            config('services.backblaze.app_key')
        )->get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account');
        
        if (!$response->successful()) {
            throw new RuntimeException('B2 authorization failed');
        }
        
        $data              = $response->json();
        $this->authToken   = $data['authorizationToken'];
        $this->apiUrl      = $data['apiUrl'];
        $this->downloadUrl = $data['downloadUrl'];
    }
    
    public function upload(string $path, string $content, string $mimeType): array
    {
        $this->authorize();
        
        // Get upload URL
        $uploadUrl = Http::withToken($this->authToken)
            ->post("{$this->apiUrl}/b2api/v2/b2_get_upload_url", [
                'bucketId' => config('services.backblaze.bucket_id'),
            ])->json();
        
        // Upload file
        return Http::withHeaders([
            'Authorization'     => $uploadUrl['authorizationToken'],
            'X-Bz-File-Name'    => rawurlencode($path),
            'Content-Type'      => $mimeType,
            'Content-Length'    => strlen($content),
            'X-Bz-Content-Sha1' => sha1($content),
        ])
        ->withBody($content, $mimeType)
        ->post($uploadUrl['uploadUrl'])
        ->json();
    }
    
    public function getSignedUrl(string $path, int $expiresInSeconds = 3600): string
    {
        $this->authorize();
        
        $response = Http::withToken($this->authToken)
            ->post("{$this->apiUrl}/b2api/v2/b2_get_download_authorization", [
                'bucketId'               => config('services.backblaze.bucket_id'),
                'fileNamePrefix'          => $path,
                'validDurationInSeconds' => $expiresInSeconds,
            ]);
        
        $token = $response->json('authorizationToken');
        return "{$this->downloadUrl}/file/" . config('services.backblaze.bucket_name') . 
               "/{$path}?Authorization={$token}";
    }
}
```

---

## Definition of Done

```
[ ] GenerateMonthlyReport job runs on 1st of month at 09:00 UTC for all active sites
[ ] PDF rendered correctly by Puppeteer — branded header, all sections present
[ ] PDF stored in B2 at path: reports/{tenant_id}/{site_id}/{period}.pdf
[ ] Report email sent with PDF attachment
[ ] Client can download report from portal — generates fresh signed URL (1hr TTL)
[ ] TriggerScheduledBackups runs hourly — queues backups for due sites only
[ ] Backup frequency matches plan: Monitor=weekly, Guard=daily, Shield=daily
[ ] B2 lifecycle rule set per plan on site create: Monitor=30d, Guard=90d, Shield=180d
[ ] Admin can manually trigger report generation from Filament
[ ] Admin can resend report email from Filament
[ ] Puppeteer service binds only to 127.0.0.1:3002 (not externally accessible)
[ ] Large PDF (50+ events): generation completes within 30 seconds
[ ] PDF generation failure: job retries 3 times, then logs error (no crash)
```
