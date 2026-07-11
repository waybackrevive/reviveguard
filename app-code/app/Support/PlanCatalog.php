<?php

namespace App\Support;

use App\Models\Plan;

/**
 * Single source of truth for portal plan copy — aligned with PlanSeeder features
 * and reviveguard.com pricing ($49 / $99 / $179 per site).
 */
final class PlanCatalog
{
    /** @var array<string, int> */
    private const RANK = [
        'monitor' => 1,
        'guard'   => 2,
        'shield'  => 3,
    ];

    /** @return \Illuminate\Support\Collection<int, Plan> */
    public static function all(): \Illuminate\Support\Collection
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

    public static function isDowngrade(?Plan $from, Plan $to): bool
    {
        if (! $from) {
            return false;
        }

        return self::rank($to->slug) < self::rank($from->slug);
    }

    public static function canChangePlan(?Plan $from, Plan $to): bool
    {
        if (! $from || $from->id === $to->id) {
            return false;
        }

        return self::isUpgrade($from, $to) || self::isDowngrade($from, $to);
    }

    public static function bestFor(Plan $plan): string
    {
        return match ($plan->slug) {
            'monitor' => 'Sites that need visibility — know instantly if something breaks, without hands-on maintenance.',
            'guard'   => 'WordPress business sites where downtime or hacks would cost you leads and revenue.',
            'shield'  => 'High-value sites that need priority human care, faster checks, and longer backup history.',
            default   => '',
        };
    }

    public static function tagline(Plan $plan): string
    {
        return match ($plan->slug) {
            'monitor' => 'Full visibility, zero effort',
            'guard'   => 'Your site fully managed — hands off',
            'shield'  => 'Priority care for sites that matter most',
            default   => $plan->portalSummary(),
        };
    }

    /**
     * @return list<array{name: string, desc: string}>
     */
    public static function included(Plan $plan): array
    {
        $freq = match ($plan->slug) {
            'monitor' => 'Twice monthly',
            'guard'   => 'Weekly',
            'shield'  => 'Daily',
            default   => ucfirst($plan->backup_frequency),
        };
        $days = $plan->retention_days;

        return match ($plan->slug) {
            'monitor' => [
                ['name' => '24/7 uptime monitoring', 'desc' => 'Checks every 10 minutes with instant down alerts'],
                ['name' => 'SSL certificate alerts', 'desc' => 'Warned at 60, 30, and 7 days before expiry'],
                ['name' => 'Domain expiry alerts', 'desc' => 'Same 60 / 30 / 7-day warnings — never lose your domain'],
                ['name' => "{$freq} cloud backups", 'desc' => "{$days}-day retention, independent of your host · 2× per month"],
                ['name' => 'Backup verification', 'desc' => 'Checksum verified — status shown in your portal'],
                ['name' => 'Monthly health report', 'desc' => 'Uptime, backup & SSL summary in your portal'],
                ['name' => 'Unlimited email support', 'desc' => 'Reply within 24 hours on business days'],
                ['name' => 'Private client portal', 'desc' => 'Real-time status — WordPress & non-WP sites'],
            ],
            'guard' => [
                ['name' => 'Everything in Monitor', 'desc' => 'All monitoring, alerts, portal & reports'],
                ['name' => 'Weekly cloud backups', 'desc' => "{$days}-day retention with restore on request"],
                ['name' => 'Backup verification', 'desc' => 'Checksum verified — status shown in your portal'],
                ['name' => 'WordPress core updates', 'desc' => 'Applied weekly by our team with pre-update backup'],
                ['name' => 'Plugin & theme updates', 'desc' => 'Weekly updates with rollback if something breaks'],
                ['name' => 'Weekly malware scan', 'desc' => 'Plugin integrity & vulnerability detection'],
                ['name' => 'Monthly broken link audit', 'desc' => 'Internal link check — issues surfaced in reports'],
                ['name' => 'US + EU monitor regions', 'desc' => 'Checks from multiple locations'],
                ['name' => 'Email + phone support', 'desc' => 'Unlimited tickets · reply within 24h'],
            ],
            'shield' => [
                ['name' => 'Everything in Guard', 'desc' => 'All managed updates, scans & weekly Guard cadence — plus daily backups'],
                ['name' => '2-minute uptime checks', 'desc' => 'Fastest detection for critical sites (Guard: 5 min)'],
                ['name' => 'Daily backups · 180 days', 'desc' => 'Longer history for peace of mind'],
                ['name' => '4-hour emergency restore SLA', 'desc' => 'Tracked in portal when you open an emergency ticket'],
                ['name' => '2 hours content edits / month', 'desc' => 'Minor site text & image changes via support tickets'],
                ['name' => 'Dedicated account manager', 'desc' => 'Named contact shown in your portal'],
                ['name' => 'Quarterly security audit', 'desc' => 'Extended external scan in your monthly report'],
                ['name' => 'Quarterly SEO snapshot', 'desc' => 'Missing titles, slow pages & crawl errors summary'],
                ['name' => 'US + EU + Asia regions', 'desc' => 'Global monitoring coverage'],
                ['name' => 'Priority support', 'desc' => 'Same-business-day response · phone included'],
            ],
            default => [
                ['name' => 'Managed protection', 'desc' => $plan->portalSummary()],
            ],
        };
    }

