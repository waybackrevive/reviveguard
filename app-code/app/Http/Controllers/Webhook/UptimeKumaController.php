<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Models\SiteUptimeProbe;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Receives Uptime Kuma status-change webhooks and records them as platform events.
 *
 * Uptime Kuma sends a JSON payload with:
 *   heartbeat.status  — 1 = up, 0 = down
 *   heartbeat.msg     — human-readable status message
 *   monitor.name      — monitor display name
 *   monitor.url       — monitored URL
 *   monitor.id        — Uptime Kuma monitor ID
 */
class UptimeKumaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $monitorId = $payload['monitor']['id']    ?? null;
        $status    = $payload['heartbeat']['status'] ?? null;
        $message   = $payload['heartbeat']['msg']    ?? '';
        $monitorUrl = $payload['monitor']['url']     ?? '';

        if ($monitorId === null || $status === null) {
            Log::warning('UptimeKuma webhook: missing monitor.id or heartbeat.status', ['payload' => $payload]);
            return response()->json(['ok' => true]); // Accept but don't process malformed payloads
        }

        $site = Site::with('subscription')->where('uptime_kuma_monitor_id', (int) $monitorId)->first();

        if (! $site) {
            // Monitor exists in Kuma but not linked to a site — log and ignore
            Log::info('UptimeKuma webhook: no site found for monitor_id', ['monitor_id' => $monitorId]);
            return response()->json(['ok' => true]);
        }

        if (! $site || ! $site->hasPaidSubscription()) {
            Log::info('UptimeKuma webhook: ignored — site not found or not on active plan', ['monitor_id' => $monitorId]);

            return response()->json(['ok' => true]);
        }

        $isUp = (int) $status === 1;

        if (! $isUp && $this->builtinProbeShowsUp($site)) {
            Log::info('UptimeKuma webhook: ignored down signal — built-in probe shows site up', [
                'site_id' => $site->id,
            ]);

            return response()->json(['ok' => true]);
        }

        if ($isUp) {
            $this->handleSiteUp($site, $message);
        } else {
            $this->handleSiteDown($site, $message);
        }

        return response()->json(['ok' => true]);
    }

    private function handleSiteDown(Site $site, string $message): void
    {
        $label = $site->displayName();

        // Only update and create event if site isn't already marked down
        if ($site->status !== SiteStatus::DOWN) {
            $site->update(['status' => SiteStatus::DOWN]);

            Event::create([
                'tenant_id' => $site->tenant_id,
                'site_id'   => $site->id,
                'type'      => 'uptime_kuma_alert',
                'severity'  => EventSeverity::CRITICAL->value,
                'title'     => "Site offline: {$label}",
                'message'   => $message ?: 'Uptime Kuma detected the site is unreachable.',
                'metadata'  => ['source' => 'uptime_kuma', 'direction' => 'down'],
                'resolved'  => false,
            ]);

            Log::warning('UptimeKuma webhook: site down', ['site' => $label, 'site_id' => $site->id]);

            try {
                (new NotificationService())->sendSiteDown($site);
            } catch (\Throwable $e) {
                Log::error('UptimeKumaController: sendSiteDown email failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function handleSiteUp(Site $site, string $message): void
    {
        $label = $site->displayName();

        // Resolve any open uptime_kuma_alert events
        Event::where('site_id', $site->id)
            ->where('type', 'uptime_kuma_alert')
            ->where('resolved', false)
            ->update([
                'resolved'    => true,
                'resolved_at' => now(),
            ]);

        // Only log recovery if the site was previously down
        if ($site->status === SiteStatus::DOWN) {
            $site->update(['status' => SiteStatus::ACTIVE]);

            Event::create([
                'tenant_id' => $site->tenant_id,
                'site_id'   => $site->id,
                'type'      => 'uptime_kuma_alert',
                'severity'  => EventSeverity::SUCCESS->value,
                'title'     => "Site back online: {$label}",
                'message'   => $message ?: 'Uptime Kuma confirmed the site is responding again.',
                'metadata'  => ['source' => 'uptime_kuma', 'direction' => 'up'],
                'resolved'  => true,
                'resolved_at' => now(),
            ]);

            Log::info('UptimeKuma webhook: site recovered', ['site' => $label, 'site_id' => $site->id]);

            try {
                (new NotificationService())->sendSiteRecovered($site);
            } catch (\Throwable $e) {
                Log::error('UptimeKumaController: sendSiteRecovered email failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function builtinProbeShowsUp(Site $site): bool
    {
        return SiteUptimeProbe::where('site_id', $site->id)
            ->where('checked_at', '>=', now()->subMinutes(5))
            ->where('is_up', true)
            ->exists();
    }
}
