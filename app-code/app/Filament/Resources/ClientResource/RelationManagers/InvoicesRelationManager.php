<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Invoice;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('issued_at'))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(),

                Tables\Columns\TextColumn::make('summaryDescription')
                    ->label('Description')
                    ->state(fn (Invoice $record) => $record->summaryDescription())
                    ->limit(40),

                Tables\Columns\TextColumn::make('formatted_total')
                    ->label('Total')
                    ->state(fn (Invoice $record) => $record->formatted_total),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state, Invoice $record) => $record->statusLabel())
                    ->color(fn (Invoice $record) => match ($record->status) {
                        'paid'   => 'success',
                        'void'   => 'gray',
                        default  => 'warning',
                    }),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('receipt_link')
                    ->label('Receipt')
                    ->state(fn (Invoice $record) => $record->receiptUrl() ? 'Open' : '—')
                    ->url(fn (Invoice $record) => $record->receiptUrl())
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->defaultSort('issued_at', 'desc');
    }
}
