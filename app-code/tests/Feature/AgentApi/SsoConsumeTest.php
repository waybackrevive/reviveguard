<?php

namespace Tests\Feature\AgentApi;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Site;
use App\Models\SiteLoginToken;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoConsumeTest extends TestCase
{
    use RefreshDatabase;

    private string $rawToken;
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
            'name'          => 'Guard',
            'slug'          => 'guard',
            'price_monthly' => 99,
            'features'      => [],
        ]);

        $client = Client::create([
            'tenant_id'       => $tenant->id,
            'name'            => 'Test',
            'email'           => 't@example.com',
            'portal_password' => bcrypt('secret'),
        ]);

        $this->rawToken = bin2hex(random_bytes(32));

        $this->site = Site::create([
            'tenant_id'         => $tenant->id,
            'client_id'         => $client->id,
            'plan_id'           => $plan->id,
            'name'              => 'Test',
            'url'               => 'https://example.com',
            'agent_token'       => hash('sha256', $this->rawToken),
            'agent_token_last4' => substr($this->rawToken, -4),
            'last_seen_at'      => now(),
            'is_active'         => true,
        ]);
    }

    public function test_sso_consume_accepts_valid_token(): void
    {
        $plain = 'one-time-login-token-abc';

        SiteLoginToken::create([
            'site_id'    => $this->site->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinute(),
        ]);

        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/sso-consume', ['login_token' => $plain]);

        $response->assertOk()->assertJson(['ok' => true]);
    }

    public function test_sso_consume_rejects_invalid_token(): void
    {
        $response = $this->withToken($this->rawToken)
            ->postJson('/api/v1/agent/sso-consume', ['login_token' => 'wrong']);

        $response->assertStatus(403);
    }
}
