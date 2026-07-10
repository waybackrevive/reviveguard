<?php

namespace App\Livewire\Portal;

use App\Models\Backup;
use App\Models\Event;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * 'overview' — show all-sites summary grid (multi-site clients)
     * 'detail'   — show single site full dashboard
     */
    public string $view = 'overview';

    /** UUID of the active site (detail view) */
    #[\Livewire\Attributes\Url(as: 'site_id')]
    public ?string $activeSiteId = null;

    public function mount(): void
    {
        $client    = Auth::guard('client')->user();
        $siteCount = Site::where('client_id', $client->id)->count();

        if ($this->activeSiteId) {
            $this->view = 'detail';
        } elseif ($siteCount <= 1) {
            // Single-site clients go straight to detail
            $this->view        = 'detail';
            $this->activeSiteId = Site::where('client_id', $client->id)->value('id');
        } else {
            $this->view = 'overview';
        }
    }

    public function refresh(): void
    {
        // Livewire re-renders on wire:poll — no extra state needed
    }

    /** Switch to full detail view for a specific site */
    public function viewSite(string $siteId): void
    {
        $client = Auth::guard('client')->user();
        // Security: ensure the site belongs to this client
        $exists = Site::where('client_id', $client->id)->where('id', $siteId)->exists();
        if ($exists) {
            $this->activeSiteId = $siteId;
            $this->view         = 'detail';
        }
    }

    /** Go back to the overview grid */
    public function backToOverview(): void
    {
        $this->activeSiteId = null;
        $this->view         = 'overview';
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        // ── All sites (used by both overview cards and site switcher) ────────
        $allSites = Site::where('client_id', $client->id)
            ->orderBy('name')
            ->get();

        // ── Detail view data ─────────────────────────────────────────────────
        $site         = null;
        $lastBackup   = null;
        $recentEvents = new EloquentCollection();

        if ($this->view === 'detail' && $this->activeSiteId) {
            $site = $allSites->firstWhere('id', $this->activeSiteId);

            if ($site) {
                $lastBackup = Backup::where('site_id', $site->id)
                    ->orderByDesc('created_at')
                    ->first();

                $recentEvents = Event::where('site_id', $site->id)
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get();
            }
        }

        // ── Overview summary counters ────────────────────────────────────────
        $summaryDown       = $allSites->where('status.value', 'down')->count();
        $summarySslSoon    = $allSites->filter(fn ($s) =>
            $s->ssl_expires_at && now()->diffInDays($s->ssl_expires_at, false) <= 30
        )->count();
        $summaryNoBackup   = 0; // computed per-site in blade (keep render lean)

        return view('livewire.portal.dashboard', [
            'client'           => $client,
            'allSites'         => $allSites,
            'site'             => $site,
            'lastBackup'       => $lastBackup,
            'recentEvents'     => $recentEvents,
            'summaryDown'      => $summaryDown,
            'summarySslSoon'   => $summarySslSoon,
            'restoreReadiness' => $site?->restoreReadiness(),
        ])->layout('portal.layouts.app');
    }
}
