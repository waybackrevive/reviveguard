<?php

namespace App\Jobs;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Services\NotificationService;
use App\Services\WhoisXmlService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs daily — checks domain WHOIS expiry for all active sites.
 * Fires alert events at 60/30/7 day thresholds (once per threshold per period).
 * Updates sites.domain_expires_at and registrar columns.
 *
 * WHOIS lookups are rate-limited server-side — 2s sleep between requests.
 * Unsupported TLDs or failed lookups are skipped silently.
 */
final class CheckDomainExpiry implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    private WhoisXmlService $whoisXml;

    public function handle(WhoisXmlService $whoisXml): void
    {
        $this->whoisXml = $whoisXml;

        Site::where('status', '!=', SiteStatus::SUSPENDED->value)
            ->whereNotNull('domain')
            ->chunk(20, function ($sites): void {
                foreach ($sites as $site) {
                    try {
                        $this->checkDomain($site);
                    } catch (Throwable $e) {
                        Log::warning("Domain expiry check failed for {$site->domain}: " . $e->getMessage());
                    }

                }
            });
    }

    private function checkDomain(Site $site): void
    {
        $data = $this->whoisXml->whois((string) $site->domain);

        if (isset($data['error']) || ! isset($data['expires_at'])) {
            Log::info("WHOIS lookup skipped for {$site->domain}: " . ($data['error'] ?? 'no expiry data'));
            return;
        }

        $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
        $daysLeft  = (int) $data['days_remaining'];
        $registrar = $data['registrar'] ?? null;

        $site->update([
            'domain_expires_at' => $expiresAt->toDateString(),
            'registrar'         => $registrar,
        ]);

        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchDomainAlert($site, $daysLeft, $threshold);
                break;
            }
        }
    }

    private function dispatchDomainAlert(Site $site, int $daysLeft, int $threshold): void
    {
        $alreadySent = Event::where('site_id', $site->id)
            ->where('type', 'domain_expiry_warning')
            ->whereRaw("metadata->>'threshold' = ?", [(string) $threshold])
            ->where('created_at', '>=', now()->subDays($threshold + 5))
            ->exists();

        if ($alreadySent) {
            return;
        }

        $severity = $daysLeft <= 7 ? EventSeverity::CRITICAL : EventSeverity::WARNING;

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'domain_expiry_warning',
            'severity'  => $severity->value,
            'title'     => "Domain expires in {$daysLeft} days",
            'message'   => "Domain {$site->domain} expires on {$site->domain_expires_at}. " .
                           'Renew your domain to prevent the website going offline.',
            'metadata'  => ['days_left' => $daysLeft, 'threshold' => $threshold],
            'resolved'  => false,
        ]);

        // Email alert
        try {
            (new NotificationService())->sendDomainExpiryWarning($site, $daysLeft);
        } catch (\Throwable $e) {
            Log::error('CheckDomainExpiry: sendDomainExpiryWarning failed', ['error' => $e->getMessage()]);
        }

        Log::warning("Domain expiry alert: {$site->domain} expires in {$daysLeft} days (threshold={$threshold})");
    }
}
