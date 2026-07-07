<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Ticket;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Support tickets';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('site')->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open', 'in_progress' => 'warning',
                        'resolved', 'closed' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Reply')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (Ticket $record) => \App\Filament\Resources\TicketResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
