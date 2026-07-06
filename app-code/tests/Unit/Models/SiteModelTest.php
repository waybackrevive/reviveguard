<?php

namespace Tests\Unit\Models;

use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Models\Site;
use PHPUnit\Framework\TestCase;

class SiteModelTest extends TestCase
{
    public function test_site_status_enum_values_are_correct(): void
    {
        $this->assertSame('pending', SiteStatus::PENDING->value);
        $this->assertSame('active', SiteStatus::ACTIVE->value);
        $this->assertSame('down', SiteStatus::DOWN->value);
        $this->assertSame('warning', SiteStatus::WARNING->value);
        $this->assertSame('suspended', SiteStatus::SUSPENDED->value);
    }

    public function test_site_type_enum_values_are_correct(): void
    {
        $this->assertSame('wordpress', SiteType::WORDPRESS->value);
        $this->assertSame('html', SiteType::HTML->value);
        $this->assertSame('other', SiteType::OTHER->value);
    }

    public function test_site_status_color_mapping(): void
    {
        $this->assertSame('success', SiteStatus::ACTIVE->color());
        $this->assertSame('danger', SiteStatus::DOWN->color());
        $this->assertSame('warning', SiteStatus::WARNING->color());
    }

    public function test_site_is_not_incrementing_and_uses_string_key(): void
    {
        $site = new Site();
        $this->assertSame('string', $site->getKeyType());
        $this->assertFalse($site->getIncrementing());
    }

    public function test_site_agent_token_is_hidden(): void
    {
        $site = new Site();
        $this->assertContains('agent_token', $site->getHidden());
    }

    public function test_hostname_and_registrable_domain_from_url(): void
    {
        $site = new Site(['url' => 'https://www.example.com/path']);

        $this->assertSame('www.example.com', $site->hostname());
        $this->assertSame('example.com', $site->registrableDomain());
    }
}
