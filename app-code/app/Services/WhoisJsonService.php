<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhoisJSON API fallback — 1,000 free requests/month.
 *
 * @see https://whoisjson.com/
 */
class WhoisJsonService
{
    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $domain = strtolower(preg_replace('/^www\./i', '', trim($domain)) ?: $domain);
        $apiKey = PlatformSetting::get('whoisjson_api_key', config('services.whoisjson.key', '')) ?? '';

        if ($apiKey === '') {
            return ['domain' => $domain, 'error' => 'WhoisJSON API key not configured', 'source' => 'whoisjson'];
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['Authorization' => 'TOKEN=' . $apiKey])
                ->get('https://whoisjson.com/api/v1/whois', ['domain' => $domain]);

            if (! $response->successful()) {
                return [
                    'domain' => $domain,
                    'error'  => "WhoisJSON HTTP {$response->status()}",
                    'source' => 'whoisjson',
                ];
            }

            $data = $response->json();

            if (! ($data['registered'] ?? true)) {
                return ['domain' => $domain, 'error' => 'Domain not registered', 'source' => 'whoisjson'];
            }

            $expiryRaw = $data['expires'] ?? null;

            if (! $expiryRaw) {
                return ['domain' => $domain, 'error' => 'No expiration in WhoisJSON response', 'source' => 'whoisjson'];
            }

            $expiryDate    = Carbon::parse($expiryRaw)->startOfDay();
            $daysRemaining = (int) now()->startOfDay()->diffInDays($expiryDate, false);

            return [
                'domain'         => $domain,
                'registrar'      => $data['registrar']['name'] ?? null,
                'expires_at'     => $expiryDate->toDateString(),
                'days_remaining' => $daysRemaining,
                'expired'        => $daysRemaining < 0,
                'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 60,
                'source'         => 'whoisjson',
            ];
        } catch (\Throwable $e) {
            Log::debug("WhoisJsonService: lookup failed for {$domain}", ['error' => $e->getMessage()]);

            return ['domain' => $domain, 'error' => $e->getMessage(), 'source' => 'whoisjson'];
        }
    }
}
