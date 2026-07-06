<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Inspect live TLS certificates — no third-party API, unlimited checks.
 */
class SslCertificateService
{
    /**
     * @return array{domain: string, valid: bool, issuer?: string, subject?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function inspect(string $host): array
    {
        $host = strtolower(trim($host));
        $bare = preg_replace('/^www\./i', '', $host) ?: $host;

        $candidates = array_values(array_unique(array_filter([
            $host,
            $bare,
            'www.' . $bare,
        ])));

        $lastError = null;

        foreach ($candidates as $candidate) {
            $result = $this->probeHost($candidate);

            if (! isset($result['error'])) {
                return $result;
            }

            $lastError = $result;
        }

        return $lastError ?? ['domain' => $host, 'valid' => false, 'error' => 'SSL check failed', 'source' => 'tls'];
    }

    /**
     * @return array{domain: string, valid: bool, issuer?: string, subject?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    private function probeHost(string $host): array
    {
        try {
            $ctx = stream_context_create(['ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'SNI_enabled'       => true,
                'peer_name'         => $host,
            ]]);

            $sock = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                12,
                STREAM_CLIENT_CONNECT,
                $ctx
            );

            if (! $sock) {
                return ['domain' => $host, 'valid' => false, 'error' => $errstr ?: "Connection failed ({$errno})", 'source' => 'tls'];
            }

            $params = stream_context_get_params($sock);
            fclose($sock);

            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');

            if (! $cert) {
                return ['domain' => $host, 'valid' => false, 'error' => 'Cannot parse certificate', 'source' => 'tls'];
            }

            $expiresAt     = Carbon::createFromTimestamp($cert['validTo_time_t']);
            $daysRemaining = (int) now()->diffInDays($expiresAt, false);

            return [
                'domain'         => $host,
                'valid'          => $daysRemaining > 0,
                'issuer'         => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                'subject'        => $cert['subject']['CN'] ?? $host,
                'expires_at'     => $expiresAt->toDateString(),
                'days_remaining' => $daysRemaining,
                'expired'        => $daysRemaining < 0,
                'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 30,
                'source'         => 'tls',
            ];
        } catch (\Throwable $e) {
            Log::debug("SslCertificateService: inspect failed for {$host}", ['error' => $e->getMessage()]);

            return ['domain' => $host, 'valid' => false, 'error' => $e->getMessage(), 'source' => 'tls'];
        }
    }
}
