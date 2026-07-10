<?php

namespace Tests\Unit\Services;

use App\Enums\BackupStatus;
use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Models\Backup;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\MaintenanceScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

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
            'price_monthly' => 49.00,
            'features'      => [
                'backup_frequency'      => 'twice_monthly',
                'backups_per_month'     => 2,
                'backup_retention_days' => 30,
                'uptime_monitoring'     => true,
            ],
        ]);

        $client = Client::create([
            'tenant_id'       => $tenant->id,
            'name'            => 'Test Client',
            'email'           => 'client@example.com',
            'portal_password' => bcrypt('secret'),
        ]);

        $this->site = Site::create([
            'tenant_id'         => $tenant->id,
            'client_id'         => $client->id,
            'plan_id'           => $plan->id,
            'name'              => 'Test Site',
            'url'               => 'https://testsite.example.com',
            'type'              => SiteType::WORDPRESS,
            'agent_token'       => hash('sha256', 'token'),
            'agent_token_last4' => 'abcd',
            'is_active'         => true,
            'status'            => SiteStatus::ACTIVE,
            'last_seen_at'      => now(),
        ]);

        $subscription = Subscription::create([
            'tenant_id'              => $tenant->id,
            'client_id'              => $client->id,
            'site_id'                => $this->site->id,
            'plan_id'                => $plan->id,
            'stripe_subscription_id' => 'sub_test',
            'stripe_status'          => 'active',
        ]);

        $this->site->update(['subscription_id' => $subscription->id]);
    }

    /** @test */
    public function backup_is_due_when_no_prior_successful_backup(): void
    {
        $scheduler = app(MaintenanceScheduler::class);

        $this->assertTrue($scheduler->isBackupDue($this->site->fresh(['plan', 'subscription'])));
    }

    /** @test */
    public function backup_not_due_when_recent_success_exists(): void
    {
        Backup::create([
            'tenant_id'    => $this->site->tenant_id,
            'site_id'      => $this->site->id,
            'status'       => BackupStatus::SUCCESS,
            'type'         => 'scheduled',
            'completed_at' => now()->subDays(3),
        ]);

        $scheduler = app(MaintenanceScheduler::class);

        $this->assertFalse($scheduler->isBackupDue($this->site->fresh(['plan', 'subscription'])));
    }

    /** @test */
    public function monitor_backup_not_due_when_two_scheduled_this_month(): void
    {
        foreach ([20, 5] as $daysAgo) {
            Backup::create([
                'tenant_id'    => $this->site->tenant_id,
                'site_id'      => $this->site->id,
                'status'       => BackupStatus::SUCCESS,
                'type'         => 'scheduled',
                'completed_at' => now()->subDays($daysAgo),
            ]);
        }

        $scheduler = app(MaintenanceScheduler::class);

        $this->assertFalse($scheduler->isBackupDue($this->site->fresh(['plan', 'subscription'])));
    }

    /** @test */
    public function queue_due_backups_creates_pending_command(): void
    {
        $queued = app(MaintenanceScheduler::class)->queueDueBackups();

        $this->assertSame(1, $queued);
        $this->assertDatabaseHas('site_commands', [
            'site_id' => $this->site->id,
            'type'    => 'run_backup',
            'status'  => 'pending',
        ]);
    }

    /** @test */
    public function malware_scan_not_due_after_recent_scan(): void
    {
        $guardPlan = Plan::create([
            'tenant_id'     => $this->site->tenant_id,
            'name'          => 'Guard',
            'slug'          => 'guard',
            'price_monthly' => 99.00,
            'features'      => [
                'malware_scan'      => true,
                'broken_link_audit' => true,
            ],
        ]);

        $this->site->update(['plan_id' => $guardPlan->id]);

        \App\Models\Event::create([
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'type'      => 'malware_scan_complete',
            'severity'  => \App\Enums\EventSeverity::SUCCESS,
            'title'     => 'Clean',
            'message'   => 'OK',
        ]);

        $scheduler = app(MaintenanceScheduler::class);

        $this->assertFalse($scheduler->isMalwareScanDue($this->site->fresh(['plan', 'subscription'])));
    }

    /** @test */
    public function broken_link_audit_due_when_none_this_month(): void
    {
        $guardPlan = Plan::create([
            'tenant_id'     => $this->site->tenant_id,
            'name'          => 'Guard',
            'slug'          => 'guard',
            'price_monthly' => 99.00,
            'features'      => [
                'malware_scan'      => true,
                'broken_link_audit' => true,
            ],
        ]);

        $this->site->update(['plan_id' => $guardPlan->id]);

        $scheduler = app(MaintenanceScheduler::class);

        $this->assertTrue($scheduler->isBrokenLinkAuditDue($this->site->fresh(['plan', 'subscription'])));
    }
}
