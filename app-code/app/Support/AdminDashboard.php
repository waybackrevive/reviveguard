<?php

namespace App\Support;

use App\Enums\EventSeverity;
use App\Enums\SiteStatus;
use App\Models\Event;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminDashboard
{
    public static function tenantId(): string
    {
        return (string) config('app.tenant_id');
    }

    public static function sitesQuery(): Builder
    {
        return Site::query()
            ->where('tenant_id', static::tenantId())
            ->with(['subscription', 'client']);
    }

    /**
     * Portal-aligned status buckets — keys sum to total site count.
     *
     * @return array{protected: int, setup: int, checkout: int, warning: int, issue: int, paused: int}
     */
    public static function portalStatusCounts(): array
    {
        $counts = [
            'protected' => 0,
            'setup'     => 0,
            'checkout'  => 0,
            'warning'   => 0,
            'issue'     => 0,
            'paused'    => 0,
        ];

        foreach (static::sitesQuery()->get() as $site) {
            $key = $site->portalStatusKey();
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }

            if ($site->monitoring_paused && $site->hasPaidSubscription()) {
                $counts['paused']++;
            }
        }

        return $counts;
    }

    public static function totalSites(): int
    {
        return static::sitesQuery()->count();
    }

    public static function payingSitesCount(): int
    {
        return Subscription::query()
            ->where('tenant_id', static::tenantId())
            ->where(function (Builder $query): void {
                $query->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            })
            ->count();
    }

    public static function estimatedMrr(): float
    {
        return (float) Subscription::query()
            ->where('tenant_id', static::tenantId())
            ->where(function (Builder $query): void {
                $query->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            })
            ->with('plan')
            ->get()
            ->sum(fn (Subscription $subscription) => (float) ($subscription->plan?->price_monthly ?? 0));
    }

    public static function avgUptime7dPaidMonitored(): ?float
    {
        $avg = static::sitesQuery()
            ->whereNotNull('subscription_id')
            ->where('monitoring_paused', false)
            ->whereNotNull('uptime_7d')
            ->avg('uptime_7d');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    public static function openTicketsCount(): int
    {
        return Ticket::query()
            ->where('tenant_id', static::tenantId())
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
    }

    public static function unresolvedCriticalEventsCount(): int
    {
        return Event::query()
            ->where('tenant_id', static::tenantId())
            ->where('resolved', false)
            ->where('severity', EventSeverity::CRITICAL->value)
            ->count();
    }

    public static function openOpsCount(): int
    {
        return static::openTicketsCount() + static::unresolvedCriticalEventsCount();
    }

    public static function portalStatusDescription(): string
    {
        $counts = static::portalStatusCounts();
        $parts  = [];

        foreach ([
            'protected' => 'protected',
            'setup'     => 'setup',
            'checkout'  => 'checkout',
            'warning'   => 'attention',
            'issue'     => 'down',
        ] as $key => $label) {
            if ($counts[$key] > 0) {
                $parts[] = "{$counts[$key]} {$label}";
            }
        }

        if ($counts['paused'] > 0) {
            $parts[] = "{$counts['paused']} paused";
        }

        return $parts !== [] ? implode(' · ', $parts) : 'No sites yet';
    }

    /**
     * @return Collection<int, array{type: string, label: string, detail: string, site_id: ?string, ticket_id: ?string}>
     */
    public static function attentionItems(): Collection
    {
        $tenantId = static::tenantId();
        $items    = collect();

        static::sitesQuery()
            ->where('status', SiteStatus::DOWN)
            ->whereNotNull('subscription_id')
            ->get()
            ->filter(fn (Site $site) => $site->hasPaidSubscription())
            ->each(function (Site $site) use ($items): void {
                $items->push([
                    'type'      => 'down',
                    'label'     => $site->displayName(),
                    'detail'    => 'Paid site is down',
                    'site_id'   => $site->id,
                    'ticket_id' => null,
                ]);
            });

        $expiryCutoff = now()->addDays(30)->startOfDay();

        static::sitesQuery()
            ->whereNotNull('subscription_id')
            ->where(function (Builder $query) use ($expiryCutoff): void {
                $query->where(function (Builder $inner) use ($expiryCutoff): void {
                    $inner->whereNotNull('ssl_expires_at')
                        ->where('ssl_expires_at', '<=', $expiryCutoff);
                })->orWhere(function (Builder $inner) use ($expiryCutoff): void {
                    $inner->whereNotNull('domain_expires_at')
                        ->where('domain_expires_at', '<=', $expiryCutoff);
                });
            })
            ->get()
            ->filter(fn (Site $site) => $site->hasPaidSubscription())
            ->each(function (Site $site) use ($items): void {
                $sslDays = $site->sslExpiresInDays();
                if ($sslDays !== null && $sslDays < 30) {
                    $items->push([
                        'type'      => 'ssl',
                        'label'     => $site->displayName(),
                        'detail'    => 'SSL expires in '.$sslDays.' days',
                        'site_id'   => $site->id,
                        'ticket_id' => null,
                    ]);
                }

                $domainDays = $site->domainExpiresInDays();
                if ($domainDays !== null && $domainDays < 30) {
                    $items->push([
                        'type'      => 'domain',
                        'label'     => $site->displayName(),
                        'detail'    => 'Domain expires in '.$domainDays.' days',
                        'site_id'   => $site->id,
                        'ticket_id' => null,
                    ]);
                }
            });

        Ticket::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('created_at', '<', now()->subDay())
            ->with('client')
            ->orderBy('created_at')
            ->get()
            ->each(function (Ticket $ticket) use ($items): void {
                $items->push([
                    'type'      => 'ticket',
                    'label'     => $ticket->subject,
                    'detail'    => ($ticket->client?->name ?? 'Client').' · open '.$ticket->created_at->diffForHumans(),
                    'site_id'   => $ticket->site_id,
                    'client_id' => $ticket->client_id,
                    'ticket_id' => $ticket->id,
                ]);
            });

        static::sitesQuery()
            ->whereNotNull('last_seen_at')
            ->where('created_at', '<', now()->subDays(7))
            ->get()
            ->filter(fn (Site $site) => $site->portalStatusKey() === 'checkout')
            ->each(function (Site $site) use ($items): void {
                $items->push([
                    'type'      => 'checkout',
                    'label'     => $site->displayName(),
                    'detail'    => 'Checkout not completed · agent connected',
                    'site_id'   => $site->id,
                    'ticket_id' => null,
                ]);
            });

        $priority = ['down' => 1, 'ssl' => 2, 'domain' => 3, 'ticket' => 4, 'checkout' => 5];

        return $items
            ->sortBy(fn (array $item) => $priority[$item['type']] ?? 99)
            ->values();
    }

    /** @return array<int, string> */
    public static function last14DayLabels(): array
    {
        return collect(range(13, 0))
            ->map(fn (int $i) => now()->startOfDay()->subDays($i)->format('M d'))
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public static function dailyEventCounts(string $severity, ?string $type = null, ?string $excludeType = null): array
    {
        $tenantId = static::tenantId();

        return collect(range(13, 0))
            ->map(function (int $i) use ($tenantId, $severity, $type, $excludeType): int {
                $day = now()->startOfDay()->subDays($i);

                $query = Event::query()
                    ->where('tenant_id', $tenantId)
                    ->where('severity', $severity)
                    ->whereDate('created_at', $day);

                if ($type !== null) {
                    $query->where('type', $type);
                }

                if ($excludeType !== null) {
                    $query->where('type', '!=', $excludeType);
                }

                return $query->count();
            })
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public static function dailyClientActivityCounts(): array
    {
        $tenantId = static::tenantId();

        return collect(range(13, 0))
            ->map(function (int $i) use ($tenantId): int {
                $day = now()->startOfDay()->subDays($i);

                return Event::query()
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'client_action')
                    ->whereDate('created_at', $day)
                    ->count();
            })
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public static function dailyNewSubscriptionCounts(): array
    {
        $tenantId = static::tenantId();

        return collect(range(13, 0))
            ->map(function (int $i) use ($tenantId): int {
                $day = now()->startOfDay()->subDays($i);

                return Subscription::query()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('created_at', $day)
                    ->count();
            })
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public static function dailyProbeFailureCounts(): array
    {
        $tenantId = static::tenantId();

        return collect(range(13, 0))
            ->map(function (int $i) use ($tenantId): int {
                $day = now()->startOfDay()->subDays($i);

                return Event::query()
                    ->where('tenant_id', $tenantId)
                    ->where('type', 'uptime_probe')
                    ->where('severity', EventSeverity::CRITICAL->value)
                    ->whereDate('created_at', $day)
                    ->count();
            })
            ->values()
            ->all();
    }
}
