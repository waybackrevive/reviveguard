<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOpsTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $client = \App\Models\Client::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'tenant_id'       => config('app.tenant_id'),
            'name'            => 'Event Client',
            'email'           => 'event@example.com',
            'portal_password' => bcrypt('password'),
            'is_active'       => true,
        ]);

        $this->site = \App\Models\Site::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $client->id,
            'plan_id'   => \App\Models\Plan::first()->id,
            'name'      => 'event-site',
            'url'       => 'https://event-site.test',
            'type'      => 'wordpress',
            'status'    => 'active',
        ]);
    }

    public function test_client_action_is_client_initiated(): void
    {
        $event = Event::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'site_id'   => $this->site->id,
            'type'      => 'client_action',
            'severity'  => 'info',
            'title'     => 'Plan changed',
            'message'   => 'Upgraded to Guard',
            'resolved'  => false,
        ]);

        $this->assertTrue($event->isClientInitiated());
        $this->assertSame('Client', $event->sourceLabel());
    }

    public function test_uptime_probe_is_system_sourced(): void
    {
        $event = Event::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'site_id'   => $this->site->id,
            'type'      => 'uptime_probe',
            'severity'  => 'critical',
            'title'     => 'Site down',
            'resolved'  => false,
        ]);

        $this->assertFalse($event->isClientInitiated());
        $this->assertSame('System', $event->sourceLabel());
        $this->assertSame('Uptime probe', $event->typeLabel());
    }
}
