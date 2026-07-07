<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Support\PortalAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_url_logs_in_active_client(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);

        $client = Client::create([
            'id'                      => \Illuminate\Support\Str::uuid(),
            'tenant_id'               => config('app.tenant_id'),
            'name'                    => 'Portal Access Client',
            'email'                   => 'access@example.com',
            'portal_password'         => bcrypt('password'),
            'is_active'               => true,
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->get(PortalAccess::signedLoginUrl($client));

        $response->assertRedirect(route('portal.sites'));
        $this->assertAuthenticatedAs($client, 'client');
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);

        $client = Client::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'tenant_id'       => config('app.tenant_id'),
            'name'            => 'Unsigned Client',
            'email'           => 'unsigned@example.com',
            'portal_password' => bcrypt('password'),
            'is_active'       => true,
        ]);

        $this->get(route('portal.admin-access', ['client' => $client->id]))
            ->assertForbidden();
    }

    public function test_suspended_client_is_redirected_to_login(): void
    {
        $this->seed(\Database\Seeders\TenantSeeder::class);

        $client = Client::create([
            'id'                      => \Illuminate\Support\Str::uuid(),
            'tenant_id'               => config('app.tenant_id'),
            'name'                    => 'Suspended Client',
            'email'                   => 'suspended@example.com',
            'portal_password'         => bcrypt('password'),
            'is_active'               => false,
            'onboarding_completed_at' => now(),
        ]);

        $this->get(PortalAccess::signedLoginUrl($client))
            ->assertRedirect(route('portal.login'));
    }
}
