<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddonOrderResource\Pages;
use App\Models\AddonOrder;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AddonOrderResource extends Resource
{
    protected static ?string $model = AddonOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Clients & revenue';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Add-on orders';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('addon_name')
                    ->label('Add-on')
                    ->searchable()
                    ->description(fn (AddonOrder $record) => $record->addon_slug),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->state(fn (AddonOrder $record) => $record->formattedAmount() ?? $record->price_label ?? '—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state, AddonOrder $record) => $record->statusLabel())
                    ->color(fn (AddonOrder $record) => $record->filamentStatusBadgeColor()),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ordered')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'awaiting_payment' => 'Awaiting payment',
                        'pending'          => 'Pending',
                        'in_progress'      => 'In progress',
                        'completed'        => 'Completed',
                        'cancelled'        => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('addon_slug')
                    ->label('Add-on')
                    ->options(fn () => collect(config('reviveguard_addons', []))
                        ->pluck('name', 'slug')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn () => Client::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('client')
                    ->label('Client')
                    ->icon('heroicon-o-user')
                    ->url(fn (AddonOrder $record) => ClientResource::getUrl('edit', ['record' => $record->client_id])),

                Tables\Actions\Action::make('site')
                    ->label('Site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (AddonOrder $record) => $record->site_id
                        ? SiteResource::getUrl('edit', ['record' => $record->site_id])
                        : null)
                    ->visible(fn (AddonOrder $record) => $record->site_id !== null),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddonOrders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client', 'site']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
