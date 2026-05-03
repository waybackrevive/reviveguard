<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * ExternalScanService — non-invasive external site assessment.
 *
 * All data sourced from:
 *   - WhoisXML API  (WHOIS, SSL, DNS, Reputation)  <- authoritative, reliable
 *   - Direct HTTP   (uptime, response time, CMS fingerprinting, security headers)
 *
 * Results are stored as JSON in site_evaluations.scan_results.
 * Admin sees a rich breakdown in the Filament panel before making any decision.
 */
class ExternalScanService
{
    private Client $http;
    private WhoisXmlService $whoisXml;

    public function __construct(WhoisXmlService $whoisXml)
    {
        $this->whoisXml = $whoisXml;
        $this->http     = new Client([
            'timeout'         => 15,
            'connect_timeout' => 8,
            'allow_redirects' => [
                'max'             => 5,
                'track_redirects' => true,
            ],
            'verify'  => true,
            'headers' => [
                'User-Agent' => 'ReviveGuard-SiteScanner/1.0 (+https://reviveguard.com)',
            ],
        ]);
    }

    /**
     * Run full scan. Returns structured array stored as scan_results JSON.
     */
    public function scan(string $rawUrl): array
    {
        $url    = $this->normaliseUrl($rawUrl);
        $domain = (string) parse_url($url, PHP_URL_HOST);

        Log::info("ExternalScanService: scanning {$url}");

        $result = [
            'scanned_url' => $url,
            'domain'      => $domain,
            'scanned_at'  => now()->toISOString(),

            // Direct HTTP checks
            'http'    => $this->checkHttp($url),
            'headers' => $this->checkSecurityHeaders($url),
            'cms'     => $this->detectCms($url),

            // WhoisXML-powered checks (rich, reliable)
            'ssl'        => $this->whoisXml->ssl($domain),
            'whois'      => $this->whoisXml->whois($domain),
            'dns'        => $this->whoisXml->dns($domain),
            'reputation' => $this->whoisXml->reputation($domain),
        ];

        $result['risk_level']   = $this->deriveRiskLevel($result);
        $result['risk_factors'] = $this->listRiskFactors($result);

        return $result;
    }

    // -- 1. HTTP reachability ----------------------------------------------------

