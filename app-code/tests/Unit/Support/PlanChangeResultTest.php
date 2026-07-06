<?php

namespace Tests\Unit\Support;

use App\Support\PlanChangeResult;
use App\Models\Subscription;
use Carbon\Carbon;
use Tests\TestCase;

class PlanChangeResultTest extends TestCase
{
    /** @test */
    public function success_message_includes_charge_and_next_billing(): void
    {
        $subscription = new Subscription;
        $result = new PlanChangeResult(
            subscription: $subscription,
            isUpgrade: true,
            chargedCents: 12905,
            nextBillingAt: Carbon::parse('2026-08-06'),
            stripeInvoiceId: 'in_test',
        );

        $msg = $result->successMessage('Shield');

        $this->assertStringContainsString('Shield', $msg);
        $this->assertStringContainsString('$129.05', $msg);
        $this->assertStringContainsString('Aug 6, 2026', $msg);
        $this->assertStringContainsString('Billing', $msg);
    }

    /** @test */
    public function downgrade_message_mentions_credit_not_charge(): void
    {
        $result = new PlanChangeResult(
            subscription: new Subscription,
            isUpgrade: false,
            chargedCents: null,
            nextBillingAt: null,
        );

        $msg = $result->successMessage('Guard');

        $this->assertStringContainsString('credit', strtolower($msg));
        $this->assertStringNotContainsString('charged', strtolower($msg));
    }
}
