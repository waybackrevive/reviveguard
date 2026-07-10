<?php

namespace Tests\Unit\Services;

use App\Services\BrokenLinkAuditService;
use Tests\TestCase;

class BrokenLinkAuditServiceTest extends TestCase
{
    /** @test */
    public function audit_rejects_invalid_url(): void
    {
        $result = app(BrokenLinkAuditService::class)->audit('');

        $this->assertSame('failed', $result['status']);
        $this->assertSame(0, $result['total_checked']);
    }

    /** @test */
    public function canonical_url_normalization_strips_trailing_slash(): void
    {
        $service = new BrokenLinkAuditService(app(\App\Services\NotificationService::class));
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('canonicalUrl');
        $method->setAccessible(true);

        $this->assertSame(
            'https://example.com/about',
            $method->invoke($service, 'https://example.com/about/')
        );
    }
}