    private function checkHttp(string $url): array
    {
        $start = microtime(true);
        try {
            $response  = $this->http->get($url);
            $elapsed   = (int) round((microtime(true) - $start) * 1000);
            $redirects = $response->getHeader('X-Guzzle-Redirect-History');
            $finalUrl  = end($redirects) ?: $url;

            return [
                'up'             => true,
                'status_code'    => $response->getStatusCode(),
                'response_ms'    => $elapsed,
                'final_url'      => $finalUrl,
                'redirects'      => array_values($redirects),
                'redirect_count' => count($redirects),
                'uses_https'     => str_starts_with((string) ($finalUrl ?: $url), 'https://'),
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

    // -- 2. Security headers -----------------------------------------------------

    private function checkSecurityHeaders(string $url): array
    {
        try {
            $response = $this->http->head($url);
            $raw      = array_change_key_case($response->getHeaders());

            $checks = [
                'strict_transport_security' => isset($raw['strict-transport-security']),
                'x_frame_options'           => isset($raw['x-frame-options']),
                'x_content_type_options'    => isset($raw['x-content-type-options']),
                'content_security_policy'   => isset($raw['content-security-policy']),
                'referrer_policy'           => isset($raw['referrer-policy']),
                'permissions_policy'        => isset($raw['permissions-policy']),
            ];

            $score = array_sum($checks);
            $max   = count($checks);

            return [
                'score'        => $score,
                'max_score'    => $max,
                'grade'        => $this->headerGrade($score, $max),
                'checks'       => $checks,
                'server'       => $raw['server'][0] ?? null,
                'x_powered_by' => $raw['x-powered-by'][0] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'score' => null, 'grade' => '?'];
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

    // -- 3. CMS / WordPress detection --------------------------------------------

    private function detectCms(string $url): array
    {
        $result = [
            'detected'   => 'unknown',
            'confidence' => 'low',
            'wordpress'  => false,
            'wp_version' => null,
            'signals'    => [],
        ];

        try {
            $response = $this->http->get($url, ['timeout' => 12]);
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

            // wp-content / wp-includes in HTML
            if (str_contains($html, '/wp-content/') || str_contains($html, '/wp-includes/')) {
                $signals[]           = 'wp-content-path';
                $result['wordpress'] = true;
                $result['detected']  = 'wordpress';
            }

            // /wp-login.php probe
            try {
                $loginResp = $this->http->get(rtrim($url, '/') . '/wp-login.php', ['timeout' => 6]);
                if ($loginResp->getStatusCode() === 200) {
                    $signals[]           = 'wp-login-found';
                    $result['wordpress'] = true;
                    $result['detected']  = 'wordpress';
                }
            } catch (\Throwable) {}

            // readme.html — can expose WP version
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

    // -- Risk level derivation ---------------------------------------------------

    private function deriveRiskLevel(array $r): string
    {
        $score = 0;

        if (! ($r['http']['up'] ?? true))           $score += 3;
        if ($r['ssl']['expired'] ?? false)           $score += 3;
        if ($r['ssl']['expiring_soon'] ?? false)     $score += 2;
        if ($r['whois']['expired'] ?? false)         $score += 3;
        if ($r['whois']['expiring_soon'] ?? false)   $score += 2;
        if (($r['reputation']['score'] ?? 0) >= 50) $score += 3;
        if (($r['reputation']['score'] ?? 0) >= 25) $score += 1;
        if (! ($r['dns']['has_mx'] ?? true))         $score += 1;
        if (! ($r['dns']['has_spf'] ?? true))        $score += 1;
        if (! ($r['dns']['has_dmarc'] ?? true))      $score += 1;

        $hs = $r['headers']['score'] ?? 6;
        $hm = $r['headers']['max_score'] ?? 6;
        if ($hm > 0 && ($hs / $hm) < 0.33) $score += 1;

        return match (true) {
            $score >= 6 => 'critical',
            $score >= 3 => 'high',
            $score >= 1 => 'medium',
            default     => 'low',
        };
    }

    private function listRiskFactors(array $r): array
    {
        $factors = [];

        if (! ($r['http']['up'] ?? true))
            $factors[] = 'Site is unreachable';
        if ($r['ssl']['expired'] ?? false)
            $factors[] = 'SSL certificate has expired';
        if ($r['ssl']['expiring_soon'] ?? false)
            $factors[] = 'SSL expires in ' . ($r['ssl']['days_remaining'] ?? '?') . ' days';
        if (! ($r['ssl']['valid'] ?? true))
            $factors[] = 'SSL certificate is invalid';
        if ($r['whois']['expired'] ?? false)
            $factors[] = 'Domain has expired';
        if ($r['whois']['expiring_soon'] ?? false)
            $factors[] = 'Domain expires in ' . ($r['whois']['days_remaining'] ?? '?') . ' days';
        if (($r['reputation']['score'] ?? 0) >= 50)
            $factors[] = 'High reputation risk score (' . ($r['reputation']['score'] ?? '?') . '/100)';
        if (! ($r['dns']['has_mx'] ?? true))
            $factors[] = 'No MX records — email not configured';
        if (! ($r['dns']['has_spf'] ?? true))
            $factors[] = 'No SPF record — email spoofing risk';
        if (! ($r['dns']['has_dmarc'] ?? true))
            $factors[] = 'No DMARC record';
        if (($r['headers']['grade'] ?? 'A') === 'F')
            $factors[] = 'Security headers grade F';

        return $factors;
    }

    // -- Helpers -----------------------------------------------------------------

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }
}
