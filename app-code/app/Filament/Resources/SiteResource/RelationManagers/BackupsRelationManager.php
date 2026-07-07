<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Enums\BackupStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackupsRelationManager extends RelationManager
{
    protected static string $relationship = 'backups';

    protected static ?string $title = 'Backups';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (BackupStatus $state) => match ($state) {
                        BackupStatus::SUCCESS  => 'success',
                        BackupStatus::FAILED   => 'danger',
                        BackupStatus::RUNNING  => 'warning',
                        BackupStatus::EXPIRED  => 'gray',
                        default                => 'gray',
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 2).' MB' : '—'),

                Tables\Columns\TextColumn::make('started_at')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
