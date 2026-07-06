<?php

namespace App\Jobs;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Services\NotificationService;
use App\Services\SslCertificateService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily SSL certificate checks for protected (paid) sites.
 */
final class CheckSslExpiry implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(SslCertificateService $ssl): void
    {
        Site::protected()
            ->where('status', '!=', SiteStatus::SUSPENDED->value)
            ->whereNotNull('url')
            ->chunk(50, function ($sites) use ($ssl): void {
                foreach ($sites as $site) {
                    try {
                        $this->checkSite($site, $ssl);
                    } catch (Throwable $e) {
                        Log::warning("SSL check failed for site {$site->id}: " . $e->getMessage());
                    }
                }
            });
    }

    private function checkSite(Site $site, SslCertificateService $ssl): void
    {
        $host = $site->hostname();

        if (empty($host)) {
            return;
        }

        $data = $ssl->inspect($host);

        if (isset($data['error'])) {
            $site->update(['ssl_valid' => false]);
            $this->createEvent(
                $site,
                'ssl_check_failed',
                EventSeverity::WARNING,
                "SSL check failed for {$host}",
                $data['error']
            );

            return;
        }

        $expiresAt = Carbon::parse($data['expires_at']);
        $daysLeft  = (int) $data['days_remaining'];
        $issuer    = $data['issuer'] ?? 'Unknown';

        $site->update([
            'ssl_expires_at' => $expiresAt->toDateString(),
            'ssl_issuer'     => (string) $issuer,
            'ssl_valid'      => $data['valid'] ?? ($daysLeft > 0),
        ]);

        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchSslAlert($site, $daysLeft, $threshold, $host);
                break;
            }
        }
    }

    private function dispatchSslAlert(Site $site, int $daysLeft, int $threshold, string $host): void
    {
        $alreadySent = Event::where('site_id', $site->id)
            ->where('type', 'ssl_expiry_warning')
            ->whereRaw("metadata->>'threshold' = ?", [(string) $threshold])
            ->where('created_at', '>=', now()->subDays($threshold + 5))
            ->exists();

        if ($alreadySent) {
            return;
        }

        $severity = $daysLeft <= 7 ? EventSeverity::CRITICAL : EventSeverity::WARNING;

        $this->createEvent(
            $site,
            'ssl_expiry_warning',
            $severity,
            "SSL certificate expires in {$daysLeft} days",
            "SSL certificate for {$host} expires on {$site->ssl_expires_at}. " .
            'Renew now to prevent browsers showing a security warning.',
            ['days_left' => $daysLeft, 'threshold' => $threshold]
        );

        try {
            (new NotificationService())->sendSslExpiryWarning($site, $daysLeft);
        } catch (\Throwable $e) {
            Log::error('CheckSslExpiry: sendSslExpiryWarning failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function createEvent(
        Site $site,
        string $type,
        EventSeverity $severity,
        string $title,
        string $message,
        array $metadata = []
    ): void {
        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => $type,
            'severity'  => $severity->value,
            'title'     => $title,
            'message'   => $message,
            'metadata'  => $metadata,
            'resolved'  => false,
        ]);
    }
}
