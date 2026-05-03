<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * ExternalScanService — performs non-invasive external scans of a prospect's site.
 *
 * All checks use only public information — no credentials required.
 * Results are stored as JSON in site_evaluations.scan_results.
 *
 * Checks performed:
 *   1. HTTP reachability (is site up? response time, final URL, redirect chain)
 *   2. SSL certificate   (expiry, issuer, days remaining)
 *   3. Security headers  (HSTS, CSP, X-Frame-Options, X-Content-Type, etc.)
 *   4. CMS / WordPress detection (generator meta, wp-login.php, readme.txt)
 *   5. WHOIS / domain expiry (via whoisfreaks.com free API or manual parse fallback)
 */
class ExternalScanService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout'         => 12,
            'connect_timeout' => 8,
            'allow_redirects' => [
                'max'             => 5,
                'track_redirects' => true,
            ],
            'verify'          => true,
            'headers'         => [
                'User-Agent' => 'ReviveGuard-SiteScanner/1.0 (+https://reviveguard.com)',
            ],
        ]);
    }

    /**
     * Run all checks on the given URL. Returns a structured result array.
     */
    public function scan(string $rawUrl): array
    {
        $url    = $this->normaliseUrl($rawUrl);
        $domain = parse_url($url, PHP_URL_HOST);

        $result = [
            'scanned_url' => $url,
            'domain'      => $domain,
            'scanned_at'  => now()->toISOString(),
            'http'        => $this->checkHttp($url),
            'ssl'         => $this->checkSsl($domain),
            'headers'     => $this->checkSecurityHeaders($url),
            'cms'         => $this->detectCms($url),
            'whois'       => $this->checkWhois($domain),
        ];

        // Derive overall risk level
        $result['risk_level'] = $this->deriveRiskLevel($result);

        return $result;
    }

    // ── 1. HTTP reachability ─────────────────────────────────────────────────

    private function checkHttp(string $url): array
    {
        $start = microtime(true);
        try {
            $response    = $this->http->get($url);
            $elapsed     = round((microtime(true) - $start) * 1000);
            $redirects   = $response->getHeader('X-Guzzle-Redirect-History');
            $finalUrl    = end($redirects) ?: $url;

            return [
                'up'            => true,
                'status_code'   => $response->getStatusCode(),
                'response_ms'   => $elapsed,
                'final_url'     => $finalUrl,
                'redirects'     => array_values($redirects),
                'redirect_count'=> count($redirects),
                'uses_https'    => str_starts_with($finalUrl ?: $url, 'https://'),
            ];
        } catch (RequestException $e) {
            return [
                'up'          => false,
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'error'       => $e->getMessage(),
                'response_ms' => null,
                'uses_https'  => null,
            ];
        } catch (\Throwable $e) {
            return ['up' => false, 'error' => $e->getMessage()];
        }
    }

    // ── 2. SSL certificate ───────────────────────────────────────────────────

    private function checkSsl(string $domain): array
    {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$domain}:443",
                $errno, $errstr, 10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $client) {
                return ['valid' => false, 'error' => $errstr ?: 'Could not connect'];
            }

            $params = stream_context_get_params($client);
            $cert   = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');
            fclose($client);

            if (! $cert) {
                return ['valid' => false, 'error' => 'Could not parse certificate'];
            }

            $expiresAt      = \Carbon\Carbon::createFromTimestamp($cert['validTo_time_t']);
            $daysRemaining  = (int) now()->diffInDays($expiresAt, false);

            return [
                'valid'          => true,
                'issuer'         => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
                'subject'        => $cert['subject']['CN'] ?? $domain,
                'expires_at'     => $expiresAt->toDateString(),
                'days_remaining' => $daysRemaining,
                'expired'        => $daysRemaining < 0,
                'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 30,
            ];
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // ── 3. Security headers ──────────────────────────────────────────────────

    private function checkSecurityHeaders(string $url): array
    {
        try {
            $response = $this->http->head($url);
            $headers  = array_change_key_case($response->getHeaders());

            $checks = [
                'strict_transport_security' => isset($headers['strict-transport-security']),
                'x_frame_options'           => isset($headers['x-frame-options']),
                'x_content_type_options'    => isset($headers['x-content-type-options']),
                'content_security_policy'   => isset($headers['content-security-policy']),
                'referrer_policy'           => isset($headers['referrer-policy']),
                'permissions_policy'        => isset($headers['permissions-policy']),
            ];

            $score = array_sum($checks);

            return [
                'score'       => $score,
                'max_score'   => count($checks),
                'grade'       => $this->headerGrade($score, count($checks)),
                'checks'      => $checks,
                'server'      => $headers['server'][0] ?? null,
                'x_powered_by'=> $headers['x-powered-by'][0] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'score' => null];
        }
    }

    private function headerGrade(int $score, int $max): string
    {
        $pct = $max > 0 ? ($score / $max) : 0;
        return match (true) {
            $pct >= 0.85 => 'A',
            $pct >= 0.65 => 'B',
            $pct >= 0.45 => 'C',
            $pct >= 0.25 => 'D',
            default      => 'F',
        };
    }

    // ── 4. CMS / WordPress detection ─────────────────────────────────────────

    private function detectCms(string $url): array
    {
        $result = [
            'detected'        => 'unknown',
            'confidence'      => 'low',
            'wordpress'       => false,
            'wp_version'      => null,
            'signals'         => [],
        ];

        try {
            // Fetch homepage HTML
            $response = $this->http->get($url, ['timeout' => 10]);
            $html     = (string) $response->getBody();
            $headers  = array_change_key_case($response->getHeaders());

            $signals  = [];

            // Generator meta tag
            if (preg_match('/<meta[^>]+name=["\']generator["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
                $signals[] = 'generator:' . $m[1];
                if (str_contains(strtolower($m[1]), 'wordpress')) {
                    $result['wordpress'] = true;
                    $result['detected']  = 'wordpress';
                    if (preg_match('/wordpress\s+([\d.]+)/i', $m[1], $ver)) {
                        $result['wp_version'] = $ver[1];
                    }
                }
            }

            // wp-content in HTML
            if (str_contains($html, '/wp-content/') || str_contains($html, '/wp-includes/')) {
                $signals[]           = 'wp-content-path';
                $result['wordpress'] = true;
                $result['detected']  = 'wordpress';
            }

            // Check /wp-login.php exists
            try {
                $loginResp = $this->http->get(rtrim($url, '/') . '/wp-login.php', ['timeout' => 6]);
                if ($loginResp->getStatusCode() === 200) {
                    $signals[]           = 'wp-login-found';
                    $result['wordpress'] = true;
                    $result['detected']  = 'wordpress';
                }
            } catch (\Throwable) {}

            // Check readme.txt for WP version
            try {
                $readmeResp = $this->http->get(rtrim($url, '/') . '/readme.html', ['timeout' => 6]);
                if ($readmeResp->getStatusCode() === 200) {
                    $readmeHtml = (string) $readmeResp->getBody();
                    if (preg_match('/version\s+([\d.]+)/i', $readmeHtml, $ver)) {
                        $result['wp_version'] = $result['wp_version'] ?? $ver[1];
                        $signals[]            = 'readme-version:' . $ver[1];
                    }
                    $result['wordpress'] = true;
                    $result['detected']  = 'wordpress';
                }
            } catch (\Throwable) {}

            // X-Powered-By header
            if (isset($headers['x-powered-by'])) {
                $signals[] = 'x-powered-by:' . $headers['x-powered-by'][0];
            }

            $result['signals']    = $signals;
            $result['confidence'] = count($signals) >= 2 ? 'high' : (count($signals) === 1 ? 'medium' : 'low');

        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    // ── 5. WHOIS / domain expiry ─────────────────────────────────────────────

    private function checkWhois(string $domain): array
    {
        // Strip www prefix
        $domain = preg_replace('/^www\./i', '', $domain);

        // Try whoisfreaks free API (no key needed for basic lookups)
        try {
            $apiResp = $this->http->get("https://api.whoisfreaks.com/v1.0/whois", [
                'query'   => ['whois' => 'live', 'domainName' => $domain, 'format' => 'json'],
                'timeout' => 8,
            ]);
            $data = json_decode((string) $apiResp->getBody(), true);

            $expiryRaw = $data['domain_registrar']['domain_expiration_date']
                      ?? $data['registry_data']['expiration_date']
                      ?? null;

            if ($expiryRaw) {
                $expiryDate    = \Carbon\Carbon::parse($expiryRaw);
                $daysRemaining = (int) now()->diffInDays($expiryDate, false);
                return [
                    'domain'         => $domain,
                    'registrar'      => $data['domain_registrar']['registrar_name'] ?? null,
                    'expires_at'     => $expiryDate->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'expired'        => $daysRemaining < 0,
                    'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 60,
                    'source'         => 'whoisfreaks',
                ];
            }
        } catch (\Throwable) {}

        // Fallback: try io-developer/php-whois if available
        if (class_exists(\Iodev\Whois\Factory::class)) {
            try {
                $whois  = \Iodev\Whois\Factory::get()->createWhois();
                $info   = $whois->loadDomainInfo($domain);
                if ($info && $info->expirationDate) {
                    $expiryDate    = \Carbon\Carbon::createFromTimestamp($info->expirationDate);
                    $daysRemaining = (int) now()->diffInDays($expiryDate, false);
                    return [
                        'domain'         => $domain,
                        'registrar'      => $info->registrar ?? null,
                        'expires_at'     => $expiryDate->toDateString(),
                        'days_remaining' => $daysRemaining,
                        'expired'        => $daysRemaining < 0,
                        'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 60,
                        'source'         => 'php-whois',
                    ];
                }
            } catch (\Throwable) {}
        }

        return ['domain' => $domain, 'error' => 'Could not fetch WHOIS data'];
    }

    // ── Risk level derivation ─────────────────────────────────────────────────

    private function deriveRiskLevel(array $result): string
    {
        $risks = 0;

        // Site is down
        if (! ($result['http']['up'] ?? true)) $risks += 3;

        // SSL expired or expiring within 30 days
        if ($result['ssl']['expired'] ?? false) $risks += 3;
        if ($result['ssl']['expiring_soon'] ?? false) $risks += 2;

        // Domain expiring within 60 days
        if ($result['whois']['expired'] ?? false) $risks += 3;
        if ($result['whois']['expiring_soon'] ?? false) $risks += 2;

        // Poor security headers
        $headerScore = $result['headers']['score'] ?? 6;
        $headerMax   = $result['headers']['max_score'] ?? 6;
        if ($headerMax > 0 && ($headerScore / $headerMax) < 0.33) $risks += 1;

        return match (true) {
            $risks >= 5 => 'critical',
            $risks >= 3 => 'high',
            $risks >= 1 => 'medium',
            default     => 'low',
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }
}
