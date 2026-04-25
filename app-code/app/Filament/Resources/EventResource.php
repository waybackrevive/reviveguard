<?php

namespace App\Filament\Resources;

use App\Enums\EventSeverity;
use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    // Read-only — no create/edit actions
    public static function form(Form $form): Form
    {
        return $form->schema([]); // no form needed (read-only)
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (EventSeverity $state) => $state->color())
                    ->icon(fn (EventSeverity $state) => $state->icon())
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('resolved')
                    ->boolean()
                    ->label('Resolved')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Occurred')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options(EventSeverity::class),

                Tables\Filters\TernaryFilter::make('resolved')
                    ->label('Resolved'),

                Tables\Filters\SelectFilter::make('site')
                    ->relationship('site', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Event $r) => ! $r->resolved)
                    ->action(fn (Event $r) => $r->update([
                        'resolved'    => true,
                        'resolved_at' => now(),
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // auto-refresh every 60 seconds
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', '00000000-0000-0000-0000-000000000001')
            ->with('site');
    }

    // No create button
    public static function canCreate(): bool
    {
        return false;
    }

    // No delete from this resource
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
