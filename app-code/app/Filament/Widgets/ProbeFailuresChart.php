<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Support\AdminDashboard;
use Filament\Widgets\ChartWidget;

class ProbeFailuresChart extends ChartWidget
{
    protected static ?string $heading = 'Probe failures (last 14 days)';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 1,
    ];

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label'           => 'Site down',
                    'data'            => AdminDashboard::dailyProbeFailureCounts(),
                    'borderColor'     => '#dc2626',
                    'backgroundColor' => '#fee2e2',
                ],
            ],
            'labels' => AdminDashboard::last14DayLabels(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFooter(): ?string
    {
        return 'Critical uptime_probe events · View all: '.EventResource::getUrl('index');
    }
}
