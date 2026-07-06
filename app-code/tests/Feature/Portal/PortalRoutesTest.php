<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalRoutesTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'id'   => '00000000-0000-0000-0000-000000000001',
            'name' => 'WaybackRevive',
            'slug' => 'waybackrevive',
        ]);

        $this->client = Client::create([
            'tenant_id'               => $tenant->id,
            'name'                    => 'Portal User',
            'email'                   => 'portal@example.com',
            'portal_password'         => bcrypt('password'),
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_root_redirects_to_portal_login(): void
    {
        $this->get('/')->assertRedirect('/portal/login');
    }

    public function test_sites_page_requires_auth(): void
    {
        $this->get('/portal/sites')->assertRedirect('/portal/login');
    }

    public function test_authenticated_client_can_access_sites(): void
    {
        $this->actingAs($this->client, 'client')
            ->get('/portal/sites')
            ->assertOk();
    }

    public function test_incomplete_onboarding_redirects_to_welcome_setup(): void
    {
        $this->client->update(['onboarding_completed_at' => null]);

        $this->actingAs($this->client, 'client')
            ->get('/portal/sites')
            ->assertRedirect(route('portal.welcome-setup'));
    }

    public function test_dashboard_redirects_to_sites(): void
    {
        $this->actingAs($this->client, 'client')
            ->get('/portal/dashboard')
            ->assertRedirect('/portal/sites');
    }
}
