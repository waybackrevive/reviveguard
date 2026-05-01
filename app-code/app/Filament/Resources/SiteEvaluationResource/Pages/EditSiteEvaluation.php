<?php

namespace App\Filament\Resources\SiteEvaluationResource\Pages;

use App\Filament\Resources\SiteEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteEvaluation extends EditRecord
{
    protected static string $resource = SiteEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
