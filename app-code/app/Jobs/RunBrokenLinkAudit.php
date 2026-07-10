<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\BrokenLinkAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Monthly broken internal link audit (server-side crawl). */
final class RunBrokenLinkAudit implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly string $siteId,
        public readonly string $trigger = 'scheduled',
    ) {}

    public function handle(BrokenLinkAuditService $auditor): void
    {
        $site = Site::with('plan')->find($this->siteId);

        if (! $site || ! $site->url) {
            return;
        }

        $result = $auditor->audit((string) $site->url);
        $auditor->recordResult($site, $result, $this->trigger);

        Log::info('RunBrokenLinkAudit: completed', [
            'site_id'      => $site->id,
            'trigger'      => $this->trigger,
            'broken_count' => $result['broken_count'] ?? null,
        ]);
    }
}
