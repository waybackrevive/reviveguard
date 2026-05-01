<?php

namespace App\Filament\Resources\ClientInviteResource\Pages;

use App\Filament\Resources\ClientInviteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientInvites extends ListRecords
{
    protected static string $resource = ClientInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New invite'),
        ];
    }
}
