<?php

namespace App\Services;

use App\Enums\EventSeverity;
use App\Models\Event;
use App\Models\Site;
use App\Support\PlanFeatures;
use Illuminate\Support\Facades\Log;

/**
 * Quarterly Shield audits — extended external security scan + SEO snapshot.
 */
final class QuarterlyAuditService
{
    public function __construct(
        private readonly ExternalScanService $externalScan,
        private readonly SeoSnapshotService $seo,
    ) {}

    public function isDue(Site $site): bool
    {
        $features = PlanFeatures::forSite($site);

        if ($features->slug() !== 'shield') {
            return false;
        }

        if (! $site->hasPaidSubscription()) {
            return false;
        }

        $lastAudit = Event::query()
            ->where('site_id', $site->id)
            ->where('type', 'quarterly_security_audit')
            ->orderByDesc('created_at')
            ->first();

        if ($lastAudit?->created_at && $lastAudit->created_at->greaterThan(now()->subDays(89))) {
            return false;
        }

        return true;
    }

    public function runForSite(Site $site): void
    {
        if (! $site->url) {
            return;
        }

        $security = $this->externalScan->scan((string) $site->url);
        $seo      = $this->seo->snapshot((string) $site->url);

        $risk     = (string) ($security['risk_level'] ?? 'unknown');
        $severity = match ($risk) {
            'critical' => EventSeverity::CRITICAL,
            'high'     => EventSeverity::WARNING,
            default    => EventSeverity::INFO,
        };

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'quarterly_security_audit',
            'severity'  => $severity,
            'title'     => 'Quarterly security audit complete',
            'message'   => 'Risk level: '.ucfirst($risk)
                .' · '.count($security['risk_factors'] ?? []).' factor(s) noted.',
            'metadata'  => [
                'risk_level'   => $risk,
                'risk_factors' => array_slice($security['risk_factors'] ?? [], 0, 10),
                'reputation'   => $security['reputation']['score'] ?? null,
                'header_grade' => $security['headers']['grade'] ?? null,
                'scanned_at'   => $security['scanned_at'] ?? now()->toISOString(),
                'scan_summary' => [
                    'ssl_valid'    => $security['ssl']['valid'] ?? null,
                    'http_up'      => $security['http']['up'] ?? null,
                    'cms'          => $security['cms']['detected'] ?? null,
                ],
            ],
        ]);

        $seoIssues = (int) ($seo['missing_titles'] ?? 0)
            + (int) ($seo['slow_pages'] ?? 0)
            + (int) ($seo['http_errors'] ?? 0);

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'quarterly_seo_snapshot',
            'severity'  => $seoIssues > 0 ? EventSeverity::WARNING : EventSeverity::SUCCESS,
            'title'     => 'Quarterly SEO snapshot complete',
            'message'   => sprintf(
                '%d pages crawled · %d missing titles · %d slow · %d errors',
                (int) ($seo['pages_crawled'] ?? 0),
                (int) ($seo['missing_titles'] ?? 0),
                (int) ($seo['slow_pages'] ?? 0),
                (int) ($seo['http_errors'] ?? 0),
            ),
            'metadata'  => $seo,
        ]);

        Log::info('QuarterlyAuditService: completed', [
            'site_id'    => $site->id,
            'risk_level' => $risk,
        ]);
    }

    public function queueDueAudits(): int
    {
        $queued = 0;

        Site::protected()
            ->where('is_active', true)
            ->with('plan')
            ->chunkById(50, function ($sites) use (&$queued): void {
                foreach ($sites as $site) {
                    if ($this->isDue($site)) {
                        \App\Jobs\RunQuarterlyShieldAudit::dispatch($site->id);
                        $queued++;
                    }
                }
            });

        return $queued;
    }
}
