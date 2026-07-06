<?php

namespace Tests\Unit\Models;

use App\Enums\SiteStatus;
use App\Models\Site;
use Tests\TestCase;

class SitePortalStatusTest extends TestCase
{
    public function test_never_connected_site_shows_setup_not_down(): void
    {
        $site = new Site([
            'status'       => SiteStatus::DOWN,
            'last_seen_at' => null,
        ]);

        $this->assertSame('setup', $site->portalStatusKey());
        $this->assertSame('Setup needed', $site->portalStatusLabel());
    }

    public function test_connected_active_site_shows_protected(): void
    {
        $site = new Site([
            'status'       => SiteStatus::ACTIVE,
            'last_seen_at' => now(),
        ]);

        $this->assertSame('protected', $site->portalStatusKey());
    }

    public function test_paid_site_without_connection_shows_setup_not_checkout(): void
    {
        $site = new Site([
            'status'            => SiteStatus::ACTIVE,
            'subscription_id'   => 'sub-1',
            'last_seen_at'      => null,
        ]);

        $site->setRelation('subscription', new \App\Models\Subscription([
            'stripe_status' => 'active',
        ]));

        $this->assertSame('setup', $site->portalStatusKey());
    }
}
