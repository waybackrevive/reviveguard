<?php

namespace App\Livewire\Portal;

use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SiteShow extends Component
{
    public Site $site;

    public function mount(Site $site): void
    {
        $client = Auth::guard('client')->user();

        if ($site->client_id !== $client->id) {
            abort(404);
        }

        $this->site = $site->load(['plan', 'events' => fn ($q) => $q->latest()->limit(5)]);
    }

    public function render(): \Illuminate\View\View
    {
        $recentEvents = $this->site->events;
        $latestBackup = $this->site->latestBackup;

        return view('livewire.portal.site-show', [
            'site'         => $this->site,
            'recentEvents' => $recentEvents,
            'latestBackup' => $latestBackup,
        ])->layout('portal.layouts.app');
    }
}
