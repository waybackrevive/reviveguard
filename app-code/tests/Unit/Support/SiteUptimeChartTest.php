<?php

namespace Tests\Unit\Support;

use App\Support\SiteUptimeChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteUptimeChartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function days_with_check_bars_returns_seven_columns_with_check_arrays(): void
    {
        $days = SiteUptimeChart::daysWithCheckBars('00000000-0000-0000-0000-000000000099', 7);

        $this->assertCount(7, $days);

        foreach ($days as $day) {
            $this->assertArrayHasKey('checks', $day);
            $this->assertArrayHasKey('check_count', $day);
            $this->assertIsArray($day['checks']);
            $this->assertSame(0, $day['check_count']);
        }
    }
}
