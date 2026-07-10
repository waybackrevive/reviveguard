<?php

use App\Jobs\CheckDomainExpiry;
use App\Jobs\CheckMissedHeartbeats;
use App\Jobs\CheckSslExpiry;
use App\Jobs\GenerateMonthlyReports;
use App\Jobs\UpdateUptimeStats;
use App\Services\EvaluationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Phase 1C — Heartbeat monitoring (every 5 min)
Schedule::job(new CheckMissedHeartbeats)->everyFiveMinutes();

// HTTP uptime probes (built-in — supports Shield 2-min checks)
Schedule::job(new \App\Jobs\ProbeSiteUptime)->everyTwoMinutes();

// Phase 1E — Monitoring jobs
Schedule::job(new CheckSslExpiry)->dailyAt('06:00');
Schedule::job(new CheckDomainExpiry)->dailyAt('07:00');
Schedule::job(new UpdateUptimeStats)->everySixHours();

// Phase 1G — Monthly report generation (1st of each month at 09:00 UTC)
Schedule::job(new GenerateMonthlyReports)->monthlyOn(1, '09:00');

// Sprint P1 — Automated backups + weekly WP updates (Guard/Shield)
Schedule::job(new \App\Jobs\ScheduleSiteMaintenance)->dailyAt('03:00')
    ->name('maintenance:schedule')
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\PruneExpiredBackups)->weeklyOn(0, '04:00')
    ->name('maintenance:prune-backups')
    ->withoutOverlapping();

// Sprint P4 — Shield content hours reset (1st of month)
Schedule::job(new \App\Jobs\ResetShieldContentHours)->monthlyOn(1, '05:00')
    ->name('shield:reset-content-hours')
    ->withoutOverlapping();

// Phase 2 — Evaluation lifecycle
// Expire proposals where proposal_expires_at has passed (check every 15 min)
Schedule::call(fn (EvaluationService $svc) => $svc->expireStaleProposals())
    ->everyFifteenMinutes()
    ->name('evaluations:expire-proposals')
    ->withoutOverlapping();

// 7-day follow-up for pending evaluations (once daily at 10:00 UTC)
Schedule::call(fn (EvaluationService $svc) => $svc->sendFollowUps())
    ->dailyAt('10:00')
    ->name('evaluations:send-followups')
    ->withoutOverlapping();

Artisan::command('stripe:validate-prices', function () {
    $mode  = \App\Support\StripeConfig::modeLabel();
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('price_monthly')->get();

    $this->info("Stripe {$mode} mode — validating plan price IDs…");
    $ok = true;

    foreach ($plans as $plan) {
        $id     = $plan->resolvedStripePriceId();
        $reason = $plan->checkoutUnavailableReason();

        if ($reason) {
            $ok = false;
            $this->error("[{$plan->slug}] {$reason}");
        } else {
            $this->line("[{$plan->slug}] OK — {$id}");
        }
    }

    if (! $ok) {
        $this->newLine();
        $this->warn('Fix .env price IDs (must start with price_, not prod_), then:');
        $this->line('  php artisan db:seed --class=PlanSeeder');
        $this->line('  php artisan config:clear && php artisan cache:clear');

        return 1;
    }

    $this->info('All plan price IDs look valid.');

    return 0;
})->purpose('Validate Stripe price IDs for checkout (run on server after .env changes)');

Artisan::command('monitoring:status', function () {
    $kuma = app(\App\Services\UptimeKumaService::class);

    $this->info('── Monitoring stack ──');
    $this->line('Built-in HTTP probes: active (every 2 min via scheduler; per-site interval respected)');
    $this->line('SSL checks: native TLS (daily + on-demand)');
    $this->line('Domain expiry: WHOIS socket → RDAP → who-dat → WhoisJSON → WhoisXML');

    if ($kuma->isConfigured()) {
        $this->line('Uptime Kuma: configured at ' . config('services.uptime_kuma.url'));
        $this->warn('Note: Uptime Kuma monitor auto-create may require manual setup in Kuma UI. Built-in probes are the default.');
    } else {
        $this->line('Uptime Kuma: not configured (optional — see deploy/uptime-kuma/docker-compose.yml)');
    }

    $sites = \App\Models\Site::protected()->with('subscription')->get();
    $this->newLine();
    $this->info("Protected sites: {$sites->count()}");

    foreach ($sites as $site) {
        $this->line(sprintf(
            '  %s — uptime: %s | SSL: %s | domain: %s',
            $site->displayName(),
            $site->uptime_30d !== null ? $site->uptime_30d . '%' : 'pending',
            $site->ssl_expires_at?->format('Y-m-d') ?? 'pending',
            $site->domain_expires_at?->format('Y-m-d') ?? 'pending',
        ));
    }

    $counts = app(\App\Services\MaintenanceScheduler::class)->dueCounts();
    $this->newLine();
    $this->info('Maintenance due (next pass): backups '.$counts['backups']
        .', updates '.$counts['updates']
        .', malware '.$counts['malware_scans']
        .', links '.$counts['broken_links']
        .', quarterly '.$counts['quarterly']);
    $this->line('Run maintenance:dry-run --sites for per-site breakdown.');

    return 0;
})->purpose('Show monitoring configuration and per-site health data');

