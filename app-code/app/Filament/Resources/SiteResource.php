<?php

namespace App\Filament\Resources;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Filament\Resources\SiteResource\Pages;
use App\Jobs\GenerateSiteReport;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Report;
use App\Models\Site;
use App\Models\SiteCommand;
use App\Services\NotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Clients';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site Info')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(fn () => Client::where('tenant_id', '00000000-0000-0000-0000-000000000001')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn () => Plan::where('tenant_id', '00000000-0000-0000-0000-000000000001')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Friendly name, e.g. "Client Main Site"'),

                    Forms\Components\TextInput::make('url')
                        ->url()
                        ->required()
                        ->maxLength(255)
                        ->prefix('https://')
                        ->helperText('Full URL including https://'),

                    Forms\Components\Select::make('type')
                        ->options(SiteType::class)
                        ->default(SiteType::WORDPRESS)
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options(SiteStatus::class)
                        ->default(SiteStatus::PENDING)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Agent Token')
                ->schema([
                    Forms\Components\Placeholder::make('agent_token_last4')
                        ->label('Token (last 4)')
                        ->content(fn ($record) => $record?->agent_token_last4
                            ? '····' . $record->agent_token_last4
                            : 'Generated on save'),

                    Forms\Components\Placeholder::make('agent_info')
                        ->label('Agent Version')
                        ->content(fn ($record) => $record?->agent_version ?? '—'),
                ])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->url(fn (Site $r) => $r->url)
                    ->openUrlInNewTab()
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (SiteStatus $state) => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uptime_7d')
                    ->label('7d Uptime')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float)$state, 1) . '%' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('SSL Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->diffInDays() < 14 ? 'danger' : null),

                Tables\Columns\TextColumn::make('domain_expires_at')
                    ->label('Domain Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->diffInDays() < 30 ? 'warning' : null),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('agent_token_last4')
                    ->label('Token')
                    ->formatStateUsing(fn ($state) => $state ? '····' . $state : '—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SiteStatus::class),

                Tables\Filters\SelectFilter::make('type')
                    ->options(SiteType::class),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn () => \App\Models\Client::orderBy('name')->pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('regenerate_token')
                    ->label('Regen Token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will invalidate the existing agent token. The WordPress plugin will stop working until updated.')
                    ->action(fn (Site $record) => static::regenerateToken($record)),

                Tables\Actions\Action::make('generate_report')
                    ->label('Generate Report')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Monthly Report')
                    ->modalDescription('This will generate the report for the previous calendar month and email it to the client.')
                    ->action(function (Site $record): void {
                        $period = \Carbon\Carbon::now()->subMonthNoOverflow()->format('Y-m');
                        GenerateSiteReport::dispatch($record->id, $period);
                        \Filament\Notifications\Notification::make()
                            ->title('Report queued')
                            ->body("Generating {$period} report for {$record->name}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resend_report')
                    ->label('Resend Report')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Latest Report')
                    ->modalDescription('This will resend the most recently completed report PDF to the client.')
                    ->action(function (Site $record): void {
                        $report = Report::where('site_id', $record->id)
                            ->where('status', 'completed')
                            ->whereNotNull('b2_file_key')
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if (! $report) {
                            \Filament\Notifications\Notification::make()
                                ->title('No report found')
                                ->body('No completed report with a PDF exists for this site.')
                                ->warning()
                                ->send();
                            return;
                        }

                        GenerateSiteReport::dispatch($record->id, $report->period);

                        \Filament\Notifications\Notification::make()
                            ->title('Report email queued')
                            ->body("Re-generating and emailing the {$report->period} report.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(fn (Site $record) => static::deleteUptimeMonitor($record)),

                Tables\Actions\Action::make('trigger_backup')
                    ->label('Run Backup')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Trigger Backup')
                    ->modalDescription('This will queue a backup command. The agent will pick it up on the next heartbeat (within 5 min).')
                    ->action(function (Site $record): void {
                        $existing = SiteCommand::where('site_id', $record->id)
                            ->where('type', CommandType::RUN_BACKUP)
                            ->where('status', CommandStatus::PENDING)
                            ->exists();

                        if ($existing) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already queued')
                                ->body('A backup command is already pending for this site.')
                                ->warning()
                                ->send();
                            return;
                        }

                        SiteCommand::create([
                            'tenant_id' => $record->tenant_id,
                            'site_id'   => $record->id,
                            'type'      => CommandType::RUN_BACKUP,
                            'status'    => CommandStatus::PENDING,
                            'params'    => [],
                            'queued_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Backup queued')
                            ->body("Backup command queued for {$record->name}. Agent will pick it up within 5 minutes.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('trigger_updates')
                    ->label('Run Updates')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Trigger WP Updates')
                    ->modalDescription('This will queue a WordPress core + plugin update command. Guard and Shield plans only.')
                    ->action(function (Site $record): void {
                        $existing = SiteCommand::where('site_id', $record->id)
                            ->where('type', CommandType::RUN_WP_UPDATES)
                            ->where('status', CommandStatus::PENDING)
                            ->exists();

                        if ($existing) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already queued')
                                ->body('An update command is already pending for this site.')
                                ->warning()
                                ->send();
                            return;
                        }

                        SiteCommand::create([
                            'tenant_id' => $record->tenant_id,
                            'site_id'   => $record->id,
                            'type'      => CommandType::RUN_WP_UPDATES,
                            'status'    => CommandStatus::PENDING,
                            'params'    => [],
                            'queued_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Update queued')
                            ->body("WP update command queued for {$record->name}. Agent will pick it up within 5 minutes.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function regenerateToken(Site $site): void
    {
        $raw   = bin2hex(random_bytes(32)); // 64-char hex token
        $site->update([
            'agent_token'      => hash('sha256', $raw),
            'agent_token_last4'=> substr($raw, -4),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('New token: ' . $raw)
            ->body('Copy this now — it will not be shown again.')
            ->warning()
            ->persistent()
            ->send();
    }

    protected static function deleteUptimeMonitor(Site $site): void
    {
        if ($site->uptime_kuma_monitor_id) {
            app(\App\Services\UptimeKumaService::class)
                ->deleteMonitor($site->uptime_kuma_monitor_id);
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit'   => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', '00000000-0000-0000-0000-000000000001')
            ->with(['client', 'plan']);
    }
}
