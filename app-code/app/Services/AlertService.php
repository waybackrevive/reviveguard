<?php

namespace App\Services;

use App\Enums\EventSeverity;
use App\Models\Event;
use App\Models\Site;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Called when a site stops sending heartbeats (status → DOWN).
     */
    public function siteDown(Site $site): void
    {
        // Create an event record visible in the admin panel + portal
        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'heartbeat_missed',
            'severity'  => EventSeverity::CRITICAL,
            'title'     => "Site down: {$site->name}",
            'message'   => "No heartbeat received for more than 15 minutes. Last seen: "
                . ($site->last_seen_at?->toDateTimeString() ?? 'never'),
            'metadata'  => ['last_seen_at' => $site->last_seen_at?->toIso8601String()],
        ]);

        // Send email notification via Resend
        try {
            (new NotificationService())->sendSiteDown($site);
        } catch (\Throwable $e) {
            Log::error('AlertService: sendSiteDown notification failed', ['error' => $e->getMessage()]);
        }

        Log::warning("Site down alert fired", ['site_id' => $site->id, 'site' => $site->name]);
    }

    /**
     * Called when a previously down/warning site sends a heartbeat.
     */
    public function siteRecovered(Site $site): void
    {
        // Resolve all open heartbeat_missed events for this site
        Event::where('site_id', $site->id)
            ->where('type', 'heartbeat_missed')
            ->where('resolved', false)
            ->update([
                'resolved'    => true,
                'resolved_at' => now(),
            ]);

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => 'site_recovered',
            'severity'  => EventSeverity::SUCCESS,
            'title'     => "Site recovered: {$site->name}",
            'message'   => "Heartbeat resumed at " . now()->toDateTimeString(),
            'metadata'  => [],
        ]);

        // Send email notification via Resend
        try {
            (new NotificationService())->sendSiteRecovered($site);
        } catch (\Throwable $e) {
            Log::error('AlertService: sendSiteRecovered notification failed', ['error' => $e->getMessage()]);
        }

        Log::info("Site recovered alert fired", ['site_id' => $site->id, 'site' => $site->name]);
    }
}
