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
    /** @var \App\Models\Site|null */
    public ?Site $site = null;

    /** UUID of the active site — driven by ?site_id= query param */
    #[\Livewire\Attributes\Url(as: 'site_id')]
    public ?string $activeSiteId = null;

    public function mount(): void
    {
        $this->loadSite();
    }

    /**
     * Called by wire:poll every 60s — reload the site and recent events.
     */
    public function refresh(): void
    {
        $this->loadSite();
    }

    public function switchSite(string $siteId): void
    {
        $this->activeSiteId = $siteId;
        $this->loadSite();
    }

    private function loadSite(): void
    {
        $client = Auth::guard('client')->user();

        if ($this->activeSiteId) {
            // Ensure the site belongs to this client (security check)
            $this->site = Site::where('client_id', $client->id)
                ->where('id', $this->activeSiteId)
                ->first();
        }

        // Fall back to first site if nothing found or no ID given
        if (! $this->site) {
            $this->site = Site::where('client_id', $client->id)
                ->whereIn('status', ['active', 'down', 'warning'])
                ->first()
                ?? Site::where('client_id', $client->id)->first();
        }
    }

    public function recentEvents(): EloquentCollection
    {
        if (! $this->site) {
            return new EloquentCollection();
        }

        return Event::where('site_id', $this->site->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        $client     = Auth::guard('client')->user();
        $lastBackup = $this->site
            ? Backup::where('site_id', $this->site->id)
                ->orderByDesc('created_at')
                ->first()
            : null;

        $allSites = Site::where('client_id', $client->id)
            ->whereIn('status', ['active', 'down', 'warning', 'pending'])
            ->orderBy('name')
            ->get(['id', 'name', 'url', 'status']);

        return view('livewire.portal.dashboard', [
            'client'       => $client,
            'site'         => $this->site,
            'recentEvents' => $this->recentEvents(),
            'lastBackup'   => $lastBackup,
            'allSites'     => $allSites,
        ])->layout('portal.layouts.app');
    }
}
