<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_all')
                ->label('Sync from Stripe')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync all client invoices')
                ->modalDescription('Imports missing paid invoices from Stripe for every client with a Stripe customer ID.')
                ->action(function (): void {
                    $count = app(InvoiceService::class)->syncAllTenantInvoices();

                    Notification::make()
                        ->title($count > 0 ? "Imported {$count} invoice(s)" : 'All invoices already synced')
                        ->success()
                        ->send();
                }),
        ];
    }
}
