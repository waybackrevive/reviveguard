<?php

namespace App\Livewire\Portal;

use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * MyWebsites — shows all sites belonging to the logged-in client
 * and serves as the entry point for the site onboarding wizard.
 */
class MyWebsites extends Component
{
    public bool $showWizard = false;

    public function openWizard(): void
    {
        $this->showWizard = true;
    }

    public function closeWizard(): void
    {
        $this->showWizard = false;
    }

    /**
     * Called by the wizard Livewire component when onboarding completes.
     */
    #[\Livewire\Attributes\On('wizard-completed')]
    public function onWizardCompleted(): void
    {
        $this->showWizard = false;
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        $sites = Site::where('client_id', $client->id)
            ->orderBy('created_at')
            ->get();

        $hasSubscription = (bool) $client->activeSubscription;

        return view('livewire.portal.my-websites', compact('sites', 'client', 'hasSubscription'))
            ->layout('portal.layouts.app');
    }
}
