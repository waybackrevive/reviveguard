<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Client;
use App\Models\Report;
use App\Models\Site;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Monitoring & care';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Reports';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Period')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Report $record) => SiteResource::getUrl('edit', ['record' => $record->site_id])),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state, Report $record) => $record->statusLabel())
                    ->color(fn (Report $record) => $record->statusBadgeColor()),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1048576, 2).' MB' : '—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('email_sent')
                    ->boolean()
                    ->label('Emailed')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'generating' => 'Generating',
                        'completed'  => 'Completed',
                        'ready'      => 'Ready',
                        'failed'     => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->options(fn () => Site::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn () => Client::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Report $record) => $record->signedDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Report $record) => $record->canDownload()),

                Tables\Actions\Action::make('client')
                    ->label('Client')
                    ->icon('heroicon-o-user')
                    ->url(fn (Report $record) => ClientResource::getUrl('edit', ['record' => $record->client_id])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['site', 'client']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
