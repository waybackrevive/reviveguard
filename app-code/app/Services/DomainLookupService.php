<?php

namespace App\Services;

use App\Models\PlatformSetting;

/**
 * Domain expiry — WHOIS socket (authoritative) → RDAP → optional WhoisXML.
 */
class DomainLookupService
{
    public function __construct(
        private readonly WhoisSocketService $whoisSocket,
        private readonly RdapDomainService $rdap,
        private readonly WhoisXmlService $whoisXml,
    ) {}

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $whois = $this->whoisSocket->lookup($domain);

        if (! isset($whois['error']) && isset($whois['expires_at'])) {
            return $whois;
        }

        $rdap = $this->rdap->lookup($domain);

        if (! isset($rdap['error']) && isset($rdap['expires_at'])) {
            return $rdap;
        }

        $apiKey = PlatformSetting::get('whoisxml_api_key', config('services.whoisxml.key', '')) ?? '';

        if ($apiKey !== '') {
            $api = $this->whoisXml->whois($domain);

            if (! isset($api['error']) && isset($api['expires_at'])) {
                return $api;
            }
        }

        return $whois['error'] ? $whois : $rdap;
    }
}
