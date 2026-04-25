<?php

namespace App\Filament\Widgets;

use App\Enums\SiteStatus;
use App\Models\Client;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteHealthOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        $totalSites  = Site::where('tenant_id', $tenantId)->count();
        $activeSites = Site::where('tenant_id', $tenantId)->where('status', SiteStatus::ACTIVE)->count();
        $downSites   = Site::where('tenant_id', $tenantId)->where('status', SiteStatus::DOWN)->count();
        $warnSites   = Site::where('tenant_id', $tenantId)->where('status', SiteStatus::WARNING)->count();
        $totalClients = Client::where('tenant_id', $tenantId)->where('is_active', true)->count();

        // Average 7d uptime across all active sites
        $avgUptime = Site::where('tenant_id', $tenantId)
            ->whereNotNull('uptime_7d')
            ->avg('uptime_7d');

        return [
            Stat::make('Total Sites', $totalSites)
                ->description('All monitored sites')
                ->icon('heroicon-o-globe-alt')
                ->color('gray'),

            Stat::make('Active / Healthy', $activeSites)
                ->description("{$downSites} down · {$warnSites} warning")
                ->icon('heroicon-o-check-circle')
                ->color($downSites > 0 ? 'danger' : ($warnSites > 0 ? 'warning' : 'success')),

            Stat::make('Avg 7d Uptime', $avgUptime ? number_format($avgUptime, 2) . '%' : '—')
                ->description('Across all monitored sites')
                ->icon('heroicon-o-signal')
                ->color($avgUptime && $avgUptime < 99 ? 'warning' : 'success'),

            Stat::make('Active Clients', $totalClients)
                ->description('Paying clients')
                ->icon('heroicon-o-users')
                ->color('primary'),
        ];
    }
}
