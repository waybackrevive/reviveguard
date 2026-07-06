<?php

namespace App\Support;

use App\Models\SiteUptimeProbe;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily uptime bars for the Monitoring tab (WP Umbrella-style).
 */
final class SiteUptimeChart
{
    /**
     * @return list<array{date: string, label: string, pct: ?float, bar_height: int, color: string, has_data: bool}>
     */
    public static function dailyBars(string $siteId, int $days = 14): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $probes = SiteUptimeProbe::where('site_id', $siteId)
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->get();

        $byDay = $probes->groupBy(fn (SiteUptimeProbe $p) => $p->checked_at->toDateString());

        $bars = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date  = now()->subDays($i)->toDateString();
            $day   = $byDay->get($date, collect());
            $total = $day->count();

            if ($total === 0) {
                $bars[] = [
                    'date'       => $date,
                    'label'      => Carbon::parse($date)->format('M j'),
                    'pct'        => null,
                    'bar_height' => 8,
                    'color'      => 'bg-gray-200',
                    'has_data'   => false,
                ];

                continue;
            }

            $up  = $day->where('is_up', true)->count();
            $pct = round(($up / $total) * 100, 1);

            $bars[] = [
                'date'       => $date,
                'label'      => Carbon::parse($date)->format('M j'),
                'pct'        => $pct,
                'bar_height' => max(12, (int) round($pct * 0.64)),
                'color'      => $pct >= 99.9 ? 'bg-emerald-500' : ($pct >= 90 ? 'bg-amber-400' : 'bg-red-400'),
                'has_data'   => true,
            ];
        }

        return $bars;
    }

    public static function periodUptimePercent(Collection $probes): ?float
    {
        if ($probes->isEmpty()) {
            return null;
        }

        $up = $probes->where('is_up', true)->count();

        return round(($up / $probes->count()) * 100, 2);
    }
}