    /**
     * Clear expectations — reduces disputes (like a professional scope doc).
     *
     * @return list<array{name: string, desc: string}>
     */
    public static function notIncluded(Plan $plan): array
    {
        return match ($plan->slug) {
            'monitor' => [
                ['name' => 'WordPress auto-updates', 'desc' => 'You manage updates, or upgrade to Guard'],
                ['name' => 'Malware removal', 'desc' => 'Available as Emergency Malware Cleanup add-on ($149)'],
                ['name' => 'Content or design changes', 'desc' => 'Available as Content Edit Hours add-on'],
                ['name' => 'Emergency restore SLA', 'desc' => 'One-time restore add-on ($99) — no SLA on Monitor'],
                ['name' => 'Phone support', 'desc' => 'Email only — upgrade to Guard for phone'],
            ],
            'guard' => [
                ['name' => '2-minute uptime checks', 'desc' => 'Shield plan — Guard uses 5-minute checks'],
                ['name' => 'Content edits included', 'desc' => 'Order Content Edit Hours as an add-on'],
                ['name' => '4-hour emergency restore SLA', 'desc' => 'Shield plan — or one-time restore add-on'],
                ['name' => 'SEO, ads, or e-commerce fixes', 'desc' => 'Contact us for a custom quote'],
            ],
            'shield' => [
                ['name' => 'Unlimited content redesigns', 'desc' => 'Minor edits via add-ons; major projects quoted separately'],
                ['name' => 'SEO campaigns or ad management', 'desc' => 'Not part of site care — ask for a custom scope'],
                ['name' => 'Custom plugin development', 'desc' => 'Quoted separately if needed'],
            ],
            default => [],
        };
    }

    /**
     * What you gain by upgrading — shown on upgrade cards.
     *
     * @return list<string>
     */
    public static function upgradeGains(?Plan $from, Plan $to): array
    {
        if (! $from) {
            return array_column(self::included($to), 'name');
        }

        $fromRank = self::rank($from->slug);
        $toRank   = self::rank($to->slug);

        if ($toRank <= $fromRank) {
            return [];
        }

        return match ($from->slug . '->' . $to->slug) {
            'monitor->guard' => [
                'Weekly backups (90-day retention) instead of twice monthly',
                'We manage WordPress core, plugin & theme updates',
                'Weekly malware scan + monthly broken link audit',
                'Automatic rollback if an update fails',
                'Phone support in addition to email',
                'US + EU monitoring regions',
            ],
            'monitor->shield' => [
                'Everything in Guard, plus:',
                '2-minute uptime checks (vs 10 min)',
                'Daily backups with 180-day retention',
                '4-hour emergency restore SLA',
                '2 hours of content edits included per month',
                'Dedicated account manager',
                'Quarterly security & SEO audits in reports',
                'Priority same-day support',
                'US + EU + Asia monitoring',
            ],
            'guard->shield' => [
                '2-minute uptime checks (vs 5 min)',
                'Daily backups · 180-day retention (vs weekly · 90 days)',
                '4-hour emergency restore SLA',
                '2 hours of content edits included per month',
                'Dedicated account manager',
                'Quarterly security & SEO audits in reports',
                'Priority same-day phone & email support',
                'Asia-Pacific monitoring region',
            ],
            default => ['All features of ' . $to->name],
        };
    }

    /** @return list<string> */
    public static function bullets(Plan $plan): array
    {
        return array_map(
            fn (array $item) => $item['name'] . ($item['desc'] ? ' — ' . $item['desc'] : ''),
            self::included($plan),
        );
    }

