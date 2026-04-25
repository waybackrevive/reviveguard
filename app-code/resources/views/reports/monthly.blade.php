<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ReviveGuard Monthly Report — {{ $period }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color: #111827; background: #fff; font-size: 14px; }
  .page { width: 794px; min-height: 1123px; padding: 60px 60px 40px; margin: 0 auto; }

  /* Header */
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 48px; padding-bottom: 24px; border-bottom: 2px solid #e5e7eb; }
  .brand { font-size: 22px; font-weight: 800; color: #1d4ed8; letter-spacing: -0.5px; }
  .report-meta { text-align: right; }
  .report-meta .period { font-size: 18px; font-weight: 700; color: #111827; }
  .report-meta .generated { font-size: 12px; color: #6b7280; margin-top: 4px; }

  /* Site info */
  .site-block { background: #f9fafb; border-radius: 10px; padding: 20px 24px; margin-bottom: 32px; }
  .site-block .label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
  .site-block .site-url { font-size: 16px; font-weight: 700; color: #1d4ed8; }
  .site-block .client-name { font-size: 14px; color: #374151; margin-top: 4px; }

  /* Stats grid */
  .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
  .stat-card { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 20px; text-align: center; }
  .stat-card.green { background: #f0fdf4; border-color: #bbf7d0; }
  .stat-card.amber { background: #fffbeb; border-color: #fde68a; }
  .stat-card.red   { background: #fef2f2; border-color: #fecaca; }
  .stat-value { font-size: 28px; font-weight: 800; color: #111827; line-height: 1; margin-bottom: 4px; }
  .stat-label { font-size: 12px; color: #6b7280; font-weight: 500; }

  /* Section */
  h2 { font-size: 15px; font-weight: 700; color: #374151; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #f3f4f6; }

  /* SSL / Domain table */
  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
  .info-table td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
  .info-table td:first-child { color: #6b7280; font-weight: 500; width: 200px; }
  .info-table td:last-child { color: #111827; font-weight: 600; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; }
  .badge.green { background: #dcfce7; color: #166534; }
  .badge.red   { background: #fee2e2; color: #991b1b; }
  .badge.amber { background: #fef9c3; color: #713f12; }

  /* Events table */
  .events-table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
  .events-table th { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
  .events-table td { padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #f9fafb; }
  .severity-critical { color: #dc2626; font-weight: 600; }
  .severity-warning  { color: #d97706; font-weight: 600; }
  .severity-success  { color: #16a34a; font-weight: 600; }
  .severity-info     { color: #2563eb; }

  /* Updates */
  .updates-list { list-style: none; margin-bottom: 32px; }
  .updates-list li { padding: 8px 12px; font-size: 13px; border-bottom: 1px solid #f3f4f6; color: #374151; }
  .updates-list li:before { content: "•"; color: #1d4ed8; margin-right: 8px; font-weight: 700; }

  /* Footer */
  .footer { margin-top: 48px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
  .footer .brand-sm { font-size: 13px; font-weight: 700; color: #1d4ed8; }
  .footer .copy { font-size: 11px; color: #9ca3af; }

  /* No data */
  .no-data { font-size: 13px; color: #9ca3af; font-style: italic; margin-bottom: 32px; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="brand">ReviveGuard</div>
    <div class="report-meta">
      <div class="period">Monthly Report — {{ $period }}</div>
      <div class="generated">Generated {{ $generatedAt }}</div>
    </div>
  </div>

  {{-- Site Info --}}
  <div class="site-block">
    <div class="label">Website</div>
    <div class="site-url">{{ $siteUrl }}</div>
    <div class="client-name">{{ $clientName }}</div>
  </div>

  {{-- Stats --}}
  <div class="stats-grid">
    <div class="stat-card {{ ($uptime30d ?? 0) >= 99 ? 'green' : (($uptime30d ?? 0) >= 95 ? 'amber' : 'red') }}">
      <div class="stat-value">{{ $uptime30d !== null ? number_format($uptime30d, 1) . '%' : 'N/A' }}</div>
      <div class="stat-label">Uptime (30 days)</div>
    </div>
    <div class="stat-card {{ $sslValid ? 'green' : 'red' }}">
      <div class="stat-value">{{ $sslDaysLeft !== null ? $sslDaysLeft . 'd' : 'N/A' }}</div>
      <div class="stat-label">SSL Expiry</div>
    </div>
    <div class="stat-card {{ $eventCount > 0 ? 'amber' : 'green' }}">
      <div class="stat-value">{{ $eventCount }}</div>
      <div class="stat-label">Events This Month</div>
    </div>
  </div>

  {{-- SSL & Domain --}}
  <h2>SSL &amp; Domain Status</h2>
  <table class="info-table">
    <tr>
      <td>SSL Certificate</td>
      <td>
        @if ($sslValid)
          <span class="badge green">Valid</span>
        @else
          <span class="badge red">Invalid / Expired</span>
        @endif
        @if ($sslExpires)
          — expires {{ $sslExpires }}
        @endif
      </td>
    </tr>
    <tr>
      <td>SSL Issuer</td>
      <td>{{ $sslIssuer ?? 'Unknown' }}</td>
    </tr>
    <tr>
      <td>Domain Expiry</td>
      <td>
        @if ($domainExpires)
          {{ $domainExpires }}
          @if ($domainDaysLeft !== null && $domainDaysLeft <= 30)
            <span class="badge amber">{{ $domainDaysLeft }} days left</span>
          @endif
        @else
          Unknown
        @endif
      </td>
    </tr>
    <tr>
      <td>Registrar</td>
      <td>{{ $registrar ?? 'Unknown' }}</td>
    </tr>
  </table>

  {{-- Site Details --}}
  <h2>Site Information</h2>
  <table class="info-table" style="margin-bottom:32px;">
    <tr><td>WordPress Version</td><td>{{ $wpVersion ?? 'Unknown' }}</td></tr>
    <tr><td>PHP Version</td><td>{{ $phpVersion ?? 'Unknown' }}</td></tr>
    <tr><td>Active Plugins</td><td>{{ $pluginCount ?? 'Unknown' }}</td></tr>
    <tr><td>Theme</td><td>{{ $themeName ?? 'Unknown' }}</td></tr>
    @if ($diskUsageMb)
    <tr><td>Disk Usage</td><td>{{ number_format($diskUsageMb, 1) }} MB</td></tr>
    @endif
  </table>

  {{-- Recent Events --}}
  <h2>Events This Month</h2>
  @if (count($events) > 0)
  <table class="events-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Severity</th>
        <th>Event</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($events as $event)
      <tr>
        <td>{{ \Carbon\Carbon::parse($event->created_at)->format('M j') }}</td>
        <td class="severity-{{ strtolower($event->severity instanceof \App\Enums\EventSeverity ? $event->severity->value : $event->severity) }}">
          {{ $event->severity instanceof \App\Enums\EventSeverity ? $event->severity->value : $event->severity }}
        </td>
        <td>{{ $event->title }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @else
  <p class="no-data">No events recorded this month — your site ran smoothly.</p>
  @endif

  {{-- Plugin Updates --}}
  @if (count($updatedPlugins) > 0)
  <h2>Plugin Updates Applied</h2>
  <ul class="updates-list">
    @foreach ($updatedPlugins as $plugin)
      <li>{{ $plugin }}</li>
    @endforeach
  </ul>
  @endif

  {{-- Backups --}}
  <h2>Backups</h2>
  <table class="info-table">
    <tr><td>Backups Completed</td><td>{{ $backupsCompleted }}</td></tr>
    <tr><td>Backups Failed</td><td>{{ $backupsFailed }}</td></tr>
    @if ($latestBackupDate)
    <tr><td>Last Successful Backup</td><td>{{ $latestBackupDate }}</td></tr>
    @endif
  </table>

  {{-- Footer --}}
  <div class="footer">
    <div class="brand-sm">ReviveGuard</div>
    <div class="copy">Confidential — prepared for {{ $clientName }}</div>
  </div>

</div>
</body>
</html>
