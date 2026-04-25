<?php

namespace App\Livewire\Portal;

use App\Models\Site;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
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
        $first  = Site::where('client_id', $client->id)->first();
        $this->siteId = optional($first)->id;
    }

    public function submitTicket(): void
    {
        $client = Auth::guard('client')->user();

        // Plan limit enforcement
        $plan = optional($client->activeSubscription)->plan;
        if (optional($plan)->slug === 'monitor') {
            session()->flash('ticket_error', 'Support tickets are available on Guard and Shield plans.');
            return;
        }

        // Guard plan: 1 ticket/month limit
        if (optional($plan)->slug === 'guard') {
            $usedThisMonth = Ticket::where('client_id', $client->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($usedThisMonth >= 1) {
                session()->flash('ticket_error', 'You have used your 1 support ticket for this month.');
                return;
            }
        }

        $validated = $this->validate();

        Ticket::create([
            'tenant_id' => $client->tenant_id,
            'client_id' => $client->id,
            'site_id'   => $validated['siteId'],
            'subject'   => $validated['subject'],
            'message'   => $validated['message'],
            'status'    => 'open',
            'priority'  => 'medium',
        ]);

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
        $this->selectedTicket = Ticket::where('id', $id)
            ->where('client_id', $client->id)
            ->first();
    }

    public function closeModal(): void
    {
        $this->selectedTicket = null;
    }

    public function render(): \Illuminate\View\View
    {
        $client  = Auth::guard('client')->user();
        $tickets = Ticket::where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        $sites = Site::where('client_id', $client->id)->get();

        $plan = optional($client->activeSubscription)->plan;

        return view('livewire.portal.tickets', [
            'tickets' => $tickets,
            'sites'   => $sites,
            'plan'    => $plan,
        ])->layout('portal.layouts.app');
    }
}