    /**
     * @return list<array{label: string, monitor: string, guard: string, shield: string}>
     */
    public static function comparisonRows(): array
    {
        return [
            ['label' => 'Price (per site)', 'monitor' => '$49/mo', 'guard' => '$99/mo', 'shield' => '$179/mo'],
            ['label' => 'Uptime checks', 'monitor' => 'Every 10 min', 'guard' => 'Every 5 min', 'shield' => 'Every 2 min'],
            ['label' => 'SSL & domain alerts', 'monitor' => 'Daily', 'guard' => 'Daily', 'shield' => 'Daily'],
            ['label' => 'Monitor regions', 'monitor' => 'US East', 'guard' => 'US + EU', 'shield' => 'US + EU + Asia'],
            ['label' => 'Backups', 'monitor' => '2× monthly · 30 days', 'guard' => 'Weekly · 90 days', 'shield' => 'Daily · 180 days'],
            ['label' => 'Backup verification', 'monitor' => 'Checksum + portal', 'guard' => 'Checksum + portal', 'shield' => 'Checksum + portal'],
            ['label' => 'WP updates', 'monitor' => 'You manage', 'guard' => 'Weekly · we handle', 'shield' => 'Weekly · we handle'],
            ['label' => 'Rollback on failed update', 'monitor' => '—', 'guard' => 'Auto-restore', 'shield' => 'Auto-restore'],
            ['label' => 'Malware / integrity scan', 'monitor' => '—', 'guard' => 'Weekly', 'shield' => 'Weekly'],
            ['label' => 'Broken link audit', 'monitor' => '—', 'guard' => 'Monthly', 'shield' => 'Monthly'],
            ['label' => 'Emergency restore SLA', 'monitor' => '— ($99 add-on)', 'guard' => '—', 'shield' => '4 hours'],
            ['label' => 'Content edits included', 'monitor' => '— (add-on)', 'guard' => '— (add-on)', 'shield' => '2 hrs / month'],
            ['label' => 'Quarterly security audit', 'monitor' => '—', 'guard' => '—', 'shield' => 'Yes'],
            ['label' => 'Quarterly SEO snapshot', 'monitor' => '—', 'guard' => '—', 'shield' => 'Yes'],
            ['label' => 'Account manager', 'monitor' => '—', 'guard' => '—', 'shield' => 'Yes'],
            ['label' => 'Email support', 'monitor' => 'Unlimited · 24h', 'guard' => 'Unlimited · 24h', 'shield' => 'Priority · same day'],
            ['label' => 'Phone support', 'monitor' => '—', 'guard' => 'Included', 'shield' => 'Priority'],
            ['label' => 'Monthly reports', 'monitor' => 'Yes', 'guard' => 'Yes', 'shield' => 'Yes'],
        ];
    }

    public static function cellForRow(array $row, string $slug): string
    {
        return $row[$slug] ?? '—';
    }

    public static function upgradeConfirmMessage(Plan $from, Plan $to): string
    {
        $price = number_format((float) $to->price_monthly, 0);

        return "Upgrade from {$from->name} to {$to->name} (\${$price}/mo)?\n\n"
            . "Your card on file will be charged the prorated difference today. New features activate immediately.\n\n"
            . "A receipt will appear under Billing & Invoices.";
    }

    public static function downgradeConfirmMessage(Plan $from, Plan $to): string
    {
        $price = number_format((float) $to->price_monthly, 0);

        return "Switch from {$from->name} to {$to->name} (\${$price}/mo)?\n\n"
            . "Your plan changes immediately. Unused time on {$from->name} is credited toward your next bill — no charge today.\n\n"
            . "Some features (e.g. faster checks or phone support) may stop right away.";
    }

    public static function planChangeConfirmMessage(Plan $from, Plan $to): string
    {
        return self::isDowngrade($from, $to)
            ? self::downgradeConfirmMessage($from, $to)
            : self::upgradeConfirmMessage($from, $to);
    }

    /**
     * Structured copy for the plan-change confirmation modal.
     *
     * @return array<string, mixed>
     */
    public static function planChangeModalData(Plan $from, Plan $to, ?string $siteName = null): array
    {
        $isUpgrade = self::isUpgrade($from, $to);
        $toPrice   = number_format((float) $to->price_monthly, 0);
        $fromPrice = number_format((float) $from->price_monthly, 0);

        return [
            'is_upgrade'    => $isUpgrade,
            'title'         => $isUpgrade ? "Upgrade to {$to->name}" : "Switch to {$to->name}",
            'site_name'     => $siteName,
            'from_name'     => $from->name,
            'to_name'       => $to->name,
            'from_price'    => $fromPrice,
            'to_price'      => $toPrice,
            'gains'         => $isUpgrade ? self::upgradeGains($from, $to) : [],
            'billing_note'  => $isUpgrade
                ? 'Your card on file is charged the prorated difference today. New features activate immediately and a receipt appears under Billing & Invoices.'
                : 'No charge today. Unused time on your current plan is credited toward your next bill. A confirmation appears under Billing & Invoices.',
            'warning'       => $isUpgrade
                ? null
                : 'Some features (faster uptime checks, phone support, longer backup history) may stop right away.',
            'confirm_label' => $isUpgrade
                ? "Confirm upgrade — \${$toPrice}/mo"
                : "Confirm switch — \${$toPrice}/mo",
        ];
    }
}
