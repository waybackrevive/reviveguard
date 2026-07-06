<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Support\ClientTimezone;
use Carbon\Carbon;
use Tests\TestCase;

class ClientTimezoneTest extends TestCase
{
    /** @test */
    public function resolve_uses_client_timezone_when_valid(): void
    {
        $client = new Client(['timezone' => 'Asia/Karachi']);

        $this->assertSame('Asia/Karachi', ClientTimezone::resolve($client));
    }

    /** @test */
    public function resolve_falls_back_to_default_for_invalid_timezone(): void
    {
        $client = new Client(['timezone' => 'Not/A/Timezone']);

        $this->assertSame(ClientTimezone::DEFAULT, ClientTimezone::resolve($client));
    }

    /** @test */
    public function format_with_abbr_includes_timezone_abbreviation(): void
    {
        $client = new Client(['timezone' => 'America/New_York']);
        $utc    = Carbon::parse('2026-01-15 18:00:00', 'UTC');

        $formatted = ClientTimezone::formatWithAbbr($client, $utc, 'M j, Y g:i A');

        $this->assertStringContainsString('Jan 15, 2026', $formatted);
        $this->assertMatchesRegularExpression('/\b(EDT|EST)\b/', $formatted);
    }

    /** @test */
    public function label_returns_human_readable_option(): void
    {
        $client = new Client(['timezone' => 'Asia/Karachi']);

        $this->assertSame('Pakistan — Karachi (PKT)', ClientTimezone::label($client));
    }
}
