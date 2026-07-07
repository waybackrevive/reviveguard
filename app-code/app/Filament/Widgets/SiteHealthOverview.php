<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\TicketResource;
use App\Support\AdminDashboard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteHealthOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalSites     = AdminDashboard::totalSites();
        $payingSites    = AdminDashboard::payingSitesCount();
        $estimatedMrr   = AdminDashboard::estimatedMrr();
        $avgUptime      = AdminDashboard::avgUptime7dPaidMonitored();
        $openOps        = AdminDashboard::openOpsCount();
        $openTickets    = AdminDashboard::openTicketsCount();
        $criticalEvents = AdminDashboard::unresolvedCriticalEventsCount();
        $breakdown      = AdminDashboard::portalStatusDescription();

        $issueCount = AdminDashboard::portalStatusCounts()['issue'];

        return [
            Stat::make('Sites', $totalSites)
                ->description($breakdown)
                ->icon('heroicon-o-globe-alt')
                ->color($issueCount > 0 ? 'danger' : 'gray')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Est. MRR', '$'.number_format($estimatedMrr, 0))
                ->description($payingSites.' active subscription'.($payingSites === 1 ? '' : 's'))
                ->icon('heroicon-o-banknotes')
                ->color($estimatedMrr > 0 ? 'success' : 'gray')
                ->url(SubscriptionResource::getUrl('index')),

            Stat::make('Avg 7d uptime', $avgUptime !== null ? number_format($avgUptime, 2).'%' : '—')
                ->description('Paid sites with monitoring on')
                ->icon('heroicon-o-signal')
                ->color($avgUptime !== null && $avgUptime < 99 ? 'warning' : 'success')
                ->url(SiteResource::getUrl('index')),

            Stat::make('Open ops', $openOps)
                ->description("{$openTickets} tickets · {$criticalEvents} critical events")
                ->icon('heroicon-o-bell-alert')
                ->color($openOps > 0 ? 'warning' : 'success')
                ->url($openTickets > 0
                    ? TicketResource::getUrl('index')
                    : EventResource::getUrl('index')),
        ];
    }
}
