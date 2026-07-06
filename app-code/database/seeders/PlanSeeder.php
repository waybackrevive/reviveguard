<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        $plans = [
            [
                'name'          => 'Monitor',
                'slug'          => 'monitor',
                'price_monthly' => 49.00,
                'stripe_price_id'      => env('STRIPE_PRICE_MONITOR_ID'),
                'stripe_test_price_id' => env('STRIPE_TEST_PRICE_MONITOR_ID'),
                'features'      => [
                    'uptime_monitoring'           => true,
                    'ssl_monitoring'              => true,
                    'domain_monitoring'           => true,
                    'backup_frequency'            => 'monthly',
                    'backup_retention_days'       => 30,
                    'wp_core_updates'             => false,
                    'wp_plugin_updates'           => false,
                    'support_tickets_per_month'   => -1,
                    'report_frequency'            => 'monthly',
                    'priority_support'            => false,
                ],
            ],
            [
                'name'          => 'Guard',
                'slug'          => 'guard',
                'price_monthly' => 99.00,
                'stripe_price_id'      => env('STRIPE_PRICE_GUARD_ID'),
                'stripe_test_price_id' => env('STRIPE_TEST_PRICE_GUARD_ID'),
                'features'      => [
                    'uptime_monitoring'           => true,
                    'ssl_monitoring'              => true,
                    'domain_monitoring'           => true,
                    'backup_frequency'            => 'daily',
                    'backup_retention_days'       => 90,
                    'wp_core_updates'             => true,
                    'wp_plugin_updates'           => true,
                    'support_tickets_per_month'   => -1,
                    'report_frequency'            => 'monthly',
                    'priority_support'            => false,
                ],
            ],
            [
                'name'          => 'Shield',
                'slug'          => 'shield',
                'price_monthly' => 179.00,
                'stripe_price_id'      => env('STRIPE_PRICE_SHIELD_ID'),
                'stripe_test_price_id' => env('STRIPE_TEST_PRICE_SHIELD_ID'),
                'features'      => [
                    'uptime_monitoring'           => true,
                    'ssl_monitoring'              => true,
                    'domain_monitoring'           => true,
                    'backup_frequency'            => 'daily',
                    'backup_retention_days'       => 180,
                    'wp_core_updates'             => true,
                    'wp_plugin_updates'           => true,
                    'support_tickets_per_month'   => -1, // unlimited: -1
                    'report_frequency'            => 'monthly',
                    'priority_support'            => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug'], 'tenant_id' => $tenantId],
                array_merge($plan, ['tenant_id' => $tenantId, 'is_active' => true])
            );
        }
    }
}
