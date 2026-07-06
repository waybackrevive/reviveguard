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
    public static function daysWithCheckBars(string $siteId, int $days = 7, ?string $timezone = null): array
    {
        $tz     = $timezone ?: (string) config('app.timezone');
        $anchor = now()->timezone($tz);
        $since  = $anchor->copy()->subDays($days - 1)->startOfDay()->utc();

        $probes = SiteUptimeProbe::where('site_id', $siteId)
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at')
            ->get();

        $byDay = $probes->groupBy(
            fn (SiteUptimeProbe $p) => $p->checked_at->copy()->timezone($tz)->toDateString()
        );

        $daysOut = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date      = $anchor->copy()->subDays($i)->toDateString();
            $dayProbes = $byDay->get($date, collect());
            $count     = $dayProbes->count();
            $barHeight = self::barHeightForCount($count);
            $checks    = [];

            foreach ($dayProbes as $probe) {
                $local = $timezone
                    ? $probe->checked_at->copy()->timezone($timezone)
                    : $probe->checked_at;

                $checks[] = [
                    'is_up'      => (bool) $probe->is_up,
                    'time'       => $local->format('g:i A'),
                    'tooltip'    => self::probeTooltip($probe, $timezone),
                    'bar_height' => $barHeight,
                    'color'      => $probe->is_up ? 'bg-emerald-500' : 'bg-red-500',
                    'ring'       => $probe->is_up ? 'ring-emerald-200' : 'ring-red-200',
                ];
            }

            $daysOut[] = [
                'date'        => $date,
                'label'       => Carbon::parse($date, $tz)->format('M j'),
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

    /**
     * @return array{
     *     total_checks: int,
     *     checks_today: int,
     *     down_checks: int,
     *     avg_response_ms: ?int,
     *     uptime_7d: ?float,
     *     uptime_14d: ?float
     * }
     */
    public static function monitoringSummary(Collection $probes): array
    {
        $today     = now()->toDateString();
        $since7    = now()->subDays(7);
        $since14   = now()->subDays(14);

        $probes7  = $probes->filter(fn (SiteUptimeProbe $p) => $p->checked_at >= $since7);
        $probes14 = $probes->filter(fn (SiteUptimeProbe $p) => $p->checked_at >= $since14);

        $withMs = $probes->whereNotNull('response_ms');

        return [
            'total_checks'    => $probes->count(),
            'checks_today'    => $probes->filter(fn (SiteUptimeProbe $p) => $p->checked_at->toDateString() === $today)->count(),
            'down_checks'     => $probes->where('is_up', false)->count(),
            'avg_response_ms' => $withMs->isNotEmpty() ? (int) round($withMs->avg('response_ms')) : null,
            'uptime_7d'       => self::periodUptimePercent($probes7),
            'uptime_14d'      => self::periodUptimePercent($probes14),
        ];
    }

    /**
     * @return list<array{time: string, date: string, is_up: bool, status_code: ?int, response_ms: ?int, tooltip: string}>
     */
    public static function recentChecks(Collection $probes, int $limit = 12, ?string $timezone = null): array
    {
        return $probes
            ->sortByDesc('checked_at')
            ->take($limit)
            ->map(function (SiteUptimeProbe $p) use ($timezone) {
                $local = $timezone ? $p->checked_at->copy()->timezone($timezone) : $p->checked_at;

                return [
                    'time'         => $local->format('g:i A'),
                    'date'         => $local->format('M j, Y'),
                    'time_abbr'    => $local->format('T'),
                    'is_up'        => (bool) $p->is_up,
                    'status_code'  => $p->status_code,
                    'response_ms'  => $p->response_ms,
                    'tooltip'      => self::probeTooltip($p, $timezone),
                ];
            })
            ->values()
            ->all();
    }

    private static function barHeightForCount(int $count): int
    {
        if ($count === 0) {
            return 8;
        }

        return max(2, min(8, (int) floor(self::MAX_COLUMN_HEIGHT / $count)));
    }

    private static function probeTooltip(SiteUptimeProbe $probe, ?string $timezone = null): string
    {
        $status = $probe->is_up ? 'Online' : 'Down';
        $at     = $probe->checked_at->copy();

        if ($timezone) {
            $at = $at->timezone($timezone);
        }

        $parts = [$at->format('M j, Y g:i A T') . ' — ' . $status];

        if ($probe->status_code) {
            $parts[] = 'HTTP ' . $probe->status_code;
        }

        if ($probe->response_ms !== null) {
            $parts[] = $probe->response_ms . 'ms';
        }

        return implode(' · ', $parts);
    }
}
