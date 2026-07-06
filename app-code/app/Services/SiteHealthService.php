<?php

namespace App\Services;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Refresh monitoring data for a single protected site (SSL, domain, uptime).
 */
class SiteHealthService
{
    public function __construct(
        private readonly SslCertificateService $ssl,
        private readonly DomainLookupService $domains,
        private readonly UptimeKumaService $uptimeKuma,
        private readonly SiteUptimeService $siteUptime,
        private readonly WhoisXmlService $whoisXml,
    ) {}

    public function refresh(Site $site): void
    {
        $site->loadMissing('subscription');

        if (! $site->hasPaidSubscription()) {
            return;
        }

        $host = $site->hostname();

        if (! $host) {
            return;
        }

        $this->refreshSsl($site, $host);
        $this->refreshDomain($site, $host);
        $this->refreshUptime($site);
    }

    private function refreshSsl(Site $site, string $host): void
    {
        $data = $this->ssl->inspect($host);

        if ((isset($data['error']) || ! isset($data['expires_at'])) && config('services.whoisxml.key')) {
            $api = $this->whoisXml->ssl($host);

            if (! isset($api['error']) && isset($api['expires_at'])) {
                $data = $api;
            }
        }

        if (isset($data['error']) || ! isset($data['expires_at'])) {
            Log::info('SiteHealthService: SSL inspect failed', [
                'site_id' => $site->id,
                'host'    => $host,
                'error'   => $data['error'] ?? 'no expiry',
            ]);
            $site->update(['ssl_valid' => false]);

            return;
        }

        $site->update([
            'ssl_expires_at' => Carbon::parse($data['expires_at'])->toDateString(),
            'ssl_issuer'     => (string) ($data['issuer'] ?? 'Unknown'),
            'ssl_valid'      => (bool) ($data['valid'] ?? true),
        ]);
    }

    private function refreshDomain(Site $site, string $host): void
    {
        $domain = $site->registrableDomain() ?? $host;
        $data   = $this->domains->lookup($domain);

        if (isset($data['error']) || ! isset($data['expires_at'])) {
            Log::info('SiteHealthService: domain lookup failed', [
                'site_id' => $site->id,
                'domain'  => $domain,
                'error'   => $data['error'] ?? 'no expiry',
                'source'  => $data['source'] ?? null,
            ]);

            return;
        }

        $site->update([
            'domain_expires_at'        => Carbon::parse($data['expires_at'])->toDateString(),
            'registrar'                => $data['registrar'] ?? null,
            'whoisxml_last_checked_at' => now(),
        ]);
    }

    private function refreshUptime(Site $site): void
    {
        if (! $site->url || $site->isMonitoringPaused()) {
            return;
        }

        if ($this->uptimeKuma->isConfigured()) {
            if (! $site->uptime_kuma_monitor_id) {
                $monitorId = $this->uptimeKuma->createMonitor($site->displayName(), $site->url);

                if ($monitorId) {
                    $site->update(['uptime_kuma_monitor_id' => $monitorId]);
                    $site->refresh();
                }
            }

            if ($site->uptime_kuma_monitor_id) {
                $uptime30 = $this->uptimeKuma->getUptimePercent((int) $site->uptime_kuma_monitor_id, 30);
                $uptime7  = $this->uptimeKuma->getUptimePercent((int) $site->uptime_kuma_monitor_id, 7);

                $updates = [];

                if ($uptime30 !== null) {
                    $updates['uptime_30d'] = $uptime30;
                }

                if ($uptime7 !== null) {
                    $updates['uptime_7d'] = $uptime7;
                }

                if ($updates !== []) {
                    $site->update($updates);

                    return;
                }
            }
        }

        $this->siteUptime->probe($site);
    }
}
