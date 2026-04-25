<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000001'],
            [
                'name'          => 'WaybackRevive',
                'slug'          => 'waybackrevive',
                'domain'        => 'app.reviveguard.com',
                'primary_color' => '#1a1a2e',
                'settings'      => [],
            ]
        );
    }
}
