<?php

namespace App\Support;

use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Human-readable plan features for comparison and upgrade decisions.
 */
final class PlanCatalog
{
    /** @var array<string, int> */
    private const RANK = [
        'monitor' => 1,
        'guard'   => 2,
        'shield'  => 3,
    ];

    /** @return Collection<int, Plan> */
    public static function all(): Collection
    {
        return Plan::where('is_active', true)->orderBy('price_monthly')->get();
    }

    public static function rank(?string $slug): int
    {
        return self::RANK[$slug ?? ''] ?? 0;
    }

    public static function isUpgrade(?Plan $from, Plan $to): bool
    {
        if (! $from) {
            return true;
        }

        return self::rank($to->slug) > self::rank($from->slug);
    }

    /**
     * Feature comparison rows for the plan matrix (slug => cell value).
     *
     * @return list<array{label: string, monitor: string, guard: string, shield: string}>
     */
    public static function comparisonRows(): array
    {
        return [
            [
                'label'   => 'Uptime monitoring',
                'monitor' => 'Every 5 min',
                'guard'   => 'Every 5 min',
                'shield'  => 'Every 2 min',
            ],
            [
                'label'   => 'SSL & domain alerts',
                'monitor' => 'Daily checks',
                'guard'   => 'Daily checks',
                'shield'  => 'Daily checks',
            ],
            [
                'label'   => 'Monitor regions',
                'monitor' => 'US East',
                'guard'   => 'US + EU',
                'shield'  => 'US + EU + Asia',
            ],
            [
                'label'   => 'Backups',
                'monitor' => 'Monthly · 30 days',
                'guard'   => 'Daily · 90 days',
                'shield'  => 'Daily · 180 days',
            ],
            [
                'label'   => 'WordPress updates',
                'monitor' => 'You manage',
                'guard'   => 'We handle',
                'shield'  => 'We handle',
            ],
            [
                'label'   => 'Email support',
                'monitor' => 'Unlimited · 24h',
                'guard'   => 'Unlimited · 24h',
                'shield'  => 'Unlimited · priority',
            ],
            [
                'label'   => 'Phone support',
                'monitor' => '—',
                'guard'   => 'Included',
                'shield'  => 'Priority',
            ],
            [
                'label'   => 'Monthly reports',
                'monitor' => 'Yes',
                'guard'   => 'Yes',
                'shield'  => 'Yes',
            ],
        ];
    }

    /**
     * Short bullet list for a single plan card.
     *
     * @return list<string>
     */
    public static function bullets(Plan $plan): array
    {
        $f = $plan->features ?? [];

        return match ($plan->slug) {
            'monitor' => [
                'Uptime, SSL & domain monitoring (5 min)',
                'Monthly backups · 30-day retention',
                'Unlimited email support · reply within 24h',
                'Monthly health reports',
            ],
            'guard' => [
                'Everything in Monitor',
                'Daily backups · 90-day retention',
                'We manage WP core & plugin updates',
                'US + EU monitor regions',
                'Unlimited email + phone support',
            ],
            'shield' => [
                'Everything in Guard',
                '2-minute uptime checks',
                'Daily backups · 180-day retention',
                'US + EU + Asia monitor regions',
                'Priority email & phone support',
            ],
            default => [
                ucfirst((string) ($f['backup_frequency'] ?? 'monthly')) . ' backups',
            ],
        };
    }

    public static function cellForRow(array $row, string $slug): string
    {
        return $row[$slug] ?? '—';
    }
}
