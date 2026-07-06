<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Addons extends Component
{
    public ?string $requestedSlug = null;

    public function requestAddon(string $slug): void
    {
        $addon = collect(config('reviveguard_addons', []))->firstWhere('slug', $slug);

        if (! $addon) {
            session()->flash('error', 'Add-on not found.');

            return;
        }

        $this->requestedSlug = $slug;
        session()->flash('addon_request', $addon);
        $this->redirect(route('portal.tickets', ['addon' => $slug]));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.addons', [
            'addons' => config('reviveguard_addons', []),
            'client' => Auth::guard('client')->user(),
        ])->layout('portal.layouts.app');
    }
}
