<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Support\AdminDashboard;
use Filament\Widgets\ChartWidget;

class ClientActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Client activity (last 14 days)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 1,
    ];

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label'           => 'Portal actions',
                    'data'            => AdminDashboard::dailyClientActivityCounts(),
                    'borderColor'     => '#059669',
                    'backgroundColor' => '#d1fae5',
                ],
            ],
            'labels' => AdminDashboard::last14DayLabels(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFooter(): ?string
    {
        return 'Plan changes, settings, tickets · View all: '.EventResource::getUrl('index');
    }
}
