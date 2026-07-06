<?php

namespace App\Support;

use App\Models\SiteUptimeProbe;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Uptime visualization for the Monitoring tab — per-check bars grouped by day.
 */
final class SiteUptimeChart
{
    private const MAX_COLUMN_HEIGHT = 96;

    /**
     * Per-check bars grouped by calendar day (oldest → newest).
     *
     * @return list<array{
     *     date: string,
     *     label: string,
     *     pct: ?float,
     *     has_data: bool,
     *     check_count: int,
     *     checks: list<array{
     *         is_up: bool,
     *         time: string,
     *         tooltip: string,
     *         bar_height: int,
     *         color: string
     *     }>
     * }>
     */
    public static function daysWithCheckBars(string $siteId, int $days = 7): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $probes = SiteUptimeProbe::where('site_id', $siteId)
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->get();

        $byDay = $probes->groupBy(fn (SiteUptimeProbe $p) => $p->checked_at->toDateString());

        $daysOut = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date      = now()->subDays($i)->toDateString();
            $dayProbes = $byDay->get($date, collect());
            $count     = $dayProbes->count();
            $barHeight = self::barHeightForCount($count);
            $checks    = [];

            foreach ($dayProbes as $probe) {
                $checks[] = [
                    'is_up'      => (bool) $probe->is_up,
                    'time'       => $probe->checked_at->format('g:i A'),
                    'tooltip'    => self::probeTooltip($probe),
                    'bar_height' => $barHeight,
                    'color'      => $probe->is_up ? 'bg-emerald-500' : 'bg-red-500',
                ];
            }

            $daysOut[] = [
                'date'        => $date,
                'label'       => Carbon::parse($date)->format('M j'),
                'pct'         => $count > 0
                    ? round($dayProbes->where('is_up', true)->count() / $count * 100, 1)
                    : null,
                'has_data'    => $count > 0,
                'check_count' => $count,
                'checks'      => $checks,
            ];
        }

        return $daysOut;
    }

    /**
     * @deprecated Use daysWithCheckBars() — kept for summary stats if needed.
     *
     * @return list<array{date: string, label: string, pct: ?float, bar_height: int, color: string, has_data: bool}>
     */
    public static function dailyBars(string $siteId, int $days = 14): array
    {
        $groups = self::daysWithCheckBars($siteId, $days);

        return array_map(function (array $day) {
            $pct = $day['pct'];

            return [
                'date'       => $day['date'],
                'label'      => $day['label'],
                'pct'        => $pct,
                'bar_height' => $day['has_data'] ? max(12, (int) round((float) $pct * 0.64)) : 8,
                'color'      => $pct === null ? 'bg-gray-200' : ($pct >= 99.9 ? 'bg-emerald-500' : ($pct >= 90 ? 'bg-amber-400' : 'bg-red-400')),
                'has_data'   => $day['has_data'],
            ];
        }, $groups);
    }

    public static function periodUptimePercent(Collection $probes): ?float
    {
        if ($probes->isEmpty()) {
            return null;
        }

        $up = $probes->where('is_up', true)->count();

        return round(($up / $probes->count()) * 100, 2);
    }

    private static function barHeightForCount(int $count): int
    {
        if ($count === 0) {
            return 8;
        }

        return max(2, min(8, (int) floor(self::MAX_COLUMN_HEIGHT / $count)));
    }

    private static function probeTooltip(SiteUptimeProbe $probe): string
    {
        $status = $probe->is_up ? 'Online' : 'Down';
        $parts  = [$probe->checked_at->format('M j, Y g:i A') . ' — ' . $status];

        if ($probe->status_code) {
            $parts[] = 'HTTP ' . $probe->status_code;
        }

        if ($probe->response_ms !== null) {
            $parts[] = $probe->response_ms . 'ms';
        }

        return implode(' · ', $parts);
    }
}
