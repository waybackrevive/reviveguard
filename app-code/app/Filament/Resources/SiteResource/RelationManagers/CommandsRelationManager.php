<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommandsRelationManager extends RelationManager
{
    protected static string $relationship = 'commands';

    protected static ?string $title = 'Active commands';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereIn('status', [
                    CommandStatus::PENDING,
                    CommandStatus::SENT,
                    CommandStatus::EXECUTING,
                ])
                ->orderByDesc('queued_at'))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (CommandType $state) => $state->label())
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CommandStatus $state) => $state->label())
                    ->color(fn (CommandStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('queued_at')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->since()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('error_message')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->defaultSort('queued_at', 'desc');
    }
}
