<?php

namespace App\Livewire\Portal;

use App\Models\Plan;
use App\Models\Site;
use App\Services\ClientActivityService;
use App\Services\StripeBillingService;
use App\Support\PlanCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Account extends Component
{
    public string $name     = '';
    public string $email    = '';
    public string $phone    = '';

    public string $currentPassword = '';
    public string $newPassword     = '';
    public string $confirmPassword = '';

    public bool $profileSaved  = false;
    public bool $passwordSaved = false;

    public string $activeTab = 'profile'; // 'profile' | 'plan' | 'billing'

    public function mount(): void
    {
        $client      = Auth::guard('client')->user();
        $this->name  = $client->name ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';

        if (in_array(request()->query('tab'), ['profile', 'plan', 'billing'], true)) {
            $this->activeTab = request()->query('tab');
        }

        if ($this->activeTab === 'billing' && $client->stripeCustomerId()) {
            try {
                app(StripeBillingService::class)->syncClientInvoicesFromStripe($client);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function saveProfile(): void
    {
        $client = Auth::guard('client')->user();

        $validated = $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('clients', 'email')->ignore($client->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $client->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $this->profileSaved = true;
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword'     => ['required', 'min:8', 'same:confirmPassword'],
            'confirmPassword' => ['required'],
        ]);

        $client = Auth::guard('client')->user();

        if (! Hash::check($this->currentPassword, $client->portal_password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $client->update([
            'portal_password' => Hash::make($this->newPassword),
        ]);

        $this->reset('currentPassword', 'newPassword', 'confirmPassword');
        $this->passwordSaved = true;
    }

    public function openBillingPortal(StripeBillingService $billing)
    {
        $client = Auth::guard('client')->user();

        try {
            $url = $billing->createBillingPortalSession($client);
        } catch (\Throwable $e) {
            $this->addError('billing', $e->getMessage());
            return;
        }

        return redirect()->away($url);
    }

    public function goToSitePlan(string $siteId)
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)->where('client_id', $client->id)->first();

        if (! $site) {
            session()->flash('error', 'Site not found.');

            return;
        }

        return redirect()->route('portal.sites.show', ['site' => $site, 'tab' => 'plan']);
    }

    public function changeSitePlan(string $siteId, string $planSlug, StripeBillingService $billing, ClientActivityService $activity): void
    {
        $client = Auth::guard('client')->user();

        $site = Site::where('id', $siteId)
            ->where('client_id', $client->id)
            ->with(['plan', 'subscription'])
            ->first();

        if (! $site) {
            $this->addError('planChange', 'Site not found.');

            return;
        }

        $newPlan = Plan::where('slug', $planSlug)->where('is_active', true)->first();

        if (! $newPlan) {
            $this->addError('planChange', 'Plan not found.');

            return;
        }

        if (! PlanCatalog::canChangePlan($site->plan, $newPlan)) {
            $this->addError('planChange', 'Please select a different plan.');

            return;
        }

        $fromSlug   = $site->plan?->slug;
        $isUpgrade  = PlanCatalog::isUpgrade($site->plan, $newPlan);

        try {
            $result = $billing->changeSitePlan($client, $site, $newPlan);
        } catch (\Throwable $e) {
            $this->addError('planChange', $e->getMessage());
            report($e);

            return;
        }

        $activity->log(
            $client,
            $isUpgrade ? 'plan_upgraded' : 'plan_downgraded',
            $isUpgrade ? "Plan upgraded to {$newPlan->name}" : "Plan changed to {$newPlan->name}",
            $site->displayName() . ' — changes are active now.',
            $site->fresh(),
            ['from' => $fromSlug, 'to' => $newPlan->slug],
        );

        session()->flash('success', $result->successMessage($newPlan->name));
        $this->activeTab = $isUpgrade ? 'plan' : 'billing';
    }

    /** @deprecated Use changeSitePlan() */
    public function upgradeSitePlan(string $siteId, string $planSlug, StripeBillingService $billing, ClientActivityService $activity): void
    {
        $this->changeSitePlan($siteId, $planSlug, $billing, $activity);
    }

    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();
        $sub      = $client->activeSubscription;
        $plan     = optional($sub)->plan;
        $invoices = $client->invoices()->orderByDesc('issued_at')->limit(24)->get();
        $siteSubscriptions = $client->subscriptions()
            ->with(['plan', 'site'])
            ->orderByDesc('created_at')
            ->get();

        $plans          = PlanCatalog::all();
        $comparisonRows = PlanCatalog::comparisonRows();
        $sites          = Site::where('client_id', $client->id)->with('plan')->orderBy('name')->get();

        return view('livewire.portal.account', [
            'client'            => $client,
            'sub'               => $sub,
            'plan'              => $plan,
            'invoices'          => $invoices,
            'siteSubscriptions' => $siteSubscriptions,
            'plans'             => $plans,
            'comparisonRows'    => $comparisonRows,
            'sites'             => $sites,
        ])->layout('portal.layouts.app');
    }
}
