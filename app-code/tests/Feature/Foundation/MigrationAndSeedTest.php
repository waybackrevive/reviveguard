<?php

namespace Tests\Feature\Foundation;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationAndSeedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tenant_table_exists_and_seeder_creates_waybackrevive(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);

        $tenant = Tenant::find('00000000-0000-0000-0000-000000000001');

        $this->assertNotNull($tenant);
        $this->assertSame('WaybackRevive', $tenant->name);
        $this->assertSame('waybackrevive', $tenant->slug);
    }

    /** @test */
    public function plan_seeder_creates_three_plans(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $plans = Plan::all();

        $this->assertCount(3, $plans);
        $this->assertTrue($plans->pluck('slug')->contains('monitor'));
        $this->assertTrue($plans->pluck('slug')->contains('guard'));
        $this->assertTrue($plans->pluck('slug')->contains('shield'));
    }

    /** @test */
    public function monitor_plan_has_correct_features(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $monitor = Plan::where('slug', 'monitor')->first();

        $this->assertNotNull($monitor);
        $this->assertSame('monthly', $monitor->backup_frequency);
        $this->assertSame(30, $monitor->retention_days);
        $this->assertSame(0, $monitor->support_tickets_per_month);
        $this->assertSame(19.00, (float) $monitor->price_monthly);
    }

    /** @test */
    public function shield_plan_has_unlimited_tickets(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $shield = Plan::where('slug', 'shield')->first();

        $this->assertNotNull($shield);
        $this->assertSame(-1, $shield->support_tickets_per_month);
        $this->assertTrue((bool) $shield->features['priority_support']);
    }
}
