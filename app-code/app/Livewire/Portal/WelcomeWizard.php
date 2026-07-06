<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WelcomeWizard extends Component
{
    public string $firstName = '';

    public string $workspaceName = '';

    public string $accountType = 'solo';

    public string $sitesManagedRange = '1-5';

    public function mount(): void
    {
        $client = Auth::guard('client')->user();

        if ($client->hasCompletedOnboarding()) {
            $this->redirect(route('portal.sites'), navigate: true);

            return;
        }

        $this->firstName     = $client->name ?? '';
        $this->workspaceName = $client->workspace_name
            ?? $client->company_name
            ?? ($client->name ? "{$client->name}'s sites" : 'My workspace');
    }

    public function complete(): void
    {
        $this->validate([
            'firstName'           => ['required', 'string', 'max:120'],
            'workspaceName'       => ['required', 'string', 'max:150'],
            'accountType'         => ['required', 'in:solo,freelance,agency'],
            'sitesManagedRange'   => ['required', 'string', 'max:30'],
        ]);

        $client = Auth::guard('client')->user();

        $client->update([
            'name'                    => $this->firstName,
            'workspace_name'          => $this->workspaceName,
            'company_name'            => $client->company_name ?: $this->workspaceName,
            'account_type'            => $this->accountType,
            'sites_managed_range'     => $this->sitesManagedRange,
            'onboarding_completed_at' => now(),
        ]);

        $this->redirect(route('portal.sites', ['open' => 1]), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.welcome-wizard')
            ->layout('portal.layouts.onboarding');
    }
}
