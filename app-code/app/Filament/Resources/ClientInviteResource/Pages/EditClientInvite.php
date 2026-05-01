<?php

namespace App\Filament\Resources\ClientInviteResource\Pages;

use App\Filament\Resources\ClientInviteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientInvite extends EditRecord
{
    protected static string $resource = ClientInviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
