<?php

namespace Tests\Feature\ProductCompletion;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Event;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Tenant;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaunchGateTest extends TestCase
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
            'name'          => 'Shield',
            'slug'          => 'shield',
            'price_monthly' => 179.00,
            'features'      => [
                'malware_scan'      => true,
                'broken_link_audit' => true,
                'backup_retention_days' => 90,
            ],
        ]);

        $client = Client::create([
            'tenant_id'       => $tenant->id,
            'name'            => 'Launch Gate Client',
            'email'           => 'launch@example.com',
            'portal_password' => bcrypt('secret'),
        ]);

        $this->site = Site::create([
            'tenant_id'  => $tenant->id,
            'client_id'  => $client->id,
            'plan_id'    => $plan->id,
            'name'       => 'Launch Gate Site',
            'url'        => 'https://launchgate.example.com',
            'is_active'  => true,
            'status'     => SiteStatus::ACTIVE,
            'ssl_valid'  => true,
            'uptime_30d' => 99.9,
        ]);
    }

    /** @test */
    public function monthly_report_preview_includes_all_product_sections(): void
    {
        $period = now()->format('Y-m');
        $at     = now()->startOfMonth()->addDays(2);

        $this->seedReportEvents($at);

        $preview = app(ReportService::class)->renderPreview($this->site->id, $period);

        $this->assertNotNull($preview);

        $expected = [
            'SSL & Domain Status',
            'Site Information',
            'Events This Month',
            'WordPress Updates Applied',
            'Rollback Activity',
            'Malware Scans',
            'Broken Link Audits',
            'Quarterly Security Audit',
            'Quarterly SEO Snapshot',
            'Backups',
        ];

        foreach ($expected as $section) {
            $this->assertContains($section, $preview['sections'], "Missing section: {$section}");
        }
    }

    /** @test */
    public function maintenance_dry_run_command_exits_successfully(): void
    {
        $this->artisan('maintenance:dry-run')
            ->assertExitCode(0);
    }

    /** @test */
    public function report_dry_run_command_exits_successfully(): void
    {
        $period = now()->format('Y-m');
        $this->seedReportEvents(now()->startOfMonth()->addDay());

        $this->artisan('report:dry-run', [
            'site'     => $this->site->id,
            '--period' => $period,
        ])->assertExitCode(0);
    }

    /** @test */
    public function monitoring_status_command_exits_successfully(): void
    {
        $this->artisan('monitoring:status')
            ->assertExitCode(0);
    }

    private function seedReportEvents(\Illuminate\Support\Carbon $at): void
    {
        $base = [
            'tenant_id' => $this->site->tenant_id,
            'site_id'   => $this->site->id,
            'created_at' => $at,
            'updated_at' => $at,
        ];

        Event::create(array_merge($base, [
            'type'     => 'update_complete',
            'severity' => EventSeverity::SUCCESS,
            'title'    => 'WordPress updates applied',
            'message'  => 'Updated: 2 plugin(s).',
        ]));

        Event::create(array_merge($base, [
            'type'     => 'rollback_complete',
            'severity' => EventSeverity::SUCCESS,
            'title'    => 'Site restored from backup',
            'message'  => 'Automatic rollback succeeded.',
        ]));

        Event::create(array_merge($base, [
            'type'     => 'malware_scan_complete',
            'severity' => EventSeverity::SUCCESS,
            'title'    => 'Malware scan complete — no issues found',
            'message'  => 'Weekly security scan finished with a clean result.',
        ]));

        Event::create(array_merge($base, [
            'type'     => 'broken_link_audit_complete',
            'severity' => EventSeverity::INFO,
            'title'    => 'Broken link audit complete',
            'message'  => 'Found 3 broken links.',
            'metadata' => ['broken_count' => 3],
        ]));

        Event::create(array_merge($base, [
            'type'     => 'quarterly_security_audit',
            'severity' => EventSeverity::SUCCESS,
            'title'    => 'Quarterly security audit',
            'message'  => 'No critical risks identified.',
            'metadata' => ['risk_level' => 'low'],
        ]));

        Event::create(array_merge($base, [
            'type'     => 'quarterly_seo_snapshot',
            'severity' => EventSeverity::INFO,
            'title'    => 'Quarterly SEO snapshot',
            'message'  => 'Site health score: 82/100.',
            'metadata' => ['pages_crawled' => 24],
        ]));
    }
}
