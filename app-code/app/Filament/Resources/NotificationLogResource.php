<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationLogResource\Pages;
use App\Models\NotificationLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Notification log';

    protected static ?string $modelLabel = 'notification';

    protected static ?string $pluralModelLabel = 'notifications';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipient')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (NotificationLog $record) => $record->subject),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('—')
                    ->url(fn (NotificationLog $record) => $record->client_id
                        ? ClientResource::getUrl('edit', ['record' => $record->client_id])
                        : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('—')
                    ->url(fn (NotificationLog $record) => $record->site_id
                        ? SiteResource::getUrl('edit', ['record' => $record->site_id])
                        : null)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'sent',
                        'danger'  => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('resend_message_id')
                    ->label('Resend ID')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent'   => 'Sent',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options(fn () => NotificationLog::query()
                        ->where('tenant_id', config('app.tenant_id'))
                        ->whereNotNull('type')
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (NotificationLog $record) => $record->subject)
                    ->modalContent(fn (NotificationLog $record) => view('filament.notification-log-detail', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationLogs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client', 'site']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
