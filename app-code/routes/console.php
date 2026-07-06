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

    return 0;
})->purpose('Show monitoring configuration and per-site health data');
