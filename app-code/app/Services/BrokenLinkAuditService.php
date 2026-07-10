<?php

namespace App\Services;

use App\Enums\EventSeverity;
use App\Models\Event;
use App\Models\Site;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Crawls up to 200 internal links and records broken URL samples.
 */
final class BrokenLinkAuditService
{
    private const MAX_LINKS = 200;

    private const SKIP_EXTENSIONS = [
        'pdf', 'zip', 'rar', '7z', 'gz', 'tar', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'svg', 'ico', 'mp4', 'mp3', 'wav', 'css', 'js', 'woff', 'woff2', 'ttf', 'eot',
    ];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function audit(string $rawUrl): array
    {
        $baseUrl = $this->normaliseUrl($rawUrl);
        $host    = (string) parse_url($baseUrl, PHP_URL_HOST);

        if ($host === '') {
            return [
                'status'         => 'failed',
                'error'          => 'Invalid site URL',
                'total_checked'  => 0,
                'broken_count'   => 0,
                'broken_samples' => [],
            ];
        }

        $http = new Client([
            'timeout'         => 12,
            'connect_timeout' => 8,
            'allow_redirects' => ['max' => 5],
            'verify'          => true,
            'headers'         => [
                'User-Agent' => 'ReviveGuard-LinkAudit/1.0 (+https://reviveguard.com)',
            ],
        ]);

        $queue    = [$baseUrl];
        $visited  = [];
        $broken   = [];
        $enqueued = [];

        while ($queue !== [] && count($visited) < self::MAX_LINKS) {
            $url = array_shift($queue);
            $canonical = $this->canonicalUrl($url);

            if ($canonical === null || isset($visited[$canonical])) {
                continue;
            }

            $visited[$canonical] = true;

            if ($this->shouldSkipUrl($canonical)) {
                continue;
            }

            $statusCode = $this->probeUrl($http, $canonical);

            if ($statusCode === null || $statusCode >= 400) {
                $broken[] = [
                    'url'         => $canonical,
                    'status_code' => $statusCode,
                ];
            }

            if ($statusCode !== null && $statusCode >= 200 && $statusCode < 400) {
                $html = $this->fetchHtml($http, $canonical);
                if ($html !== null) {
                    foreach ($this->extractInternalLinks($html, $baseUrl, $host) as $link) {
                        $linkCanonical = $this->canonicalUrl($link);
                        if ($linkCanonical === null || isset($visited[$linkCanonical]) || isset($enqueued[$linkCanonical])) {
                            continue;
                        }
                        if (count($visited) + count($enqueued) >= self::MAX_LINKS) {
                            break;
                        }
                        $enqueued[$linkCanonical] = true;
                        $queue[] = $linkCanonical;
                    }
                }
            }
        }

        return [
            'status'         => 'success',
            'total_checked'  => count($visited),
            'broken_count'   => count($broken),
            'broken_samples' => array_slice($broken, 0, 10),
            'scanned_at'     => now()->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function recordResult(Site $site, array $result, string $trigger = 'scheduled'): void
    {
        if (($result['status'] ?? '') !== 'success') {
            Event::create([
                'tenant_id' => $site->tenant_id,
                'site_id'   => $site->id,
                'type'      => 'broken_link_audit_failed',
                'severity'  => EventSeverity::WARNING,
                'title'     => 'Broken link audit could not complete',
                'message'   => (string) ($result['error'] ?? 'The audit did not finish successfully.'),
                'metadata'  => ['trigger' => $trigger],
            ]);

            return;
        }

        $brokenCount = (int) ($result['broken_count'] ?? 0);
        $metadata    = [
            'trigger'        => $trigger,
            'total_checked'  => (int) ($result['total_checked'] ?? 0),
            'broken_count'   => $brokenCount,
            'broken_samples' => $result['broken_samples'] ?? [],
            'scanned_at'     => $result['scanned_at'] ?? now()->toISOString(),
        ];

        $severity = $brokenCount > 0 ? EventSeverity::WARNING : EventSeverity::SUCCESS;
        $title    = $brokenCount > 0
            ? "Broken link audit: {$brokenCount} issue(s) found"
            : 'Broken link audit complete — no broken links';

        $message = $brokenCount > 0
            ? $this->buildBrokenMessage($result['broken_samples'] ?? [], $brokenCount)
            : 'Monthly link check finished with no broken internal links.';

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'broken_link_audit_complete',
            'severity'  => $severity,
            'title'     => $title,
            'message'   => $message,
            'metadata'  => $metadata,
        ]);

        if ($brokenCount === 0) {
            return;
        }

        try {
            $this->notifications->sendBrokenLinkAuditAlert(
                $site->load('client'),
                $brokenCount,
                is_array($result['broken_samples'] ?? null) ? $result['broken_samples'] : [],
            );

            $admins = User::query()
                ->where('tenant_id', $site->tenant_id)
                ->where('is_super_admin', true)
                ->pluck('email')
                ->filter()
                ->unique();

            foreach ($admins as $email) {
                $this->notifications->sendAdminSecurityAlert(
                    $email,
                    $site,
                    'broken_links',
                    is_array($result['broken_samples'] ?? null) ? $result['broken_samples'] : [],
                );
            }
        } catch (\Throwable $e) {
            Log::error('BrokenLinkAuditService: notification failed', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function probeUrl(Client $http, string $url): ?int
    {
        try {
            $response = $http->head($url, ['http_errors' => false]);

            return $response->getStatusCode();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse()->getStatusCode();
            }
        } catch (\Throwable) {
            return null;
        }

        try {
            $response = $http->get($url, ['http_errors' => false]);

            return $response->getStatusCode();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchHtml(Client $http, string $url): ?string
    {
        try {
            $response = $http->get($url, ['http_errors' => false]);
            $type     = strtolower((string) $response->getHeaderLine('Content-Type'));

            if (! str_contains($type, 'text/html') && ! str_contains($type, 'application/xhtml')) {
                return null;
            }

            $body = (string) $response->getBody();

            return strlen($body) > 500_000 ? substr($body, 0, 500_000) : $body;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function extractInternalLinks(string $html, string $baseUrl, string $host): array
    {
        $links = [];

        if (! preg_match_all('/href\s*=\s*["\']([^"\']+)["\']/i', $html, $matches)) {
            return $links;
        }

        foreach ($matches[1] as $href) {
            $href = trim(html_entity_decode((string) $href));

            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }

            if (preg_match('/^(mailto:|tel:|javascript:)/i', $href)) {
                continue;
            }

            $absolute = $this->resolveUrl($href, $baseUrl);
            if ($absolute === null) {
                continue;
            }

            $linkHost = (string) parse_url($absolute, PHP_URL_HOST);
            if (strcasecmp($linkHost, $host) !== 0) {
                continue;
            }

            $links[] = $absolute;
        }

        return array_values(array_unique($links));
    }

    private function resolveUrl(string $href, string $baseUrl): ?string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->canonicalUrl($href);
        }

        if (str_starts_with($href, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $this->canonicalUrl($scheme.':'.$href);
        }

        $base = rtrim($baseUrl, '/');

        if (str_starts_with($href, '/')) {
            $parts = parse_url($base);
            $scheme = $parts['scheme'] ?? 'https';
            $host   = $parts['host'] ?? '';

            return $this->canonicalUrl("{$scheme}://{$host}{$href}");
        }

        return $this->canonicalUrl($base.'/'.$href);
    }

    private function canonicalUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $host  = $parts['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $path   = $parts['path'] ?? '/';
        $path   = $path === '' ? '/' : $path;
        $path   = rtrim($path, '/') ?: '/';

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return "{$scheme}://{$host}{$path}{$query}";
    }

    private function shouldSkipUrl(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext !== '' && in_array($ext, self::SKIP_EXTENSIONS, true);
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     */
    private function buildBrokenMessage(array $samples, int $total): string
    {
        $lines = [];
        foreach (array_slice($samples, 0, 3) as $sample) {
            $url = (string) ($sample['url'] ?? 'Unknown URL');
            $code = $sample['status_code'] ?? '?';
            $lines[] = "{$url} (HTTP {$code})";
        }

        $extra = $total - count($lines);
        if ($extra > 0) {
            $lines[] = "+{$extra} more";
        }

        return implode(' · ', $lines);
    }

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }
}
