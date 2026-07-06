<?php

namespace App\Jobs;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Services\DomainLookupService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily domain expiry checks via RDAP (free) with optional WhoisXML fallback.
 */
final class CheckDomainExpiry implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(DomainLookupService $domains): void
    {
        Site::protected()
            ->where('status', '!=', SiteStatus::SUSPENDED->value)
            ->whereNotNull('url')
            ->chunk(20, function ($sites) use ($domains): void {
                foreach ($sites as $site) {
                    try {
                        $this->checkDomain($site, $domains);
                    } catch (Throwable $e) {
                        Log::warning('Domain expiry check failed', [
                            'site_id' => $site->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }

                    usleep(500_000);
                }
            });
    }

    private function checkDomain(Site $site, DomainLookupService $domains): void
    {
        $domain = $site->registrableDomain();

        if (! $domain) {
            return;
        }

        $data = $domains->lookup($domain);

        if (isset($data['error']) || ! isset($data['expires_at'])) {
            Log::info("Domain lookup skipped for {$domain}: " . ($data['error'] ?? 'no expiry data'));

            return;
        }

        $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
        $daysLeft  = (int) $data['days_remaining'];
        $registrar = $data['registrar'] ?? null;

        $site->update([
            'domain_expires_at'        => $expiresAt->toDateString(),
            'registrar'                => $registrar,
            'whoisxml_last_checked_at' => now(),
        ]);

        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchDomainAlert($site, $daysLeft, $threshold, $domain);
                break;
            }
        }
    }

    private function dispatchDomainAlert(Site $site, int $daysLeft, int $threshold, string $domain): void
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
            'message'   => "Domain {$domain} expires on {$site->domain_expires_at}. " .
                           'Renew your domain to prevent the website going offline.',
            'metadata'  => ['days_left' => $daysLeft, 'threshold' => $threshold],
            'resolved'  => false,
        ]);

        try {
            (new NotificationService())->sendDomainExpiryWarning($site, $daysLeft);
        } catch (\Throwable $e) {
            Log::error('CheckDomainExpiry: sendDomainExpiryWarning failed', ['error' => $e->getMessage()]);
        }
    }
}
