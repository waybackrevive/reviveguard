<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Uptime Kuma API integration (headless, using the REST API exposed on the instance).
 * Only creates/deletes monitors — we don't need full UI access.
 */
class UptimeKumaService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(PlatformSetting::get('uptime_kuma_url',    config('services.uptime_kuma.url', ''))    ?? '', '/');
        $this->apiKey  = PlatformSetting::get('uptime_kuma_api_key', config('services.uptime_kuma.api_key', '')) ?? '';
    }

    /**
     * Create an HTTP monitor for the given site URL.
     * Returns the monitor ID on success, null on failure (non-fatal).
     */
    public function createMonitor(string $siteName, string $siteUrl): ?int
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::info('UptimeKuma: skipping monitor creation (not configured)', ['site' => $siteUrl]);
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/api/v1/monitor", [
                    'type'     => 'http',
                    'name'     => $siteName,
                    'url'      => $siteUrl,
                    'interval' => 300, // 5-minute check
                    'retryInterval' => 300,
                    'maxretries'    => 1,
                ]);

            if ($response->successful()) {
                $monitorId = $response->json('monitorID') ?? $response->json('id');
                Log::info('UptimeKuma: monitor created', ['id' => $monitorId, 'site' => $siteUrl]);
                return $monitorId ? (int) $monitorId : null;
            }

            Log::warning('UptimeKuma: failed to create monitor', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'site'   => $siteUrl,
            ]);
        } catch (\Throwable $e) {
            Log::warning('UptimeKuma: exception creating monitor', [
                'error' => $e->getMessage(),
                'site'  => $siteUrl,
            ]);
        }

        return null;
    }

    /**
     * Get uptime percentage for a monitor over the given number of days.
     * Returns null when Kuma is not configured or the request fails.
     */
    public function getUptimePercent(int $monitorId, int $days = 30): ?float
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return null;
        }

        $hours = $days * 24;

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->get("{$this->baseUrl}/api/v1/monitor/{$monitorId}/uptime/{$hours}");

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return round((float) ($data['uptime'] ?? 0) * 100, 2);
        } catch (\Throwable $e) {
            Log::warning('UptimeKuma: getUptimePercent failed', [
                'error'      => $e->getMessage(),
                'monitor_id' => $monitorId,
            ]);

            return null;
        }
    }

    /**
     * Delete a monitor by ID (called when a site is deleted).
     */
    public function deleteMonitor(int $monitorId): bool
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->delete("{$this->baseUrl}/api/v1/monitor/{$monitorId}");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('UptimeKuma: exception deleting monitor', [
                'error' => $e->getMessage(),
                'id'    => $monitorId,
            ]);
            return false;
        }
    }
}
