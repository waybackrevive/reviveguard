<?php

namespace App\Filament\Resources;

use App\Enums\EventSeverity;
use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Monitoring & care';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Event')
                    ->schema([
                        TextEntry::make('severity')
                            ->badge()
                            ->color(fn (EventSeverity $state) => $state->color()),

                        TextEntry::make('source')
                            ->label('Source')
                            ->state(fn (Event $record) => $record->sourceLabel())
                            ->badge()
                            ->color(fn (Event $record) => $record->sourceBadgeColor()),

                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn ($state, Event $record) => $record->typeLabel())
                            ->color('gray'),

                        TextEntry::make('site.name')
                            ->label('Site')
                            ->placeholder('—'),

                        TextEntry::make('title')
                            ->columnSpanFull(),

                        TextEntry::make('message')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('resolved')
                            ->label('Resolved')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No'),

                        TextEntry::make('created_at')
                            ->label('Occurred')
                            ->dateTime(),

                        TextEntry::make('resolved_at')
                            ->label('Resolved at')
                            ->dateTime()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata')
                            ->label('')
                            ->state(fn (Event $record) => $record->metadata
                                ? json_encode($record->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : 'No metadata')
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(fn (Event $record) => empty($record->metadata)),
            ]);
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

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->state(fn (Event $record) => $record->sourceLabel())
                    ->color(fn (Event $record) => $record->sourceBadgeColor()),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn ($state, Event $record) => $record->typeLabel())
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

                Tables\Filters\SelectFilter::make('type')
                    ->options(fn () => Event::typeFilterOptions()),

                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'client' => 'Client-initiated',
                        'system' => 'System',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'client' => $query->where('type', 'client_action'),
                            'system' => $query->where('type', '!=', 'client_action'),
                            default  => $query,
                        };
                    }),

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
                    ->visible(fn (Event $record) => ! $record->resolved)
                    ->action(fn (Event $record) => $record->update([
                        'resolved'    => true,
                        'resolved_at' => now(),
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'view'  => Pages\ViewEvent::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with('site');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
