<?php

namespace App\Filament\Resources\SiteEvaluationResource\Pages;

use App\Filament\Resources\SiteEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteEvaluations extends ListRecords
{
    protected static string $resource = SiteEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
