<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Enums\EventSeverity;
use App\Models\Event;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Recent events';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (EventSeverity $state) => $state->color()),

                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('resolved')
                    ->boolean()
                    ->label('Resolved'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Occurred')
                    ->since()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(20)
            ->paginated([10, 20])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Event $record) => ! $record->resolved)
                    ->action(fn (Event $record) => $record->update([
                        'resolved'    => true,
                        'resolved_at' => now(),
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
