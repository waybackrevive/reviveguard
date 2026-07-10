<?php

namespace Tests\Feature\AgentApi;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Backup;
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

    public function test_command_result_creates_backup_record_on_success(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_BACKUP,
            'status'    => CommandStatus::SENT,
            'params'    => ['trigger' => 'manual'],
            'queued_at' => now(),
            'sent_at'   => now(),
        ]);

        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
                'result'     => [
                    'b2_path'      => 'reviveguard-backups/site/backup.tar.gz',
                    'file_size_mb' => 12.5,
                    'checksum'     => 'sha256:abc123def456',
                ],
            ]);

        $response->assertStatus(200);

        $backup = Backup::where('site_id', $this->site->id)->first();
        $this->assertNotNull($backup);
        $this->assertSame('success', $backup->status->value);
        $this->assertSame('manual', $backup->type);
        $this->assertSame('abc123def456', $backup->checksum_sha256);

        $this->assertDatabaseHas('events', [
            'site_id' => $this->site->id,
            'type'    => 'backup_complete',
        ]);
    }

    public function test_command_result_is_idempotent_when_already_completed(): void
    {
        $command = SiteCommand::create([
            'tenant_id'    => $this->site->tenant_id,
            'site_id'      => $this->site->id,
            'type'         => CommandType::RUN_BACKUP,
            'status'       => CommandStatus::SUCCESS,
            'params'       => [],
            'queued_at'    => now(),
            'completed_at' => now()->subMinute(),
        ]);

        $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
            ])
            ->assertStatus(200);

        $this->assertSame(0, Backup::where('site_id', $this->site->id)->count());
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

        $this->assertDatabaseHas('events', [
            'site_id' => $this->site->id,
            'type'    => 'backup_failed',
        ]);

        $backup = Backup::where('site_id', $this->site->id)->first();
        $this->assertNotNull($backup);
        $this->assertSame('failed', $backup->status->value);
    }

    public function test_command_result_malware_scan_clean_creates_complete_event(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_MALWARE_SCAN,
            'status'    => CommandStatus::SENT,
            'params'    => ['trigger' => 'manual'],
            'queued_at' => now(),
        ]);

        $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
                'result'     => [
                    'scan_type' => 'wordpress',
                    'findings'  => [],
                    'summary'   => ['critical_count' => 0, 'warning_count' => 0, 'clean' => true],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'site_id' => $this->site->id,
            'type'    => 'malware_scan_complete',
        ]);
    }

    public function test_command_result_update_failure_queues_rollback_when_pre_update_backup_exists(): void
    {
        $backup = Backup::create([
            'tenant_id'    => $this->site->tenant_id,
            'site_id'      => $this->site->id,
            'status'       => \App\Enums\BackupStatus::SUCCESS,
            'type'         => 'pre_update',
            'b2_file_key'  => 'reviveguard-backups/test/backup.tar.gz',
            'b2_bucket'    => 'test-bucket',
            'completed_at' => now()->subHour(),
        ]);

        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_WP_UPDATES,
            'status'    => CommandStatus::SENT,
            'params'    => ['trigger' => 'manual'],
            'queued_at' => now(),
        ]);

        $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'failed',
                'error'      => 'Plugin conflict',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'site_id' => $this->site->id,
            'type'    => 'update_failed',
        ]);

        $this->assertDatabaseHas('site_commands', [
            'site_id' => $this->site->id,
            'type'    => 'rollback_restore',
            'status'  => 'pending',
        ]);

        $rollback = SiteCommand::where('site_id', $this->site->id)
            ->where('type', CommandType::ROLLBACK_RESTORE)
            ->first();

        $this->assertNotNull($rollback);
        $this->assertSame($backup->b2_file_key, $rollback->params['b2_path'] ?? null);
    }

    public function test_command_result_deferred_update_queues_pre_update_backup(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::RUN_WP_UPDATES,
            'status'    => CommandStatus::SENT,
            'params'    => ['trigger' => 'scheduled'],
            'queued_at' => now(),
        ]);

        $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
                'result'     => [
                    'status' => 'deferred',
                    'reason' => 'No backup in last 24 hours',
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('site_commands', [
            'site_id' => $this->site->id,
            'type'    => 'run_backup',
            'status'  => 'pending',
        ]);
    }

    public function test_command_result_rollback_success_creates_event(): void
    {
        $command = SiteCommand::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => CommandType::ROLLBACK_RESTORE,
            'status'    => CommandStatus::SENT,
            'params'    => [
                'trigger'   => 'auto',
                'b2_path'   => 'reviveguard-backups/test/backup.tar.gz',
                'b2_bucket' => 'test-bucket',
            ],
            'queued_at' => now(),
        ]);

        $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/command-result', [
                'command_id' => $command->id,
                'status'     => 'success',
                'result'     => ['status' => 'success', 'message' => 'Restored'],
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('events', [
            'site_id' => $this->site->id,
            'type'    => 'rollback_complete',
        ]);
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
