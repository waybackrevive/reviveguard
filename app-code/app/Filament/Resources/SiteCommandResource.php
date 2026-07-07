<?php

namespace App\Filament\Resources;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Filament\Resources\SiteCommandResource\Pages;
use App\Models\Site;
use App\Models\SiteCommand;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiteCommandResource extends Resource
{
    protected static ?string $model = SiteCommand::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Monitoring & care';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Command queue';

    protected static ?string $slug = 'site-commands';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->url(fn (SiteCommand $record) => SiteResource::getUrl('edit', ['record' => $record->site_id])),

                Tables\Columns\TextColumn::make('site.client.name')
                    ->label('Client')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (CommandType $state) => $state->label())
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CommandStatus $state) => $state->label())
                    ->color(fn (CommandStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('queued_at')
                    ->label('Queued')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CommandStatus::class),

                Tables\Filters\SelectFilter::make('type')
                    ->options(CommandType::class),

                Tables\Filters\Filter::make('active')
                    ->label('Active only')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereIn('status', [
                        CommandStatus::PENDING,
                        CommandStatus::SENT,
                        CommandStatus::EXECUTING,
                    ]))
                    ->default(true),

                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->options(fn () => Site::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('site')
                    ->label('Site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (SiteCommand $record) => SiteResource::getUrl('edit', ['record' => $record->site_id])),
            ])
            ->defaultSort('queued_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteCommands::route('/'),
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
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', [
                CommandStatus::PENDING,
                CommandStatus::SENT,
                CommandStatus::EXECUTING,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
