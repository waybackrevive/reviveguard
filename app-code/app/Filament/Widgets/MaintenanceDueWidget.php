<?php

namespace App\Filament\Widgets;

use App\Services\MaintenanceScheduler;
use Filament\Widgets\Widget;

class MaintenanceDueWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.maintenance-due';

    /** @return array{backups: int, updates: int, malware_scans: int, broken_links: int, quarterly: int, total: int} */
    public function getCounts(): array
    {
        $counts = app(MaintenanceScheduler::class)->dueCounts();
        $counts['total'] = array_sum([
            $counts['backups'],
            $counts['updates'],
            $counts['malware_scans'],
            $counts['broken_links'],
            $counts['quarterly'],
        ]);

        return $counts;
    }
}
