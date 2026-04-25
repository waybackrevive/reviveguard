<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SiteEventsChart extends ChartWidget
{
    protected static ?string $heading = 'Events (Last 14 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $tenantId = '00000000-0000-0000-0000-000000000001';

        $days   = collect(range(13, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $labels = $days->map(fn ($d) => $d->format('M d'))->toArray();

        $critical = $days->map(fn ($d) => Event::where('tenant_id', $tenantId)
            ->where('severity', 'critical')
            ->whereDate('created_at', $d)
            ->count()
        )->toArray();

        $warnings = $days->map(fn ($d) => Event::where('tenant_id', $tenantId)
            ->where('severity', 'warning')
            ->whereDate('created_at', $d)
            ->count()
        )->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Critical',
                    'data'            => $critical,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => '#fee2e2',
                ],
                [
                    'label'           => 'Warning',
                    'data'            => $warnings,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => '#fef3c7',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
