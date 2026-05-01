<?php

namespace App\Filament\Resources\SiteEvaluationResource\Pages;

use App\Filament\Resources\SiteEvaluationResource;
use App\Models\SiteEvaluation;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListSiteEvaluations extends ListRecords
{
    protected static string $resource = SiteEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Shows "X / 26 accepted this month" as a subtitle under the page heading.
     */
    public function getSubheading(): ?string
    {
        $cap      = (int) config('app.evaluation_monthly_cap', 26);
        $slot     = now()->format('Y-m');
        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');

        $accepted = SiteEvaluation::where('tenant_id', $tenantId)
            ->where('month_slot', $slot)
            ->whereNotIn('status', ['declined', 'expired'])
            ->count();

        return "{$accepted} / {$cap} slots used this month (" . now()->format('F Y') . ")";
    }
}
