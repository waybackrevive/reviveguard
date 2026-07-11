<?php

namespace App\Livewire\Portal;

use App\Models\Event;
use App\Models\Site;
use App\Support\PlanFeatures;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Security extends Component
{
    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        if (! $client) {
            return view('livewire.portal.security', [
                'rows'            => collect(),
                'events'          => collect(),
                'hasSecurityPlan' => false,
            ])->layout('portal.layouts.app');
        }

        $sites = Site::query()
            ->where('client_id', $client->id)
            ->with('plan')
            ->orderBy('name')
            ->get();

        $securitySiteIds = [];
        $rows            = [];

        foreach ($sites as $site) {
            $features = PlanFeatures::forSite($site);
            $show     = $features->canMalwareScan() || $features->canBrokenLinkAudit();

            if (! $show) {
                continue;
            }

            $securitySiteIds[] = $site->id;

            $lastMalware = $site->events()
                ->whereIn('type', ['malware_scan_complete', 'malware_scan_alert', 'malware_scan_failed'])
                ->latest()
                ->first();

            $lastLinks = $site->events()
                ->whereIn('type', ['broken_link_audit_complete', 'broken_link_audit_failed'])
                ->latest()
                ->first();

            $rows[] = [
                'site'         => $site,
                'malware'      => $lastMalware,
                'links'        => $lastLinks,
                'canMalware'   => $features->canMalwareScan(),
                'canLinks'     => $features->canBrokenLinkAudit(),
            ];
        }

        $events = $securitySiteIds === []
            ? collect()
            : Event::query()
                ->whereIn('site_id', $securitySiteIds)
                ->whereIn('type', [
                    'malware_scan_complete',
                    'malware_scan_alert',
                    'malware_scan_failed',
                    'broken_link_audit_complete',
                    'broken_link_audit_failed',
                    'quarterly_security_audit',
                ])
                ->with('site')
                ->orderByDesc('created_at')
                ->limit(40)
                ->get();

        return view('livewire.portal.security', [
            'rows'            => collect($rows),
            'events'          => $events,
            'hasSecurityPlan' => $rows !== [],
        ])->layout('portal.layouts.app');
    }
}
