<?php

namespace App\Services;

use App\Models\PlatformSetting;

/**
 * Domain expiry lookups — RDAP first (free, scalable), WhoisXML optional fallback.
 */
class DomainLookupService
{
    public function __construct(
        private readonly RdapDomainService $rdap,
        private readonly WhoisXmlService $whoisXml,
    ) {}

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $result = $this->rdap->lookup($domain);

        if (! isset($result['error']) && isset($result['expires_at'])) {
            return $result;
        }

        $apiKey = PlatformSetting::get('whoisxml_api_key', config('services.whoisxml.key', '')) ?? '';

        if ($apiKey !== '') {
            $fallback = $this->whoisXml->whois($domain);

            if (! isset($fallback['error']) && isset($fallback['expires_at'])) {
                return $fallback;
            }
        }

        return $result;
    }
}
