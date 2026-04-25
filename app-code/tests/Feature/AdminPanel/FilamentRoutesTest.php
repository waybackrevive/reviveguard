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
