<?php

namespace App\Livewire\Portal;

use App\Models\Site;
use App\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Tickets extends Component
{
    public string  $subject = '';
    public string  $message = '';
    public ?string $siteId  = null;

    // Ticket detail modal
    public ?Ticket $selectedTicket = null;

    public bool $submitted = false;

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

    public function submitTicket(): void
    {
        $client = Auth::guard('client')->user();

        if (!$client) {
            session()->flash('ticket_error', 'Your session expired. Please sign in again.');
            return;
        }

        if (!Schema::hasTable('tickets')) {
            session()->flash('ticket_error', 'Support tickets are not available yet. Please contact your administrator.');
            return;
        }

        // Plan limit enforcement
        $plan = optional($client->activeSubscription)->plan;
        if (optional($plan)->slug === 'monitor') {
            session()->flash('ticket_error', 'Support tickets are available on Guard and Shield plans.');
            return;
        }

        // Guard plan: 1 ticket/month limit
        if (optional($plan)->slug === 'guard') {
            try {
                $usedThisMonth = Ticket::where('client_id', $client->id)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count();
            } catch (QueryException $e) {
                Log::warning('Portal tickets monthly usage query failed', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);
                session()->flash('ticket_error', 'Support is temporarily unavailable. Please try again shortly.');
                return;
            }

            if ($usedThisMonth >= 1) {
                session()->flash('ticket_error', 'You have used your 1 support ticket for this month.');
                return;
            }
        }

        $validated = $this->validate();

        try {
            Ticket::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'site_id'   => $validated['siteId'],
                'subject'   => $validated['subject'],
                'message'   => $validated['message'],
                'status'    => 'open',
                'priority'  => 'medium',
            ]);
        } catch (QueryException $e) {
            Log::warning('Portal ticket create failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('ticket_error', 'Could not submit ticket right now. Please try again shortly.');
            return;
        }

        $this->reset('subject', 'message');
        $this->submitted = true;
    }

    public function dismissSuccess(): void
    {
        $this->submitted = false;
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

        $plan = optional($client->activeSubscription)->plan;

        return view('livewire.portal.tickets', [
            'tickets' => $tickets,
            'sites'   => $sites,
            'plan'    => $plan,
        ])->layout('portal.layouts.app');
    }
}