Artisan::command('maintenance:dry-run {--sites : List per-site due tasks}', function () {
    $scheduler = app(\App\Services\MaintenanceScheduler::class);
    $counts    = $scheduler->dueCounts();
    $total     = array_sum($counts);

    $this->info('── Maintenance dry-run (read-only — nothing queued) ──');
    $this->newLine();
    $this->info('Registered schedulers:');
    $this->line('  maintenance:schedule          daily 03:00 UTC — backups, WP updates, malware, links');
    $this->line('  maintenance:prune-backups     weekly Sun 04:00 UTC');
    $this->line('  shield:reset-content-hours    monthly 1st 05:00 UTC');
    $this->line('  GenerateMonthlyReports        monthly 1st 09:00 UTC');
    $this->line('  CheckMissedHeartbeats         every 5 min');
    $this->line('  ProbeSiteUptime               every 2 min');
    $this->line('  CheckSslExpiry                daily 06:00 UTC');
    $this->line('  CheckDomainExpiry             daily 07:00 UTC');
    $this->line('  UpdateUptimeStats             every 6 hours');
    $this->line('  evaluations:expire-proposals  every 15 min');
    $this->line('  evaluations:send-followups    daily 10:00 UTC');

    $this->newLine();
    $this->info('Tasks due on next maintenance pass:');
    $this->line("  Backups:        {$counts['backups']}");
    $this->line("  WP updates:     {$counts['updates']}");
    $this->line("  Malware scans:  {$counts['malware_scans']}");
    $this->line("  Link audits:    {$counts['broken_links']}");
    $this->line("  Quarterly:      {$counts['quarterly']}");
    $this->line("  Total:          {$total}");

    if ($this->option('sites')) {
        $this->newLine();
        $this->info('Per-site due tasks:');

        $quarterly = app(\App\Services\QuarterlyAuditService::class);

        \App\Models\Site::protected()
            ->where('is_active', true)
            ->with('plan')
            ->orderBy('name')
            ->chunkById(50, function ($sites) use ($scheduler, $quarterly): void {
                foreach ($sites as $site) {
                    $due = [];
                    if ($scheduler->isBackupDue($site)) {
                        $due[] = 'backup';
                    }
                    if ($scheduler->isWpUpdateDue($site)) {
                        $due[] = 'wp-update';
                    }
                    if ($scheduler->isMalwareScanDue($site)) {
                        $due[] = 'malware';
                    }
                    if ($scheduler->isBrokenLinkAuditDue($site)) {
                        $due[] = 'links';
                    }
                    if ($quarterly->isDue($site)) {
                        $due[] = 'quarterly';
                    }

                    if ($due !== []) {
                        $this->line(sprintf('  %s — %s', $site->displayName(), implode(', ', $due)));
                    }
                }
            });
    }

    return 0;
})->purpose('Preview maintenance tasks due (no jobs queued)');

Artisan::command('report:dry-run {site : Site UUID} {--period= : YYYY-MM period (default: previous month)}', function () {
    $siteId = (string) $this->argument('site');
    $period = $this->option('period') ?: now()->subMonth()->format('Y-m');

    if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
        $this->error('Period must be YYYY-MM format.');

        return 1;
    }

    $site = \App\Models\Site::with('client')->find($siteId);
    if (! $site) {
        $this->error("Site not found: {$siteId}");

        return 1;
    }

    $preview = app(\App\Services\ReportService::class)->renderPreview($siteId, $period);
    if (! $preview) {
        $this->error('Could not render report preview.');

        return 1;
    }

    $this->info("── Report dry-run: {$site->displayName()} — {$period} ──");
    $this->newLine();
    $this->info('Sections found in HTML:');
    foreach ($preview['sections'] as $section) {
        $this->line("  ✓ {$section}");
    }

    $required = ['SSL & Domain Status', 'Site Information', 'Events This Month', 'Backups'];
    $missing  = array_diff($required, $preview['sections']);

    if ($missing !== []) {
        $this->newLine();
        $this->error('Missing required sections: '.implode(', ', $missing));

        return 1;
    }

    $this->newLine();
    $this->info('Required sections present. HTML length: '.strlen($preview['html']).' bytes');

    return 0;
})->purpose('Render monthly report HTML and list sections (no PDF/email)');
