<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Authoritative domain expiry via WHOIS (port 43) — no API keys, registrar-grade data.
 *
 * Queries the registry / registrar WHOIS server directly (same data registrars use).
 */
class WhoisSocketService
{
    /** @var array<string, string> */
    private const REGISTRY_SERVERS = [
        'com'  => 'whois.verisign-grs.com',
        'net'  => 'whois.verisign-grs.com',
        'org'  => 'whois.pir.org',
        'io'   => 'whois.nic.io',
        'co'   => 'whois.nic.co',
        'info' => 'whois.afilias.net',
        'biz'  => 'whois.biz',
        'us'   => 'whois.nic.us',
    ];

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $domain = strtolower(preg_replace('/^www\./i', '', trim($domain)) ?: $domain);
        $labels = explode('.', $domain);
        $tld    = end($labels) ?: '';

        $server = self::REGISTRY_SERVERS[$tld] ?? null;

        if (! $server) {
            return ['domain' => $domain, 'error' => "WHOIS server unknown for .{$tld}", 'source' => 'whois'];
        }

        try {
            $raw = $this->query($server, $domain);

            if (str_contains($raw, 'TLD is not supported')) {
                return ['domain' => $domain, 'error' => 'TLD not supported', 'source' => 'whois'];
            }

            $parsed = $this->parse($domain, $raw);

            if (! isset($parsed['expires_at'])) {
                $referral = $this->extractReferralServer($raw);

                if ($referral && $referral !== $server) {
                    $raw      = $this->query($referral, $domain);
                    $parsed   = $this->parse($domain, $raw);
                }
            }

            if (! isset($parsed['expires_at'])) {
                return array_merge($parsed, ['error' => 'Expiration date not found in WHOIS']);
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::debug("WhoisSocketService: lookup failed for {$domain}", ['error' => $e->getMessage()]);

            return ['domain' => $domain, 'error' => $e->getMessage(), 'source' => 'whois'];
        }
    }

    private function query(string $server, string $domain): string
    {
        $socket = @fsockopen($server, 43, $errno, $errstr, 10);

        if (! $socket) {
            throw new \RuntimeException("WHOIS connect failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 10);
        fwrite($socket, $domain . "\r\n");

        $response = '';

        while (! feof($socket)) {
            $chunk = fread($socket, 8192);

            if ($chunk === false) {
                break;
            }

            $response .= $chunk;
        }

        fclose($socket);

        return $response;
    }

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, source: string}
     */
    public function parse(string $domain, string $raw): array
    {
        $result = ['domain' => $domain, 'source' => 'whois'];

        if (preg_match('/^Registrar:\s*(.+)$/mi', $raw, $m)) {
            $result['registrar'] = trim($m[1]);
        } elseif (preg_match('/^Registrar Name:\s*(.+)$/mi', $raw, $m)) {
            $result['registrar'] = trim($m[1]);
        }

        $expiryRaw = null;

        foreach ([
            'Registry Expiry Date',
            'Registrar Registration Expiration Date',
            'Expiry Date',
            'Expiration Date',
            'paid-till',
            'renewal date',
        ] as $label) {
            if (preg_match('/^' . preg_quote($label, '/') . ':\s*(.+)$/mi', $raw, $m)) {
                $expiryRaw = trim($m[1]);
                break;
            }
        }

        if (! $expiryRaw) {
            return $result;
        }

        $expiryDate    = Carbon::parse($expiryRaw)->startOfDay();
        $daysRemaining = (int) now()->startOfDay()->diffInDays($expiryDate, false);

        return array_merge($result, [
            'expires_at'     => $expiryDate->toDateString(),
            'days_remaining' => $daysRemaining,
            'expired'        => $daysRemaining < 0,
            'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 60,
        ]);
    }

    private function extractReferralServer(string $raw): ?string
    {
        if (preg_match('/^Registrar WHOIS Server:\s*(.+)$/mi', $raw, $m)) {
            return strtolower(trim($m[1]));
        }

        return null;
    }
}
