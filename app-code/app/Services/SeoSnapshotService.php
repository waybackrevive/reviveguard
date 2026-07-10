<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Basic SEO snapshot — missing titles, slow pages, HTTP errors.
 */
final class SeoSnapshotService
{
    private const MAX_PAGES = 50;

    private const SLOW_MS = 3000;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(string $rawUrl): array
    {
        $baseUrl = $this->normaliseUrl($rawUrl);
        $host    = (string) parse_url($baseUrl, PHP_URL_HOST);

        if ($host === '') {
            return ['status' => 'failed', 'error' => 'Invalid URL'];
        }

        $http = new Client([
            'timeout'         => 15,
            'connect_timeout' => 8,
            'allow_redirects' => ['max' => 5],
            'verify'          => true,
            'headers'         => [
                'User-Agent' => 'ReviveGuard-SEO/1.0 (+https://reviveguard.com)',
            ],
        ]);

        $queue     = [$baseUrl];
        $visited   = [];
        $missing   = [];
        $slow      = [];
        $errors    = [];
        $enqueued  = [];

        while ($queue !== [] && count($visited) < self::MAX_PAGES) {
            $url       = array_shift($queue);
            $canonical = $this->canonicalUrl($url);

            if ($canonical === null || isset($visited[$canonical])) {
                continue;
            }

            $visited[$canonical] = true;
            $start               = microtime(true);

            try {
                $response   = $http->get($canonical, ['http_errors' => false]);
                $elapsedMs  = (int) round((microtime(true) - $start) * 1000);
                $statusCode = $response->getStatusCode();
                $type       = strtolower((string) $response->getHeaderLine('Content-Type'));

                if ($statusCode >= 400) {
                    $errors[] = ['url' => $canonical, 'status_code' => $statusCode];
                    continue;
                }

                if ($elapsedMs >= self::SLOW_MS) {
                    $slow[] = ['url' => $canonical, 'response_ms' => $elapsedMs];
                }

                if (! str_contains($type, 'text/html')) {
                    continue;
                }

                $html = (string) $response->getBody();
                if (strlen($html) > 400_000) {
                    $html = substr($html, 0, 400_000);
                }

                if (! preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $titleMatch)
                    || trim($titleMatch[1]) === '') {
                    $missing[] = $canonical;
                }

                foreach ($this->extractInternalLinks($html, $canonical, $host) as $link) {
                    $linkCanonical = $this->canonicalUrl($link);
                    if ($linkCanonical === null || isset($visited[$linkCanonical]) || isset($enqueued[$linkCanonical])) {
                        continue;
                    }
                    if (count($visited) + count($enqueued) >= self::MAX_PAGES) {
                        break;
                    }
                    $enqueued[$linkCanonical] = true;
                    $queue[] = $linkCanonical;
                }
            } catch (RequestException $e) {
                $errors[] = [
                    'url'   => $canonical,
                    'error' => $e->getMessage(),
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'url'   => $canonical,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status'              => 'success',
            'pages_crawled'       => count($visited),
            'missing_titles'      => count($missing),
            'missing_title_urls'  => array_slice($missing, 0, 10),
            'slow_pages'          => count($slow),
            'slow_page_samples'   => array_slice($slow, 0, 10),
            'http_errors'         => count($errors),
            'error_samples'       => array_slice($errors, 0, 10),
            'scanned_at'          => now()->toISOString(),
        ];
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
            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto:|tel:|javascript:)/i', $href)) {
                continue;
            }

            $absolute = $this->resolveUrl($href, $baseUrl);
            if ($absolute === null) {
                continue;
            }

            if (strcasecmp((string) parse_url($absolute, PHP_URL_HOST), $host) !== 0) {
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

        $base = rtrim($baseUrl, '/');

        if (str_starts_with($href, '/')) {
            $parts  = parse_url($base);
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
        $path   = rtrim($parts['path'] ?? '/', '/') ?: '/';

        return "{$scheme}://{$host}{$path}";
    }

    private function normaliseUrl(string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }
}
