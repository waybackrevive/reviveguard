<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Reports';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'failed'    => 'danger',
                        'pending'   => 'gray',
                        default     => 'warning',
                    }),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 2).' MB' : '—'),

                Tables\Columns\IconColumn::make('email_sent')
                    ->boolean()
                    ->label('Emailed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(100)
                    ->wrap()
                    ->placeholder('—')
                    ->color('danger'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
