<?php

namespace Tests\Unit\Services;

use App\Services\UptimeKumaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UptimeKumaServiceTest extends TestCase
{
    public function test_create_monitor_returns_null_when_not_configured(): void
    {
        // No UPTIME_KUMA_URL or API_KEY configured
        config(['services.uptime_kuma.url' => '', 'services.uptime_kuma.api_key' => '']);

        $service = new UptimeKumaService();
        $result  = $service->createMonitor('Test Site', 'https://example.com');

        $this->assertNull($result);
    }

    public function test_create_monitor_returns_monitor_id_on_success(): void
    {
        config([
            'services.uptime_kuma.url'     => 'http://uptime.local',
            'services.uptime_kuma.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://uptime.local/api/v1/monitor' => Http::response(['monitorID' => 42], 200),
        ]);

        $service = new UptimeKumaService();
        $result  = $service->createMonitor('Test Site', 'https://example.com');

        $this->assertSame(42, $result);
    }

    public function test_create_monitor_returns_null_on_api_failure(): void
    {
        config([
            'services.uptime_kuma.url'     => 'http://uptime.local',
            'services.uptime_kuma.api_key' => 'test-key',
        ]);

        Http::fake([
            'http://uptime.local/api/v1/monitor' => Http::response(['error' => 'fail'], 500),
        ]);

        Log::shouldReceive('warning')->once();

        $service = new UptimeKumaService();
        $result  = $service->createMonitor('Test Site', 'https://example.com');

        $this->assertNull($result);
    }

    public function test_delete_monitor_returns_false_when_not_configured(): void
    {
        config(['services.uptime_kuma.url' => '', 'services.uptime_kuma.api_key' => '']);

        $service = new UptimeKumaService();
        $this->assertFalse($service->deleteMonitor(42));
    }
}
