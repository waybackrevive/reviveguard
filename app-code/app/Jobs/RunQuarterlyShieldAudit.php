<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\QuarterlyAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Quarterly Shield security + SEO audit for one site. */
final class RunQuarterlyShieldAudit implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public readonly string $siteId,
    ) {}

    public function handle(QuarterlyAuditService $audits): void
    {
        $site = Site::with('plan')->find($this->siteId);

        if (! $site) {
            return;
        }

        $audits->runForSite($site);
    }
}
