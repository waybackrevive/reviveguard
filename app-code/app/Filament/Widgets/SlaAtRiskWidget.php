<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketSlaService;
use Filament\Widgets\Widget;

class SlaAtRiskWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.sla-at-risk';

    public function getTickets(): array
    {
        $sla = app(TicketSlaService::class);

        return Ticket::query()
            ->where('tenant_id', config('app.tenant_id'))
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('sla_due_at')
            ->orderBy('sla_due_at')
            ->with(['client', 'site'])
            ->get()
            ->filter(fn (Ticket $ticket) => $sla->isAtRisk($ticket))
            ->map(fn (Ticket $ticket) => [
                'id'        => $ticket->id,
                'subject'   => $ticket->subject,
                'client'    => $ticket->client?->name ?? 'Client',
                'site'      => $ticket->site?->displayName(),
                'sla_label' => $sla->slaLabel($ticket),
                'breached'  => $sla->isBreached($ticket),
                'url'       => TicketResource::getUrl('edit', ['record' => $ticket->id]),
            ])
            ->values()
            ->all();
    }
}
