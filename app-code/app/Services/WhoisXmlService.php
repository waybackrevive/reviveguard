<?php

namespace App\Services;

use App\Models\PlatformSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * WhoisXmlService — thin wrapper around the WhoisXML API suite.
 *
 * APIs used (all share the same API key):
 *   1. WHOIS API          — domain registration / expiry / registrar
 *   2. SSL Certificates   — cert expiry, issuer, SANs, chain info
 *   3. DNS Lookup         — A, MX, TXT (SPF, DMARC) records
 *   4. Domain Reputation  — spam score, malware score, risk category
 *
 * All methods return a structured array. On failure they return an array
 * with an 'error' key so callers can display partial results gracefully.
 *
 * Rate limits (trial): 500 WHOIS, 100 SSL, 500 DNS, 50 Reputation credits.
 * In production, upgrade to a paid bundle — all ~$10/mo for our volume.
 */
class WhoisXmlService
{
    private Client $http;
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = PlatformSetting::get('whoisxml_api_key', config('services.whoisxml.key', '')) ?? '';
        $this->http   = new Client([
            'timeout'         => 12,
            'connect_timeout' => 8,
            'headers'         => [
                'Accept'     => 'application/json',
                'User-Agent' => 'ReviveGuard/1.0 (+https://reviveguard.com)',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. WHOIS — domain expiry, creation date, registrar, nameservers
    // ─────────────────────────────────────────────────────────────────────────

    public function whois(string $domain, bool $hardRefresh = false): array
    {
        $domain = $this->stripWww($domain);

        try {
            $query = [
                'apiKey'          => $this->apiKey,
                'domainName'      => $domain,
                'outputFormat'    => 'JSON',
                'preferFresh'     => 1,
                'da'              => 0,  // skip DA to save credits
            ];

            if ($hardRefresh) {
                $query['_hardRefresh'] = 1;
            }

            $resp = $this->http->get('https://www.whoisxmlapi.com/whoisserver/WhoisService', [
                'query' => $query,
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            $r    = $data['WhoisRecord'] ?? [];

            // Parse key dates
            $expiryRaw   = $r['registryData']['expiresDate']      ?? $r['expiresDate']      ?? null;
            $createdRaw  = $r['registryData']['createdDate']       ?? $r['createdDate']      ?? null;
            $updatedRaw  = $r['registryData']['updatedDate']       ?? $r['updatedDate']      ?? null;
            $registrar   = $r['registrarName']                     ?? $r['registryData']['registrarName'] ?? null;
            $status      = $r['status']                            ?? $r['registryData']['status'] ?? null;
            $nameservers = $r['nameServers']['hostNames']          ?? [];
            $privacyShield = str_contains(
                strtolower($r['registrantContact']['organization'] ?? ''),
                'privacy'
            );

            $result = [
                'domain'          => $domain,
                'registrar'       => $registrar,
                'status'          => is_array($status) ? implode(', ', $status) : $status,
                'nameservers'     => array_slice((array) $nameservers, 0, 4),
                'privacy_shield'  => $privacyShield,
                'created_at'      => $createdRaw  ? $this->parseDate($createdRaw)  : null,
                'updated_at'      => $updatedRaw  ? $this->parseDate($updatedRaw)  : null,
                'source'          => 'whoisxml',
            ];

            if ($expiryRaw) {
                $expiryDate              = \Carbon\Carbon::parse($expiryRaw)->startOfDay();
                $daysRemaining           = (int) now()->startOfDay()->diffInDays($expiryDate, false);
                $result['expires_at']    = $expiryDate->toDateString();
                $result['days_remaining']= $daysRemaining;
                $result['expired']       = $daysRemaining < 0;
                $result['expiring_soon'] = $daysRemaining >= 0 && $daysRemaining <= 60;
                $result['domain_age_years'] = $createdRaw
                    ? (int) round(\Carbon\Carbon::parse($createdRaw)->diffInYears(now()))
                    : null;
            }

            return $result;

        } catch (\Throwable $e) {
            Log::warning("WhoisXmlService::whois failed for {$domain}: " . $e->getMessage());
            return ['domain' => $domain, 'error' => $e->getMessage(), 'source' => 'whoisxml'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. SSL Certificates API — expiry, issuer, SANs, chain validation
    // ─────────────────────────────────────────────────────────────────────────

    public function ssl(string $domain): array
    {
        $domain = $this->stripWww($domain);

        try {
            $resp = $this->http->get('https://ssl-certificates.whoisxmlapi.com/api/v1', [
                'query' => [
                    'apiKey' => $this->apiKey,
                    'host'   => $domain,
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true);
            $cert = $data['certificates'][0] ?? $data['certificate'] ?? [];

            // Handle nested structure
            $subject   = $cert['subject']   ?? [];
            $issuer    = $cert['issuer']    ?? [];
            $validity  = $cert['validity']  ?? [];
            $san       = $cert['extensions']['subjectAltName'] ?? '';

            $notAfterRaw = $validity['notAfter'] ?? $cert['notAfter'] ?? null;
            $notBefore   = $validity['notBefore'] ?? $cert['notBefore'] ?? null;

            if (! $notAfterRaw) {
                return ['domain' => $domain, 'error' => 'No certificate data returned', 'source' => 'whoisxml'];
            }

            $expiresAt     = \Carbon\Carbon::parse($notAfterRaw);
            $daysRemaining = (int) now()->diffInDays($expiresAt, false);

            // Extract SANs (comma or space separated)
            $sans = array_filter(
                array_map('trim', preg_split('/[,\s]+/', $san) ?: []),
                fn ($s) => str_starts_with($s, 'DNS:') || str_starts_with($s, '*') || str_contains($s, '.')
            );
            $sans = array_map(fn ($s) => ltrim($s, 'DNS:'), array_values($sans));

            return [
                'domain'          => $domain,
                'valid'           => $daysRemaining > 0 && ($cert['valid'] ?? true),
                'issuer'          => $issuer['organizationName'] ?? $issuer['O'] ?? $issuer['commonName'] ?? $issuer['CN'] ?? 'Unknown',
                'issuer_country'  => $issuer['countryName'] ?? $issuer['C'] ?? null,
                'subject'         => $subject['commonName'] ?? $subject['CN'] ?? $domain,
                'issued_at'       => $notBefore ? \Carbon\Carbon::parse($notBefore)->toDateString() : null,
                'expires_at'      => $expiresAt->toDateString(),
                'days_remaining'  => $daysRemaining,
                'expired'         => $daysRemaining < 0,
                'expiring_soon'   => $daysRemaining >= 0 && $daysRemaining <= 30,
                'sans'            => array_slice($sans, 0, 10),
                'sans_count'      => count($sans),
                'source'          => 'whoisxml',
            ];

        } catch (\Throwable $e) {
            Log::warning("WhoisXmlService::ssl failed for {$domain}: " . $e->getMessage());
            // Graceful fallback to PHP stream
            return $this->sslFallback($domain);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. DNS Lookup — A, MX, TXT (SPF + DMARC), NS records
    // ─────────────────────────────────────────────────────────────────────────

    public function dns(string $domain): array
    {
        $domain = $this->stripWww($domain);

        $result = [
            'domain'     => $domain,
            'a_records'  => [],
            'mx_records' => [],
            'ns_records' => [],
            'spf'        => null,
            'dmarc'      => null,
            'has_mx'     => false,
            'has_spf'    => false,
            'has_dmarc'  => false,
            'source'     => 'whoisxml',
        ];

        // We fetch multiple record types in parallel — DNS API supports type param
        $types = ['A', 'MX', 'TXT', 'NS'];

        foreach ($types as $type) {
            try {
                $resp = $this->http->get('https://www.whoisxmlapi.com/whoisserver/DNSService', [
                    'query' => [
                        'apiKey'       => $this->apiKey,
                        'domainName'   => $domain,
                        'type'         => $type,
                        'outputFormat' => 'JSON',
                    ],
                ]);

                $data    = json_decode((string) $resp->getBody(), true);
                $records = $data['DNSData']['dnsRecords'] ?? [];

                switch ($type) {
                    case 'A':
                        $result['a_records'] = array_column($records, 'address');
                        break;

                    case 'MX':
                        $mxList = [];
                        foreach ($records as $rec) {
                            $mxList[] = [
                                'host'     => rtrim($rec['target'] ?? $rec['name'] ?? '', '.'),
                                'priority' => $rec['priority'] ?? 10,
                            ];
                        }
                        usort($mxList, fn ($a, $b) => $a['priority'] <=> $b['priority']);
                        $result['mx_records'] = $mxList;
                        $result['has_mx']     = count($mxList) > 0;
                        break;

                    case 'TXT':
                        foreach ($records as $rec) {
                            $txt = $rec['strings'][0] ?? $rec['string'] ?? '';
                            if (str_starts_with($txt, 'v=spf1')) {
                                $result['spf']     = $txt;
                                $result['has_spf'] = true;
                            }
                            if (str_starts_with(strtolower($txt), 'v=dmarc1')) {
                                $result['dmarc']     = $txt;
                                $result['has_dmarc'] = true;
                            }
                        }
                        break;

                    case 'NS':
                        $result['ns_records'] = array_map(
                            fn ($r) => rtrim($r['target'] ?? $r['name'] ?? '', '.'),
                            $records
                        );
                        break;
                }

            } catch (\Throwable $e) {
                Log::debug("WhoisXmlService::dns({$type}) failed for {$domain}: " . $e->getMessage());
                // Non-fatal — partial results still useful
            }
        }

        // DMARC is on _dmarc subdomain — check explicitly if not found
        if (! $result['has_dmarc']) {
            try {
                $resp = $this->http->get('https://www.whoisxmlapi.com/whoisserver/DNSService', [
                    'query' => [
                        'apiKey'       => $this->apiKey,
                        'domainName'   => "_dmarc.{$domain}",
                        'type'         => 'TXT',
                        'outputFormat' => 'JSON',
                    ],
                ]);
                $data = json_decode((string) $resp->getBody(), true);
                foreach ($data['DNSData']['dnsRecords'] ?? [] as $rec) {
                    $txt = $rec['strings'][0] ?? $rec['string'] ?? '';
                    if (str_starts_with(strtolower($txt), 'v=dmarc1')) {
                        $result['dmarc']     = $txt;
                        $result['has_dmarc'] = true;
                        break;
                    }
                }
            } catch (\Throwable) {}
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Domain Reputation — spam, malware, risk score 0-100
    // ─────────────────────────────────────────────────────────────────────────

    public function reputation(string $domain): array
    {
        $domain = $this->stripWww($domain);

        try {
            $resp = $this->http->get('https://domain-reputation.whoisxmlapi.com/api/v1', [
                'query' => [
                    'apiKey'     => $this->apiKey,
                    'domainName' => $domain,
                    'mode'       => 'fast',  // 'full' uses more credits but is deeper
                ],
            ]);

            $data       = json_decode((string) $resp->getBody(), true);
            $repData    = $data['reputationScore'] ?? null;
            $components = $data['testResults'] ?? [];

            // Summarise components
            $flagged = [];
            foreach ($components as $test) {
                if (($test['warning'] ?? false) || ($test['threatType'] ?? '') !== '') {
                    $flagged[] = [
                        'test'  => $test['test'] ?? $test['name'] ?? 'unknown',
                        'type'  => $test['threatType'] ?? 'warning',
                        'value' => $test['testResult'] ?? null,
                    ];
                }
            }

            $score = is_numeric($repData) ? (int) round($repData) : null;

            return [
                'domain'     => $domain,
                'score'      => $score,                          // 0–100, higher = riskier
                'risk_label' => $this->reputationLabel($score),
                'flagged'    => $flagged,
                'flag_count' => count($flagged),
                'safe'       => $score !== null && $score < 25,
                'source'     => 'whoisxml',
            ];

        } catch (\Throwable $e) {
            Log::warning("WhoisXmlService::reputation failed for {$domain}: " . $e->getMessage());
            return ['domain' => $domain, 'error' => $e->getMessage(), 'score' => null, 'source' => 'whoisxml'];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function reputationLabel(?int $score): string
    {
        if ($score === null) return 'unknown';
        return match (true) {
            $score < 25  => 'low risk',
            $score < 50  => 'medium risk',
            $score < 75  => 'high risk',
            default      => 'critical risk',
        };
    }

    private function stripWww(string $domain): string
    {
        return preg_replace('/^www\./i', '', $domain);
    }

    private function parseDate(string $raw): string
    {
        try {
            return \Carbon\Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return $raw;
        }
    }

    /**
     * PHP stream_socket_client fallback if WhoisXML SSL API fails.
     * Returns same shape as ssl() so callers are agnostic.
     */
    private function sslFallback(string $domain): array
    {
        try {
            $ctx = stream_context_create(['ssl' => [
                'capture_peer_cert' => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ]]);
            $sock = @stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
            if (! $sock) {
                return ['domain' => $domain, 'valid' => false, 'error' => $errstr, 'source' => 'fallback'];
            }
            $params = stream_context_get_params($sock);
            fclose($sock);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');
            if (! $cert) {
                return ['domain' => $domain, 'valid' => false, 'error' => 'Cannot parse cert', 'source' => 'fallback'];
            }
            $expiresAt     = \Carbon\Carbon::createFromTimestamp($cert['validTo_time_t']);
            $daysRemaining = (int) now()->diffInDays($expiresAt, false);
            return [
                'domain'         => $domain,
                'valid'          => $daysRemaining > 0,
                'issuer'         => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                'subject'        => $cert['subject']['CN'] ?? $domain,
                'expires_at'     => $expiresAt->toDateString(),
                'days_remaining' => $daysRemaining,
                'expired'        => $daysRemaining < 0,
                'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 30,
                'sans'           => [],
                'source'         => 'fallback',
            ];
        } catch (\Throwable $e) {
            return ['domain' => $domain, 'valid' => false, 'error' => $e->getMessage(), 'source' => 'fallback'];
        }
    }
}
