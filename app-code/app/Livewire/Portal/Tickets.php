<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\DispatchesPortalToast;
use App\Models\Site;
use App\Models\Ticket;
use App\Services\ClientActivityService;
use App\Support\SupportTier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Tickets extends Component
{
    use DispatchesPortalToast;

    public string  $subject = '';
    public string  $message = '';
    public ?string $siteId  = null;

    public ?Ticket $selectedTicket = null;

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'message' => ['required', 'string', 'min:20'],
            'siteId'  => ['nullable', 'exists:sites,id'],
        ];
    }

    public function mount(): void
    {
        $client = Auth::guard('client')->user();

        if (!$client || !Schema::hasTable('sites')) {
            return;
        }

        try {
            $first = Site::where('client_id', $client->id)->first();
            $this->siteId = optional($first)->id;
        } catch (QueryException $e) {
            Log::warning('Portal tickets mount failed to load sites', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }

        $addonSlug = request()->query('addon');

        if ($addonSlug) {
            $addon = collect(config('reviveguard_addons', []))->firstWhere('slug', $addonSlug);

            if ($addon) {
                $this->subject = 'Add-on request: ' . $addon['name'];
                $this->message = "I'd like to order the {$addon['name']} add-on ({$addon['price_label']}).\n\n"
                    . $addon['description'] . "\n\nPlease apply this to site: ";
            }
        }
    }

    public function submitTicket(ClientActivityService $activity): void
    {
        $client = Auth::guard('client')->user();

        if (!$client) {
            $this->toastError('Your session expired. Please sign in again.');
            return;
        }

        if (!Schema::hasTable('tickets')) {
            $this->toastError('Support tickets are not available yet. Please contact your administrator.');
            return;
        }

        $plan = $client->bestSupportPlan();

        if (! SupportTier::canSubmitTicket($plan)) {
            $this->toastError('Email support is not available on your current plan.');
            return;
        }

        try {
            $usedThisMonth = Ticket::where('client_id', $client->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
        } catch (QueryException $e) {
            Log::warning('Portal tickets monthly usage query failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            $this->toastError('Support is temporarily unavailable. Please try again shortly.');
            return;
        }

        if (SupportTier::ticketLimitReached($plan, $usedThisMonth)) {
            $this->toastError('You have reached your support ticket limit for this month.');
            return;
        }

        $validated = $this->validate();

        $site = $validated['siteId']
            ? Site::where('id', $validated['siteId'])->where('client_id', $client->id)->first()
            : null;

        try {
            Ticket::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'site_id'   => $validated['siteId'],
                'subject'   => $validated['subject'],
                'message'   => $validated['message'],
                'status'    => 'open',
                'priority'  => optional($plan)->slug === 'shield' ? 'high' : 'medium',
            ]);
        } catch (QueryException $e) {
            Log::warning('Portal ticket create failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            $this->toastError('Could not submit ticket right now. Please try again shortly.');
            return;
        }

        $activity->log(
            $client,
            'support_ticket_submitted',
            'Support ticket submitted',
            $validated['subject'],
            $site,
            ['priority' => optional($plan)->slug === 'shield' ? 'high' : 'medium'],
        );

        $this->reset('subject', 'message');
        $this->toastSuccess('Ticket submitted. ' . SupportTier::forPlan($plan)['reply_sla'] . '.');
    }

    public function showTicket(string $id): void
    {
        $client = Auth::guard('client')->user();

        if (!$client || !Schema::hasTable('tickets')) {
            $this->selectedTicket = null;
            return;
        }

        try {
            $this->selectedTicket = Ticket::where('id', $id)
                ->where('client_id', $client->id)
                ->first();
        } catch (QueryException $e) {
            Log::warning('Portal ticket detail query failed', [
                'client_id' => $client->id,
                'ticket_id' => $id,
                'error' => $e->getMessage(),
            ]);
            $this->selectedTicket = null;
        }
    }

    public function closeModal(): void
    {
        $this->selectedTicket = null;
    }

    public function render(): \Illuminate\View\View
    {
        $client  = Auth::guard('client')->user();

        if (!$client) {
            return view('livewire.portal.tickets', [
                'tickets' => collect(),
                'sites' => collect(),
                'plan' => null,
                'supportTier' => SupportTier::forPlan(null),
            ])->layout('portal.layouts.app');
        }

        $tickets = collect();
        $sites = collect();

        if (Schema::hasTable('tickets')) {
            try {
                $tickets = Ticket::where('client_id', $client->id)
                    ->orderByDesc('created_at')
                    ->get();
            } catch (QueryException $e) {
                Log::warning('Portal tickets list query failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (Schema::hasTable('sites')) {
            try {
                $sites = Site::where('client_id', $client->id)->get();
            } catch (QueryException $e) {
                Log::warning('Portal tickets site query failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $plan = $client->bestSupportPlan();

        return view('livewire.portal.tickets', [
            'tickets'     => $tickets,
            'sites'       => $sites,
            'plan'        => $plan,
            'supportTier' => SupportTier::forPlan($plan),
        ])->layout('portal.layouts.app');
    }
}
