<?php

namespace Tests\Feature\AgentApi;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\PluginSnapshot;
use App\Models\Site;
use App\Models\SiteCommand;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentEndpointsTest extends TestCase
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
            'status'            => SiteStatus::ACTIVE,
        ]);
    }

    // ---- command-result ----

    public function test_command_result_marks_success(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::SENT,
            'params'    => [],
            'queued_at' => now(),
        ]);

        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
                'result'     => ['backup_file' => 'backup-2026-01-01.tar.gz'],
            ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok']);

        $command->refresh();
        $this->assertEquals(CommandStatus::SUCCESS, $command->status);
        $this->assertNotNull($command->completed_at);
    }

    public function test_command_result_marks_failed_with_error(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::SENT,
            'params'    => [],
            'queued_at' => now(),
        ]);

        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'failed',
                'error'      => 'Disk full',
            ]);

        $response->assertStatus(200);

        $command->refresh();
        $this->assertEquals(CommandStatus::FAILED, $command->status);
        $this->assertEquals('Disk full', $command->error_message);
    }

    public function test_command_result_404_for_wrong_site(): void
    {
        // Create a command belonging to a different site via different token
        $otherToken = bin2hex(random_bytes(32));
        $otherClient = Client::create([
            'tenant_id'      => $this->site->tenant_id,
            'name'           => 'Other Client',
            'email'          => 'other@example.com',
            'portal_password' => bcrypt('secret'),
        ]);
        $otherSite = Site::create([
            'tenant_id'         => $this->site->tenant_id,
            'client_id'         => $otherClient->id,
            'plan_id'           => $this->site->plan_id,
            'name'              => 'Other Site',
            'url'               => 'https://other.example.com',
            'agent_token'       => hash('sha256', $otherToken),
            'agent_token_last4' => substr($otherToken, -4),
            'is_active'         => true,
        ]);
        $command = SiteCommand::create([
            'tenant_id' => $otherSite->tenant_id,
            'site_id'   => $otherSite->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::SENT,
            'params'    => [],
            'queued_at' => now(),
        ]);

        // Try to complete it with the wrong site's token
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
            ]);

        $response->assertStatus(404);
    }

    // ---- plugin-list ----

    public function test_plugin_list_creates_snapshot(): void
    {
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/plugin-list', [
                'plugins' => [
                    ['name' => 'woocommerce', 'version' => '8.0', 'active' => true, 'update_available' => false],
                    ['name' => 'wordfence',   'version' => '7.10', 'active' => true, 'update_available' => true],
                    ['name' => 'akismet',     'version' => '5.1', 'active' => false, 'update_available' => false],
                ],
            ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok', 'received' => 3]);

        $snapshot = PluginSnapshot::where('site_id', $this->site->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals(3, $snapshot->total);
        $this->assertEquals(2, $snapshot->active);
        $this->assertEquals(1, $snapshot->inactive);
        $this->assertEquals(1, $snapshot->updates_available);
    }

    // ---- event ----

    public function test_event_creates_record(): void
    {
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/event', [
                'type'     => 'update_complete',
                'severity' => 'success',
                'title'    => 'WooCommerce updated to 8.1',
                'message'  => 'Plugin updated successfully',
                'metadata' => ['plugin' => 'woocommerce', 'version' => '8.1'],
            ]);

        $response->assertStatus(200)->assertJson(['status' => 'ok']);

        $event = Event::where('site_id', $this->site->id)->first();
        $this->assertNotNull($event);
        $this->assertEquals('update_complete', $event->type);
        $this->assertEquals(EventSeverity::SUCCESS, $event->severity);
    }

    public function test_event_rejects_invalid_severity(): void
    {
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/event', [
                'type'     => 'something',
                'severity' => 'nuclear',
                'title'    => 'Test',
            ]);

        $response->assertStatus(422);
    }
}
