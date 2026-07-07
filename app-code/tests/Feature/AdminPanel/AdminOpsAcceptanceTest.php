<?php

namespace Tests\Feature\AdminPanel;

use App\Enums\BackupStatus;
use App\Enums\SiteStatus;
use App\Models\Backup;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use App\Support\AdminDashboard;
use App\Support\PortalAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint A1–A5 acceptance checks for the Filament admin ops panel.
 */
class AdminOpsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Client $client;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $this->plan = Plan::first();

        $this->admin = User::create([
            'id'             => \Illuminate\Support\Str::uuid(),
            'tenant_id'      => config('app.tenant_id'),
            'name'           => 'QA Admin',
            'email'          => 'qa-admin@reviveguard.com',
            'password'       => bcrypt('password'),
            'is_super_admin' => true,
        ]);

        $this->client = Client::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'tenant_id'       => config('app.tenant_id'),
            'name'            => 'QA Client',
            'email'           => 'qa-client@example.com',
            'portal_password' => bcrypt('password'),
            'stripe_id'       => 'cus_qa_test',
            'is_active'       => true,
        ]);
    }

    public function test_a1_dashboard_metrics_reconcile(): void
    {
        $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);
        $this->makeSite(SiteStatus::PENDING, paid: false, agent: false);

        $counts = AdminDashboard::portalStatusCounts();
        $bucketTotal = $counts['protected'] + $counts['setup'] + $counts['checkout']
            + $counts['warning'] + $counts['issue'];

        $this->assertSame(2, AdminDashboard::totalSites());
        $this->assertSame(2, $bucketTotal);
        $this->assertSame(1, AdminDashboard::payingSitesCount());
    }

    public function test_a1_dashboard_page_loads_for_super_admin(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_a2_site_edit_page_loads_with_portal_aligned_data(): void
    {
        $site = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/sites/'.$site->id.'/edit');

        $response->assertOk();
        $response->assertSee('Monitoring');
        $response->assertSee('Billing');
        $response->assertSee('Support tickets');
    }

    public function test_a2_portal_status_filter_returns_matching_site(): void
    {
        $protected = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        $this->assertTrue(
            Site::wherePortalStatus('protected')->whereKey($protected->id)->exists()
        );
    }

    public function test_a3_client_edit_loads_and_portal_access_works(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/clients/'.$this->client->id.'/edit');

        $response->assertOk();
        $response->assertSee('Billing');

        $this->get(PortalAccess::signedLoginUrl($this->client))
            ->assertRedirect(route('portal.welcome-setup'));
    }

    public function test_a4_billing_resource_indexes_load(): void
    {
        $site = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        $this->actingAs($this->admin, 'web')->get('/admin/subscriptions')->assertOk();
        $this->actingAs($this->admin, 'web')->get('/admin/invoices')->assertOk();
        $this->actingAs($this->admin, 'web')->get('/admin/addon-orders')->assertOk();
    }

    public function test_a5_backup_download_hidden_when_b2_not_configured(): void
    {
        $site = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        $backup = Backup::create([
            'id'          => \Illuminate\Support\Str::uuid(),
            'tenant_id'   => config('app.tenant_id'),
            'site_id'     => $site->id,
            'status'      => BackupStatus::SUCCESS,
            'type'        => 'full',
            'b2_bucket'   => 'test-bucket',
            'b2_file_key' => 'backups/site.tar.gz',
            'size_bytes'  => 1048576,
            'started_at'  => now(),
            'completed_at'=> now(),
        ]);

        $this->assertTrue($backup->canDownload());
        $this->assertNull($backup->signedDownloadUrl());
    }

    public function test_a5_ticket_row_links_resolve_for_stale_ticket(): void
    {
        $site = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        $ticket = Ticket::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $this->client->id,
            'site_id'   => $site->id,
            'subject'   => 'QA stale ticket',
            'message'   => 'Help',
            'status'    => 'open',
            'priority'  => 'medium',
        ]);
        $ticket->created_at = now()->subDays(2);
        $ticket->saveQuietly();

        $items = AdminDashboard::attentionItems();
        $ticketItem = $items->firstWhere('type', 'ticket');

        $this->assertNotNull($ticketItem);
        $this->assertSame($ticket->id, $ticketItem['ticket_id']);
        $this->assertSame($this->client->id, $ticketItem['client_id']);
        $this->assertSame($site->id, $ticketItem['site_id']);
    }

    public function test_tickets_backups_admin_pages_load(): void
    {
        $this->actingAs($this->admin, 'web')->get('/admin/tickets')->assertOk();
        $this->actingAs($this->admin, 'web')->get('/admin/backups')->assertOk();
    }

    public function test_a6_reports_and_commands_indexes_load(): void
    {
        $site = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);

        \App\Models\Report::create([
            'id'          => \Illuminate\Support\Str::uuid(),
            'tenant_id'   => config('app.tenant_id'),
            'site_id'     => $site->id,
            'client_id'   => $this->client->id,
            'type'        => 'monthly',
            'period'      => '2026-06',
            'status'      => 'completed',
            'b2_bucket'   => 'reports-bucket',
            'b2_file_key' => 'reports/site.pdf',
            'size_bytes'  => 512000,
        ]);

        \App\Models\SiteCommand::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'site_id'   => $site->id,
            'type'      => \App\Enums\CommandType::RUN_BACKUP,
            'status'    => \App\Enums\CommandStatus::PENDING,
            'params'    => [],
            'queued_at' => now(),
        ]);

        $report = \App\Models\Report::first();
        $this->assertTrue($report->canDownload());
        $this->assertNull($report->signedDownloadUrl());

        $this->actingAs($this->admin, 'web')->get('/admin/reports')->assertOk();
        $this->actingAs($this->admin, 'web')->get('/admin/site-commands')->assertOk();
    }

    private function makeSite(SiteStatus $status, bool $paid, bool $agent): Site
    {
        $site = Site::create([
            'id'           => \Illuminate\Support\Str::uuid(),
            'tenant_id'    => config('app.tenant_id'),
            'client_id'    => $this->client->id,
            'plan_id'      => $this->plan->id,
            'name'         => 'qa-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 8),
            'url'          => 'https://qa-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 6).'.test',
            'type'         => 'wordpress',
            'status'       => $status,
            'last_seen_at' => $agent ? now() : null,
        ]);

        if ($paid) {
            $subscription = Subscription::create([
                'id'                     => \Illuminate\Support\Str::uuid(),
                'tenant_id'              => config('app.tenant_id'),
                'client_id'              => $this->client->id,
                'site_id'                => $site->id,
                'plan_id'                => $this->plan->id,
                'stripe_subscription_id' => 'sub_qa_'.(string) \Illuminate\Support\Str::uuid(),
                'stripe_status'          => 'active',
            ]);

            $site->update(['subscription_id' => $subscription->id]);
        }

        return $site->fresh(['subscription']);
    }
}
