<?php

namespace Tests\Feature\AgentApi;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\SiteCommand;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeartbeatTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;
    private string $rawToken;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'id'   => '00000000-0000-0000-0000-000000000001',
            'name' => 'WaybackRevive',
            'slug' => 'waybackrevive',
        ]);

        $plan = Plan::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Monitor',
            'slug'          => 'monitor',
            'price_monthly' => 19.00,
            'features'      => ['backup_retention_days' => 30, 'daily_backups' => false, 'support_tickets' => 0],
        ]);

        $client = Client::create([
            'tenant_id'      => $tenant->id,
            'name'           => 'Test Client',
            'email'          => 'client@example.com',
            'portal_password' => bcrypt('secret'),
        ]);

        $this->rawToken = bin2hex(random_bytes(32));

        $this->site = Site::create([
            'tenant_id'         => $tenant->id,
            'client_id'         => $client->id,
            'plan_id'           => $plan->id,
            'name'              => 'Test Site',
            'url'               => 'https://testsite.example.com',
            'agent_token'       => hash('sha256', $this->rawToken),
            'agent_token_last4' => substr($this->rawToken, -4),
            'is_active'         => true,
            'status'            => SiteStatus::PENDING,
        ]);
    }

    public function test_heartbeat_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/agent/heartbeat');
        $response->assertStatus(401);
    }

    public function test_heartbeat_rejects_invalid_token(): void
    {
        $response = $this->withToken('invalid-token-xyz')
            ->postJson('/api/v1/agent/heartbeat');

        $response->assertStatus(401);
    }

    public function test_heartbeat_updates_site_and_returns_ok(): void
    {
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/heartbeat', [
                'wp_version'    => '6.5.0',
                'php_version'   => '8.2.0',
                'agent_version' => '1.0.0',
                'disk_usage_mb' => 2048,
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $this->site->refresh();
        $this->assertEquals(SiteStatus::ACTIVE, $this->site->status);
        $this->assertEquals('6.5.0', $this->site->wp_version);
        $this->assertNotNull($this->site->last_seen_at);
    }

    public function test_heartbeat_returns_pending_command(): void
    {
        SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::PENDING,
            'params'    => [],
            'queued_at' => now(),
        ]);

        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/heartbeat', []);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'command', 'params', 'command_id']);

        $response->assertJsonFragment(['command' => 'run_backup']);
    }

    public function test_heartbeat_marks_command_as_sent(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::PENDING,
            'params'    => [],
            'queued_at' => now(),
        ]);

        $this->withToken($this->rawToken)->postJson('/api/v1/agent/heartbeat', []);

        $command->refresh();
        $this->assertEquals(CommandStatus::SENT, $command->status);
        $this->assertNotNull($command->sent_at);
    }
}
