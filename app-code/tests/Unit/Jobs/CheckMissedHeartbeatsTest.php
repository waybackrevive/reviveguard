<?php

namespace Tests\Unit\Jobs;

use App\Enums\SiteStatus;
use App\Jobs\CheckMissedHeartbeats;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Tenant;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckMissedHeartbeatsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Plan $plan;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'id'   => '00000000-0000-0000-0000-000000000001',
            'name' => 'WaybackRevive',
            'slug' => 'waybackrevive',
        ]);

        $this->plan = Plan::create([
            'tenant_id'     => $this->tenant->id,
            'name'          => 'Monitor',
            'slug'          => 'monitor',
            'price_monthly' => 19.00,
            'features'      => [],
        ]);

        $this->client = Client::create([
            'tenant_id'      => $this->tenant->id,
            'name'           => 'Test Client',
            'email'          => 'client@example.com',
            'portal_password' => bcrypt('secret'),
        ]);
    }

    private function makeSite(array $attrs = []): Site
    {
        return Site::create(array_merge([
            'tenant_id'         => $this->tenant->id,
            'client_id'         => $this->client->id,
            'plan_id'           => $this->plan->id,
            'name'              => 'Test Site',
            'url'               => 'https://test.example.com',
            'agent_token'       => hash('sha256', bin2hex(random_bytes(16))),
            'agent_token_last4' => 'abcd',
            'is_active'         => true,
            'status'            => SiteStatus::ACTIVE,
        ], $attrs));
    }

    public function test_flags_site_down_when_heartbeat_missed(): void
    {
        $site = $this->makeSite(['last_seen_at' => now()->subMinutes(20)]);

        $alertService = Mockery::mock(AlertService::class);
        $alertService->shouldReceive('siteDown')->once()->with(Mockery::on(
            fn ($s) => $s->id === $site->id
        ));

        $job = new CheckMissedHeartbeats();
        $job->handle($alertService);

        $site->refresh();
        $this->assertEquals(SiteStatus::DOWN, $site->status);
    }

    public function test_does_not_flag_site_with_recent_heartbeat(): void
    {
        $site = $this->makeSite(['last_seen_at' => now()->subMinutes(3)]);

        $alertService = Mockery::mock(AlertService::class);
        $alertService->shouldNotReceive('siteDown');

        $job = new CheckMissedHeartbeats();
        $job->handle($alertService);

        $site->refresh();
        $this->assertEquals(SiteStatus::ACTIVE, $site->status);
    }

    public function test_does_not_re_alert_already_down_site(): void
    {
        $site = $this->makeSite([
            'status'       => SiteStatus::DOWN,
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $alertService = Mockery::mock(AlertService::class);
        $alertService->shouldNotReceive('siteDown');

        $job = new CheckMissedHeartbeats();
        $job->handle($alertService);

        $site->refresh();
        $this->assertEquals(SiteStatus::DOWN, $site->status);
    }

    public function test_skips_inactive_sites(): void
    {
        $site = $this->makeSite([
            'is_active'    => false,
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $alertService = Mockery::mock(AlertService::class);
        $alertService->shouldNotReceive('siteDown');

        $job = new CheckMissedHeartbeats();
        $job->handle($alertService);

        $site->refresh();
        $this->assertNotEquals(SiteStatus::DOWN, $site->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
