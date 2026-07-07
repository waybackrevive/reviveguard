<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SubscriptionResource;
use App\Support\AdminDashboard;
use Filament\Widgets\ChartWidget;

class NewSubscriptionsChart extends ChartWidget
{
    protected static ?string $heading = 'New subscriptions (last 14 days)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 1,
    ];

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label'           => 'New subs',
                    'data'            => AdminDashboard::dailyNewSubscriptionCounts(),
                    'borderColor'     => '#2563eb',
                    'backgroundColor' => '#dbeafe',
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
        return 'By created_at · View all: '.SubscriptionResource::getUrl('index');
    }
}
