<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resolve')
                ->label('Mark resolved')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => ! $this->record->resolved)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'resolved'    => true,
                        'resolved_at' => now(),
                    ]);

                    $this->refreshFormData(['resolved', 'resolved_at']);
                }),

            Actions\Action::make('site')
                ->label('Open site')
                ->icon('heroicon-o-globe-alt')
                ->url(fn () => $this->record->site_id
                    ? \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $this->record->site_id])
                    : null)
                ->visible(fn () => $this->record->site_id !== null),
        ];
    }
}
