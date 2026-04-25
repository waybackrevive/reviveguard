<?php

namespace App\Livewire\Portal;

use App\Models\Backup;
use App\Models\Event;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /** @var \App\Models\Site|null */
    public ?Site $site = null;

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

    private function loadSite(): void
    {
        $client = Auth::guard('client')->user();

        // Phase 1: clients have one site; take first active or any
        $this->site = Site::where('client_id', $client->id)->first();
    }

    public function recentEvents(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->site) {
            return collect();
        }

        return Event::where('site_id', $this->site->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        $lastBackup = $this->site
            ? Backup::where('site_id', $this->site->id)
                ->orderByDesc('created_at')
                ->first()
            : null;

        return view('livewire.portal.dashboard', [
            'client'       => Auth::guard('client')->user(),
            'site'         => $this->site,
            'recentEvents' => $this->recentEvents(),
            'lastBackup'   => $lastBackup,
        ])->layout('portal.layouts.app');
    }
}
