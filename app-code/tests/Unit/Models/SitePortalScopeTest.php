<?php

namespace Tests\Unit\Models;

use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePortalScopeTest extends TestCase
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
            'name'      => 'Scope Test Client',
            'email'     => 'scope@example.com',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function test_portal_status_scopes_match_portal_status_key(): void
    {
        $protected = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);
        $checkout  = $this->makeSite(SiteStatus::PENDING, paid: false, agent: true);
        $setup     = $this->makeSite(SiteStatus::PENDING, paid: false, agent: false);

        $this->assertSame('protected', $protected->portalStatusKey());
        $this->assertSame('checkout', $checkout->portalStatusKey());
        $this->assertSame('setup', $setup->portalStatusKey());

        $this->assertTrue(Site::wherePortalStatus('protected')->whereKey($protected->id)->exists());
        $this->assertTrue(Site::wherePortalStatus('checkout')->whereKey($checkout->id)->exists());
        $this->assertTrue(Site::wherePortalStatus('setup')->whereKey($setup->id)->exists());
    }

    public function test_where_unpaid_excludes_active_subscription(): void
    {
        $paid   = $this->makeSite(SiteStatus::ACTIVE, paid: true, agent: true);
        $unpaid = $this->makeSite(SiteStatus::PENDING, paid: false, agent: false);

        $ids = Site::whereUnpaid()->pluck('id')->all();

        $this->assertContains($unpaid->id, $ids);
        $this->assertNotContains($paid->id, $ids);
    }

    private function makeSite(SiteStatus $status, bool $paid, bool $agent): Site
    {
        $site = Site::create([
            'id'           => \Illuminate\Support\Str::uuid(),
            'tenant_id'    => config('app.tenant_id'),
            'client_id'    => $this->client->id,
            'plan_id'      => $this->plan->id,
            'name'         => 'scope-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 8),
            'url'          => 'https://scope-'.substr((string) \Illuminate\Support\Str::uuid(), 0, 6).'.test',
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
                'stripe_subscription_id' => 'sub_scope_'.(string) \Illuminate\Support\Str::uuid(),
                'stripe_status'          => 'active',
            ]);

            $site->update(['subscription_id' => $subscription->id]);
        }

        return $site->fresh(['subscription']);
    }
}
