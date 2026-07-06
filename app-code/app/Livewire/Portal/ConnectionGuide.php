<?php

namespace App\Livewire\Portal;

use Livewire\Component;

class ConnectionGuide extends Component
{
    public ?string $siteId = null;

    public string $connectionToken = '';

    public bool $compact = false;

    public function render(): \Illuminate\View\View
    {
        $site = $this->siteId ? \App\Models\Site::find($this->siteId) : null;
        $apiUrl = rtrim(config('services.reviveguard.api_url', config('app.url')), '/');
        $pluginUrl = config('services.reviveguard.plugin_download_url') ?: route('portal.plugin.download');

        return view('livewire.portal.connection-guide', [
            'site'            => $site,
            'apiUrl'          => $apiUrl,
            'pluginUrl'       => $pluginUrl,
            'connectionToken' => $this->connectionToken,
            'isConnected'     => $site?->hasAgentConnected() ?? false,
        ]);
    }
}
