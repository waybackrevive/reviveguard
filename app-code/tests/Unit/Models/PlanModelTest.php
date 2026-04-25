<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use PHPUnit\Framework\TestCase;

class PlanModelTest extends TestCase
{
    public function test_backup_frequency_accessor(): void
    {
        $plan = new Plan();
        $plan->setRawAttributes(['features' => json_encode(['backup_frequency' => 'daily'])]);

        $this->assertSame('daily', $plan->backup_frequency);
    }

    public function test_retention_days_accessor(): void
    {
        $plan = new Plan();
        $plan->setRawAttributes(['features' => json_encode(['backup_retention_days' => 90])]);

        $this->assertSame(90, $plan->retention_days);
    }

    public function test_support_tickets_per_month_accessor(): void
    {
        $plan = new Plan();
        $plan->setRawAttributes(['features' => json_encode(['support_tickets_per_month' => -1])]);

        $this->assertSame(-1, $plan->support_tickets_per_month);
    }

    public function test_plan_uses_string_key(): void
    {
        $plan = new Plan();
        $this->assertSame('string', $plan->getKeyType());
        $this->assertFalse($plan->getIncrementing());
    }
}
