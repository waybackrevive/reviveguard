<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Domain registration lookups via public RDAP (IANA bootstrap).
 *
 * No API key, no per-query billing — suitable for 50–100+ client sites on a daily schedule.
 * Falls back gracefully when a TLD has no RDAP server.
 */
class RdapDomainService
{
    private const BOOTSTRAP_URL = 'https://data.iana.org/rdap/dns.json';

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $domain = strtolower(preg_replace('/^www\./i', '', trim($domain)) ?: $domain);

        $baseUrl = $this->rdapBaseUrl($domain);

        if (! $baseUrl) {
            return ['domain' => $domain, 'error' => 'No RDAP server for this TLD', 'source' => 'rdap'];
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'ReviveGuard/1.0 (+https://app.reviveguard.com)'])
                ->get(rtrim($baseUrl, '/') . '/domain/' . urlencode($domain));

            if (! $response->successful()) {
                return [
                    'domain' => $domain,
                    'error'  => "RDAP HTTP {$response->status()}",
                    'source' => 'rdap',
                ];
            }

            return $this->parseResponse($domain, $response->json());
        } catch (\Throwable $e) {
            Log::debug("RdapDomainService: lookup failed for {$domain}", ['error' => $e->getMessage()]);

            return ['domain' => $domain, 'error' => $e->getMessage(), 'source' => 'rdap'];
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    private function parseResponse(string $domain, array $data): array
    {
        $expiryRaw = null;

        foreach ($data['events'] ?? [] as $event) {
            if (($event['eventAction'] ?? '') === 'expiration') {
                $expiryRaw = $event['eventDate'] ?? null;
                break;
            }
        }

        $registrar = null;

        foreach ($data['entities'] ?? [] as $entity) {
            $roles = $entity['roles'] ?? [];
            if (in_array('registrar', $roles, true)) {
                $registrar = $entity['vcardArray'][1][1][3] ?? $entity['handle'] ?? null;
                break;
            }
        }

        $result = [
            'domain'    => $domain,
            'registrar' => is_string($registrar) ? $registrar : null,
            'source'    => 'rdap',
        ];

        if (! $expiryRaw) {
            return array_merge($result, ['error' => 'No expiration date in RDAP response']);
        }

        $expiryDate    = Carbon::parse($expiryRaw)->startOfDay();
        $daysRemaining = (int) now()->startOfDay()->diffInDays($expiryDate, false);

        return array_merge($result, [
            'expires_at'     => $expiryDate->toDateString(),
            'days_remaining' => $daysRemaining,
            'expired'        => $daysRemaining < 0,
            'expiring_soon'    => $daysRemaining >= 0 && $daysRemaining <= 60,
        ]);
    }

    private function rdapBaseUrl(string $domain): ?string
    {
        $labels = explode('.', $domain);

        if (count($labels) < 2) {
            return null;
        }

        $bootstrap = $this->bootstrap();

        for ($i = 0; $i < count($labels); $i++) {
            $suffix = implode('.', array_slice($labels, $i));

            foreach ($bootstrap as $entry) {
                if (in_array($suffix, $entry['tlds'], true)) {
                    return $entry['url'];
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{tlds: list<string>, url: string}>
     */
    private function bootstrap(): array
    {
        return Cache::remember('rdap_dns_bootstrap', 86400, function () {
            $response = Http::timeout(15)->get(self::BOOTSTRAP_URL);

            if (! $response->successful()) {
                Log::warning('RdapDomainService: failed to load IANA bootstrap');

                return [];
            }

            $parsed = [];

            foreach ($response->json('services') ?? [] as $service) {
                $tlds = $service[0] ?? [];
                $urls = $service[1] ?? [];

                if (! empty($tlds) && ! empty($urls[0])) {
                    $parsed[] = [
                        'tlds' => array_map('strtolower', $tlds),
                        'url'  => rtrim((string) $urls[0], '/') . '/',
                    ];
                }
            }

            return $parsed;
        });
    }
}
