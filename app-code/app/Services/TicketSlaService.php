<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Ticket;
use App\Support\PlanFeatures;
use Illuminate\Support\Carbon;

final class TicketSlaService
{
    public function emergencySlaHours(?Plan $plan): ?int
    {
        if (! $plan || PlanFeatures::for($plan)->slug() !== 'shield') {
            return null;
        }

        $hours = $plan->features['emergency_restore_sla_hours'] ?? null;

        return $hours !== null ? (int) $hours : null;
    }

    public function applyOnCreate(Ticket $ticket, Client $client, TicketType $type): void
    {
        if ($type !== TicketType::EMERGENCY_RESTORE) {
            return;
        }

        $plan  = $client->bestSupportPlan();
        $hours = $this->emergencySlaHours($plan);

        if ($hours === null) {
            return;
        }

        $ticket->update([
            'type'       => $type->value,
            'sla_due_at' => now()->addHours($hours),
            'priority'   => 'urgent',
        ]);
    }

    public function isAtRisk(Ticket $ticket, ?Carbon $now = null): bool
    {
        $now ??= now();

        if (! $ticket->isOpen() || $ticket->sla_due_at === null) {
            return false;
        }

        return $ticket->sla_due_at->lessThanOrEqualTo($now->copy()->addHour());
    }

    public function isBreached(Ticket $ticket, ?Carbon $now = null): bool
    {
        $now ??= now();

        return $ticket->isOpen()
            && $ticket->sla_due_at !== null
            && $ticket->sla_due_at->lessThan($now);
    }

    public function slaLabel(Ticket $ticket): ?string
    {
        if ($ticket->sla_due_at === null) {
            return null;
        }

        if (! $ticket->isOpen()) {
            return 'SLA closed';
        }

        if ($this->isBreached($ticket)) {
            return 'SLA breached · due '.$ticket->sla_due_at->diffForHumans();
        }

        return 'SLA due '.$ticket->sla_due_at->diffForHumans();
    }
}
