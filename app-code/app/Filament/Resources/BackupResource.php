<?php

namespace App\Filament\Resources;

use App\Enums\BackupStatus;
use App\Filament\Resources\BackupResource\Pages;
use App\Models\Backup;
use App\Models\Site;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Monitoring & care';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Backups';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.client.name')
                    ->label('Client')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray'    => BackupStatus::PENDING->value,
                        'primary' => BackupStatus::RUNNING->value,
                        'success' => BackupStatus::SUCCESS->value,
                        'danger'  => BackupStatus::FAILED->value,
                        'warning' => BackupStatus::EXPIRED->value,
                    ]),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 2) . ' MB' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('b2_file_key')
                    ->label('B2 Key')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(BackupStatus::class),

                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->options(fn () => Site::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Backup $record) => $record->signedDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Backup $record) => $record->canDownload()),

                Tables\Actions\Action::make('site')
                    ->label('Site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (Backup $record) => SiteResource::getUrl('edit', ['record' => $record->site_id])),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackups::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['site', 'site.client']);
    }

    public static function canCreate(): bool
    {
        return false; // Backups are created by the agent, not manually
    }
}
