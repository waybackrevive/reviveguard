<?php

namespace App\Livewire\Portal;

use Livewire\Component;

class AddSite extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.add-site')
            ->layout('portal.layouts.app');
    }
}
