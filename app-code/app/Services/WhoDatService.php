<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Self-hosted who-dat RDAP/WHOIS API (optional).
 *
 * @see https://github.com/lissy93/who-dat
 */
class WhoDatService
{
    /**
     * @return array{domain: string, registrar?: string, expires_at?: string, days_remaining?: int, expired?: bool, expiring_soon?: bool, error?: string, source: string}
     */
    public function lookup(string $domain): array
    {
        $domain = strtolower(preg_replace('/^www\./i', '', trim($domain)) ?: $domain);
        $base   = rtrim((string) config('services.who_dat.url', ''), '/');

        if ($base === '') {
            return ['domain' => $domain, 'error' => 'who-dat URL not configured', 'source' => 'who-dat'];
        }

        try {
            $headers = ['User-Agent' => 'ReviveGuard/1.0 (+https://app.reviveguard.com)'];

            if ($key = config('services.who_dat.auth_key')) {
                $headers['Authorization'] = 'Bearer ' . $key;
            }

            $response = Http::timeout(12)
                ->withHeaders($headers)
                ->get("{$base}/v1/whois/" . urlencode($domain));

            if (! $response->successful()) {
                return [
                    'domain' => $domain,
                    'error'  => "who-dat HTTP {$response->status()}",
                    'source' => 'who-dat',
                ];
            }

            $data = $response->json();

            if (! ($data['isRegistered'] ?? true)) {
                return ['domain' => $domain, 'error' => 'Domain not registered', 'source' => 'who-dat'];
            }

            $expiryRaw = $data['expiresAt'] ?? $data['expirationDate'] ?? null;

            if (! $expiryRaw) {
                return ['domain' => $domain, 'error' => 'No expiration in who-dat response', 'source' => 'who-dat'];
            }

            $expiryDate    = Carbon::parse($expiryRaw)->startOfDay();
            $daysRemaining = (int) now()->startOfDay()->diffInDays($expiryDate, false);

            return [
                'domain'         => $domain,
                'registrar'      => $data['registrar'] ?? null,
                'expires_at'     => $expiryDate->toDateString(),
                'days_remaining' => $daysRemaining,
                'expired'        => $daysRemaining < 0,
                'expiring_soon'  => $daysRemaining >= 0 && $daysRemaining <= 60,
                'source'         => 'who-dat',
            ];
        } catch (\Throwable $e) {
            Log::debug("WhoDatService: lookup failed for {$domain}", ['error' => $e->getMessage()]);

            return ['domain' => $domain, 'error' => $e->getMessage(), 'source' => 'who-dat'];
        }
    }
}
