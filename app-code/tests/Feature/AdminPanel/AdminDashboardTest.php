<?php

namespace Tests\Feature\AdminPanel;

use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Support\AdminDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $this->plan = Plan::first();

        $this->client = Client::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'name'      => 'Acme Co',
            'email'     => 'acme@example.com',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function test_portal_status_counts_sum_to_total_sites(): void
    {
        $this->createSite(status: SiteStatus::ACTIVE, paid: true, agent: true);
        $this->createSite(status: SiteStatus::DOWN, paid: true, agent: true);
        $this->createSite(status: SiteStatus::PENDING, paid: false, agent: false);

        $counts = AdminDashboard::portalStatusCounts();
        $bucketTotal = $counts['protected'] + $counts['setup'] + $counts['checkout'] + $counts['warning'] + $counts['issue'];

        $this->assertSame(3, AdminDashboard::totalSites());
        $this->assertSame(3, $bucketTotal);
        $this->assertSame(1, $counts['protected']);
        $this->assertSame(1, $counts['issue']);
        $this->assertSame(1, $counts['setup']);
    }

    public function test_paying_sites_uses_active_subscriptions_not_client_flag(): void
    {
        $this->createSite(status: SiteStatus::ACTIVE, paid: true, agent: true);
        $this->createSite(status: SiteStatus::PENDING, paid: false, agent: false);

        $this->assertSame(1, AdminDashboard::payingSitesCount());
    }

    public function test_estimated_mrr_sums_active_plan_prices(): void
    {
        $this->createSite(status: SiteStatus::ACTIVE, paid: true, agent: true);

        $this->assertSame((float) $this->plan->price_monthly, AdminDashboard::estimatedMrr());
    }

    public function test_daily_new_subscription_counts_include_today(): void
    {
        $this->createSite(status: SiteStatus::ACTIVE, paid: true, agent: true);

        $counts = AdminDashboard::dailyNewSubscriptionCounts();

        $this->assertSame(1, end($counts));
    }

    public function test_attention_items_include_down_paid_site_and_stale_ticket(): void
    {
        $downSite = $this->createSite(status: SiteStatus::DOWN, paid: true, agent: true);

        $ticket = Ticket::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $this->client->id,
            'site_id'   => $downSite->id,
            'subject'   => 'Help with backups',
            'message'   => 'Need restore',
            'status'    => 'open',
            'priority'  => 'normal',
        ]);
        $ticket->created_at = now()->subDays(2);
        $ticket->saveQuietly();

        $types = AdminDashboard::attentionItems()->pluck('type')->all();

        $this->assertContains('down', $types);
        $this->assertContains('ticket', $types);
    }

    private function createSite(SiteStatus $status, bool $paid, bool $agent): Site
    {
        $site = Site::create([
            'id'           => \Illuminate\Support\Str::uuid(),
            'tenant_id'    => config('app.tenant_id'),
            'client_id'    => $this->client->id,
            'plan_id'      => $this->plan->id,
            'name'         => 'site-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 8),
            'url'          => 'https://example-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 6).'.test',
            'type'         => 'wordpress',
            'status'       => $status,
            'last_seen_at' => $agent ? now() : null,
        ]);

        if ($paid) {
            $subscription = Subscription::create([
                'id'                     => \Illuminate\Support\Str::uuid(),
                'tenant_id'              => config('app.tenant_id'),
                'client_id'                => $this->client->id,
                'site_id'                  => $site->id,
                'plan_id'                  => $this->plan->id,
                'stripe_subscription_id'   => 'sub_test_'.(string) \Illuminate\Support\Str::uuid(),
                'stripe_status'            => 'active',
            ]);

            $site->update(['subscription_id' => $subscription->id]);
            $site->load('subscription');
        }

        return $site->fresh(['subscription']);
    }
}
