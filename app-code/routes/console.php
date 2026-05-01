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
