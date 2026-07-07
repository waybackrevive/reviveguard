<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UptimeProbesRelationManager extends RelationManager
{
    protected static string $relationship = 'uptimeProbes';

    protected static ?string $title = 'Probes (last 24h)';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('checked_at', '>=', now()->subDay())
                ->orderByDesc('checked_at'))
            ->columns([
                Tables\Columns\IconColumn::make('is_up')
                    ->boolean()
                    ->label('Up'),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('HTTP')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('response_ms')
                    ->label('ms')
                    ->suffix(' ms')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Checked')
                    ->since()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([25, 50, 100])
            ->defaultSort('checked_at', 'desc');
    }
}
