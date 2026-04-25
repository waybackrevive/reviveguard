<?php

namespace App\Jobs;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs daily — checks SSL certificate expiry for all active sites.
 * Fires alert events at 60/30/7 day thresholds (once per threshold per period).
 * Updates sites.ssl_expires_at, ssl_issuer, ssl_valid columns.
 */
final class CheckSslExpiry implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(): void
    {
        Site::where('status', '!=', SiteStatus::SUSPENDED->value)
            ->whereNotNull('url')
            ->chunk(50, function ($sites): void {
                foreach ($sites as $site) {
                    try {
                        $this->checkSite($site);
                    } catch (Throwable $e) {
                        Log::warning("SSL check failed for site {$site->id}: " . $e->getMessage());
                    }
                }
            });
    }

    private function checkSite(Site $site): void
    {
        $host    = (string) parse_url($site->url, PHP_URL_HOST);
        $timeout = 10;

        if (empty($host)) {
            return;
        }

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ],
        ]);

        $stream = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $stream) {
            $site->update(['ssl_valid' => false]);
            $this->createEvent(
                $site,
                'ssl_check_failed',
                EventSeverity::WARNING,
                "SSL check failed for {$site->domain}",
                "Connection error: {$errstr}"
            );
            return;
        }

        $params = stream_context_get_params($stream);
        fclose($stream);

        $certResource = $params['options']['ssl']['peer_certificate'] ?? null;
        if (! $certResource) {
            return;
        }

        $cert = openssl_x509_parse($certResource);
        if (! $cert || ! isset($cert['validTo_time_t'])) {
            return;
        }

        $expiresAt = Carbon::createFromTimestamp((int) $cert['validTo_time_t']);
        $daysLeft  = (int) now()->diffInDays($expiresAt, false);
        $issuer    = $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? 'Unknown');

        $site->update([
            'ssl_expires_at' => $expiresAt->toDateString(),
            'ssl_issuer'     => (string) $issuer,
            'ssl_valid'      => $daysLeft > 0,
        ]);

        // Alert at 60, 30, 7 days — only once per threshold crossing
        foreach ([60, 30, 7] as $threshold) {
            if ($daysLeft <= $threshold && $daysLeft > ($threshold - 1)) {
                $this->dispatchSslAlert($site, $daysLeft, $threshold);
                break;
            }
        }
    }

    private function dispatchSslAlert(Site $site, int $daysLeft, int $threshold): void
    {
        // Guard: don't re-fire if we already alerted for this threshold this period
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
            "SSL certificate for {$site->domain} expires on {$site->ssl_expires_at}. " .
            'Renew now to prevent browsers showing a security warning.',
            ['days_left' => $daysLeft, 'threshold' => $threshold]
        );

        // Email alert
        try {
            (new NotificationService())->sendSslExpiryWarning($site, $daysLeft);
        } catch (\Throwable $e) {
            Log::error('CheckSslExpiry: sendSslExpiryWarning failed', ['error' => $e->getMessage()]);
        }

        Log::warning("SSL expiry alert: {$site->domain} expires in {$daysLeft} days (threshold={$threshold})");
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
