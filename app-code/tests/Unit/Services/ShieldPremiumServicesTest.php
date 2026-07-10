<?php

namespace Tests\Unit\Services;

use App\Enums\TicketType;
use App\Models\Plan;
use App\Models\Ticket;
use App\Services\ContentHoursService;
use App\Services\TicketSlaService;
use Tests\TestCase;

class ShieldPremiumServicesTest extends TestCase
{
    /** @test */
    public function content_hours_allowance_is_120_for_shield(): void
    {
        $plan = new Plan([
            'slug'     => 'shield',
            'features' => ['content_edit_minutes_monthly' => 120],
        ]);

        $this->assertSame(120, app(ContentHoursService::class)->monthlyAllowance($plan));
    }

    /** @test */
    public function content_hours_allowance_is_zero_for_guard(): void
    {
        $plan = new Plan(['slug' => 'guard', 'features' => []]);

        $this->assertSame(0, app(ContentHoursService::class)->monthlyAllowance($plan));
    }

    /** @test */
    public function emergency_sla_hours_for_shield_is_four(): void
    {
        $plan = new Plan([
            'slug'     => 'shield',
            'features' => ['emergency_restore_sla_hours' => 4],
        ]);

        $this->assertSame(4, app(TicketSlaService::class)->emergencySlaHours($plan));
    }

    /** @test */
    public function ticket_type_enum_has_emergency_restore(): void
    {
        $this->assertSame('emergency_restore', TicketType::EMERGENCY_RESTORE->value);
    }

    /** @test */
    public function sla_at_risk_when_due_within_one_hour(): void
    {
        $sla = app(TicketSlaService::class);

        $ticket = new Ticket([
            'status'     => 'open',
            'sla_due_at' => now()->addMinutes(30),
        ]);

        $this->assertTrue($sla->isAtRisk($ticket));
        $this->assertFalse($sla->isBreached($ticket));
    }

    /** @test */
    public function sla_breached_when_past_due(): void
    {
        $sla = app(TicketSlaService::class);

        $ticket = new Ticket([
            'status'     => 'open',
            'sla_due_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($sla->isBreached($ticket));
    }
}
