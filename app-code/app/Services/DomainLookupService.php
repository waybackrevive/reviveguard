<?php

namespace App\Services;

/**
 * Domain expiry — WHOIS socket → RDAP → who-dat → WhoisJSON → WhoisXML.
 */
class DomainLookupService
{
    public function __construct(
        private readonly WhoisSocketService $whoisSocket,
        private readonly RdapDomainService $rdap,
        private readonly WhoDatService $whoDat,
        private readonly WhoisJsonService $whoisJson,
        private readonly WhoisXmlService $whoisXml,
    ) {}

    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $resolvers = [
            fn () => $this->whoisSocket->lookup($domain),
            fn () => $this->rdap->lookup($domain),
        ];

        if (config('services.who_dat.url')) {
            $resolvers[] = fn () => $this->whoDat->lookup($domain);
        }

        if (config('services.whoisjson.key')) {
            $resolvers[] = fn () => $this->whoisJson->lookup($domain);
        }

        if (config('services.whoisxml.key')) {
            $resolvers[] = fn () => $this->whoisXml->whois($domain);
        }

        $result = null;

        foreach ($resolvers as $resolver) {
            $result = $resolver();

            if (! isset($result['error']) && isset($result['expires_at'])) {
                return $result;
            }
        }

        return $result ?? ['domain' => $domain, 'error' => 'All domain lookup methods failed', 'source' => 'none'];
    }
}
