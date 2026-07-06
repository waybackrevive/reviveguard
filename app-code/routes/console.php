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

Artisan::command('sites:refresh-health {site?}', function (?string $siteId) {
    $query = \App\Models\Site::protected()->whereNotNull('url');

    if ($siteId) {
        $query->where('id', $siteId);
    }

    $sites = $query->get();

    if ($sites->isEmpty()) {
        $this->warn('No protected sites with a URL found.');

        return 1;
    }

    foreach ($sites as $site) {
        \App\Jobs\RefreshSiteHealthJob::dispatchSync($site->id);
        $this->line("Refreshed: {$site->displayName()}");
    }

    $this->info('Health refresh complete.');

    return 0;
})->purpose('Run SSL, domain, and uptime checks for protected sites');
