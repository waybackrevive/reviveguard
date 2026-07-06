<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Site;

/**
 * Plan-gated monitoring frequency and probe region options.
 */
final class MonitorSettings
{
    /** @var array<string, list<int>> */
    private const INTERVALS = [
        'monitor' => [5, 10, 30],
        'guard'   => [5, 10, 30],
        'shield'  => [2, 5, 10, 30],
    ];

    /** @var array<string, list<string>> */
    private const REGIONS = [
        'monitor' => ['us-east'],
        'guard'   => ['us-east', 'eu-west'],
        'shield'  => ['us-east', 'eu-west', 'ap-south'],
    ];

    /** @var array<string, string> */
    public const REGION_LABELS = [
        'us-east'  => 'US East',
        'eu-west'  => 'EU West',
        'ap-south' => 'Asia Pacific',
    ];

    public static function planSlug(Site $site): string
    {
        return $site->plan?->slug ?? 'monitor';
    }

    /** @return list<int> */
    public static function allowedIntervals(Site $site): array
    {
        return self::INTERVALS[self::planSlug($site)] ?? self::INTERVALS['monitor'];
    }

    /** @return list<string> */
    public static function allowedRegions(Site $site): array
    {
        return self::REGIONS[self::planSlug($site)] ?? self::REGIONS['monitor'];
    }

    public static function intervalLabel(int $minutes): string
    {
        return match ($minutes) {
            2  => '2 min',
            5  => '5 min',
            10 => '10 min',
            30 => '30 min',
            60 => '1 hour',
            default => "{$minutes} min",
        };
    }

    public static function normalizeInterval(Site $site, int $minutes): int
    {
        $allowed = self::allowedIntervals($site);

        return in_array($minutes, $allowed, true) ? $minutes : $allowed[0];
    }

    public static function normalizeRegion(Site $site, string $region): string
    {
        $allowed = self::allowedRegions($site);

        return in_array($region, $allowed, true) ? $region : $allowed[0];
    }

    public static function regionLabel(string $region): string
    {
        return self::REGION_LABELS[$region] ?? ucfirst(str_replace('-', ' ', $region));
    }

    public static function defaultsForPlan(?Plan $plan): array
    {
        $slug = $plan?->slug ?? 'monitor';

        return [
            'monitor_interval_minutes' => self::INTERVALS[$slug][0] ?? 5,
            'monitor_region'           => self::REGIONS[$slug][0] ?? 'us-east',
        ];
    }
}
