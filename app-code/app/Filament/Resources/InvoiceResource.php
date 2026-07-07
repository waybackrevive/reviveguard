<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Clients & revenue';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Invoices';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('summary')
                    ->label('Description')
                    ->state(fn (Invoice $record) => $record->summaryDescription())
                    ->limit(40),

                Tables\Columns\TextColumn::make('formatted_total')
                    ->label('Amount')
                    ->state(fn (Invoice $record) => $record->formatted_total)
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('total_cents', $direction)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state, Invoice $record) => $record->statusLabel())
                    ->color(fn (Invoice $record) => match ($record->status) {
                        'paid' => 'success',
                        'void' => 'gray',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid'   => 'Paid',
                        'unpaid' => 'Unpaid',
                        'void'   => 'Void',
                    ]),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn () => Client::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Invoice $record) => $record->receiptUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => $record->receiptUrl() !== null),

                Tables\Actions\Action::make('stripe')
                    ->label('Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Invoice $record) => $record->stripeDashboardUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record) => $record->stripeDashboardUrl() !== null),

                Tables\Actions\Action::make('sync_client')
                    ->label('Sync client')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Sync invoices from Stripe')
                    ->modalDescription(fn (Invoice $record) => 'Import any missing paid invoices for '.$record->client?->name.' from Stripe.')
                    ->action(function (Invoice $record): void {
                        if (! $record->client) {
                            Notification::make()->title('No client on invoice')->warning()->send();

                            return;
                        }

                        $count = app(InvoiceService::class)->syncInvoicesForClient($record->client);

                        Notification::make()
                            ->title($count > 0 ? "Imported {$count} invoice(s)" : 'Already up to date')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
