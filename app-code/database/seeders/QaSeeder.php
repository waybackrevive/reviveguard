<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Site;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QaSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        // Admin user — create if not exists, always sync password + super admin flag
        $admin = User::firstOrCreate(
            ['email' => 'admin@reviveguard.test'],
            [
                'name'           => 'Admin',
                'password'       => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );
        $admin->update([
            'password'       => Hash::make('password'),
            'is_super_admin' => true,
        ]);

        // Test client — create if not exists, always sync password + active flag
        $client = Client::firstOrCreate(
            ['email' => 'testclient@reviveguard.test'],
            [
                'tenant_id'       => $tenantId,
                'name'            => 'Test Client',
                'portal_password' => Hash::make('password'),
                'company_name'    => 'Test Co Ltd',
                'phone'           => '+1 555-0100',
                'timezone'        => 'UTC',
                'is_active'       => true,
            ]
        );
        // Always sync password so re-seeding fixes auth issues
        $client->update([
            'portal_password' => Hash::make('password'),
            'is_active'       => true,
        ]);

        $rawToken = 'qa-test-token-000000000001';

        // Test site
        $site = Site::firstOrCreate(
            ['url' => 'https://example-qa.test'],
            [
                'tenant_id'     => $tenantId,
                'client_id'     => $client->id,
                'name'          => 'QA Test Site',
                'status'        => 'active',
                'uptime_30d'    => 99.5,
                'uptime_7d'     => 100.0,
                'ssl_expires_at' => now()->addDays(60),
                'ssl_issuer'    => "Let's Encrypt",
                'ssl_valid'     => true,
                'domain_expires_at' => now()->addDays(180),
                'registrar'     => 'Namecheap',
                'wp_version'    => '6.5.3',
                'php_version'   => '8.2.0',
                'plugin_count'  => 12,
                'theme_name'    => 'Astra',
                'disk_usage_mb' => 512,
                'last_seen_at'  => now(),
                'agent_token'   => hash('sha256', $rawToken),
                'agent_token_last4' => substr($rawToken, -4),
                'is_active'     => true,
            ]
        );
        // Always sync the token so re-seeding fixes auth issues
        $site->update([
            'agent_token'       => hash('sha256', $rawToken),
            'agent_token_last4' => substr($rawToken, -4),
            'is_active'         => true,
        ]);

        // Test ticket
        Ticket::firstOrCreate(
            [
                'client_id' => $client->id,
                'subject'   => 'QA Test Ticket',
            ],
            [
                'tenant_id' => $tenantId,
                'site_id'   => $site->id,
                'message'   => 'This is a test support ticket created by QaSeeder for manual QA.',
                'status'    => 'open',
                'priority'  => 'medium',
            ]
        );

        $this->command->info("QA data seeded:");
        $this->command->info("  Client:    testclient@reviveguard.test / password");
        $this->command->info("  Site:      {$site->url}");
        $this->command->info("  Raw token: {$rawToken}  (use this in WP plugin)");
        $this->command->info("  Hashed:    " . hash('sha256', $rawToken));
    }
}
