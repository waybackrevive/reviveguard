<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOpsTest extends TestCase
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
            'id'              => \Illuminate\Support\Str::uuid(),
            'tenant_id'       => config('app.tenant_id'),
            'name'            => 'Ops Client',
            'email'           => 'ops@example.com',
            'portal_password' => bcrypt('password'),
            'stripe_id'       => 'cus_test_abcdefghijklmnop',
            'is_active'       => true,
        ]);
    }

    public function test_paying_sites_count_and_summary_label(): void
    {
        $this->makeSite(paid: true);
        $this->makeSite(paid: false);

        $this->assertSame(2, $this->client->sites()->count());
        $this->assertSame(1, $this->client->payingSitesCount());
        $this->assertSame('2 sites · 1 paid', $this->client->sitesSummaryLabel());
    }

    public function test_open_tickets_count(): void
    {
        $site = $this->makeSite(paid: false);

        Ticket::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $this->client->id,
            'site_id'   => $site->id,
            'subject'   => 'Need help',
            'message'   => 'Details',
            'status'    => 'open',
            'priority'  => 'normal',
        ]);

        Ticket::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $this->client->id,
            'site_id'   => $site->id,
            'subject'   => 'Resolved',
            'message'   => 'Done',
            'status'    => 'resolved',
            'priority'  => 'normal',
        ]);

        $this->assertSame(1, $this->client->openTicketsCount());
    }

    public function test_masked_stripe_customer_id(): void
    {
        $this->assertSame('cus_test_a…', $this->client->maskedStripeCustomerId());
    }

    private function makeSite(bool $paid): Site
    {
        $site = Site::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $this->client->id,
            'plan_id'   => $this->plan->id,
            'name'      => 'site-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 8),
            'url'       => 'https://example-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 6).'.test',
            'type'      => 'wordpress',
            'status'    => 'pending',
        ]);

        if ($paid) {
            $subscription = Subscription::create([
                'id'                     => \Illuminate\Support\Str::uuid(),
                'tenant_id'              => config('app.tenant_id'),
                'client_id'              => $this->client->id,
                'site_id'                => $site->id,
                'plan_id'                => $this->plan->id,
                'stripe_subscription_id' => 'sub_ops_'.(string) \Illuminate\Support\Str::uuid(),
                'stripe_status'          => 'active',
            ]);

            $site->update(['subscription_id' => $subscription->id]);
        }

        return $site->fresh();
    }
}
