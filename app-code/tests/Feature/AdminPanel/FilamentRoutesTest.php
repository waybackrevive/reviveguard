<?php

namespace Tests\Feature\AdminPanel;

use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\TenantSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $this->admin = User::create([
            'id'             => \Illuminate\Support\Str::uuid(),
            'tenant_id'      => '00000000-0000-0000-0000-000000000001',
            'name'           => 'Admin',
            'email'          => 'admin@reviveguard.com',
            'password'       => bcrypt('password'),
            'is_super_admin' => true,
        ]);
    }

    public function test_admin_login_page_loads(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_admin_redirects_to_login_when_unauthenticated(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_accessible_when_authenticated(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin');
        $response->assertStatus(200);
    }

    public function test_clients_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/clients');
        $response->assertStatus(200);
    }

    public function test_sites_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/sites');
        $response->assertStatus(200);
    }

    public function test_events_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/events');
        $response->assertStatus(200);
    }

    public function test_event_view_page_accessible(): void
    {
        $client = Client::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'tenant_id'       => config('app.tenant_id'),
            'name'            => 'View Event Client',
            'email'           => 'view-event@example.com',
            'portal_password' => bcrypt('password'),
            'is_active'       => true,
        ]);

        $site = Site::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'client_id' => $client->id,
            'plan_id'   => \App\Models\Plan::first()->id,
            'name'      => 'view-event-site',
            'url'       => 'https://view-event.test',
            'type'      => 'wordpress',
            'status'    => 'active',
        ]);

        $event = \App\Models\Event::create([
            'id'        => \Illuminate\Support\Str::uuid(),
            'tenant_id' => config('app.tenant_id'),
            'site_id'   => $site->id,
            'type'      => 'uptime_probe',
            'severity'  => 'warning',
            'title'     => 'Test event',
            'message'   => 'Probe failed twice',
            'metadata'  => ['status_code' => 503],
            'resolved'  => false,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get('/admin/events/'.$event->id);
        $response->assertStatus(200);
        $response->assertSee('Probe failed twice');
    }

    public function test_subscriptions_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/subscriptions');
        $response->assertStatus(200);
    }

    public function test_invoices_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/invoices');
        $response->assertStatus(200);
    }

    public function test_addon_orders_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/addon-orders');
        $response->assertStatus(200);
    }

    public function test_reports_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/reports');
        $response->assertStatus(200);
    }

    public function test_site_commands_index_accessible(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/site-commands');
        $response->assertStatus(200);
    }

    public function test_non_super_admin_cannot_access_filament(): void
    {
        $regularUser = User::create([
            'id'             => \Illuminate\Support\Str::uuid(),
            'tenant_id'      => '00000000-0000-0000-0000-000000000001',
            'name'           => 'Regular',
            'email'          => 'regular@reviveguard.com',
            'password'       => bcrypt('password'),
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($regularUser, 'web')->get('/admin');
        // Filament returns 403 when canAccessPanel() returns false
        $response->assertStatus(403);
    }
}
