<?php

namespace App\Filament\Resources;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\SiteStatus;
use App\Enums\SiteType;
use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers;
use App\Jobs\GenerateSiteReport;
use App\Jobs\RunBrokenLinkAudit;
use App\Jobs\RunExternalMalwareScan;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Report;
use App\Models\Site;
use App\Models\SiteCommand;
use App\Services\NotificationService;
use App\Services\WhoisXmlService;
use App\Support\StripeConfig;
use App\Support\PlanFeatures;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Clients & revenue';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $tenantId = config('app.tenant_id');

        return $form->schema([
            Forms\Components\Section::make('Site Info')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(fn () => Client::where('tenant_id', $tenantId)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn () => Plan::where('tenant_id', $tenantId)
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
                        ->label('Probe status')
                        ->options(SiteStatus::class)
                        ->default(SiteStatus::PENDING)
                        ->required()
                        ->disabledOn('edit')
                        ->helperText(fn (string $operation) => $operation === 'edit'
                            ? 'Set by uptime probes and heartbeats — edit only to correct bad data.'
                            : null),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Monitoring')
                ->schema([
                    Forms\Components\Placeholder::make('monitor_interval')
                        ->label('Interval')
                        ->content(fn (?Site $record) => $record?->monitor_interval_minutes
                            ? $record->monitor_interval_minutes.' min'
                            : '—'),

                    Forms\Components\Placeholder::make('monitor_region')
                        ->label('Region')
                        ->content(fn (?Site $record) => $record?->monitor_region ?? 'Default (app server)'),

                    Forms\Components\Placeholder::make('monitoring_paused')
                        ->label('Paused')
                        ->content(fn (?Site $record) => $record?->monitoring_paused
                            ? 'Yes · since '.$record->monitoring_paused_at?->format('M j, Y')
                            : 'No'),

                    Forms\Components\Placeholder::make('last_uptime_probe_at')
                        ->label('Last probe')
                        ->content(fn (?Site $record) => $record?->last_uptime_probe_at?->diffForHumans() ?? 'Never'),
                ])
                ->columns(2)
                ->visibleOn('edit'),

            Forms\Components\Section::make('Billing')
                ->schema([
                    Forms\Components\Placeholder::make('subscription_status')
                        ->label('Subscription')
                        ->content(fn (?Site $record) => $record?->subscription
                            ? $record->subscription->billingStatusLabel()
                            : 'No active subscription'),

                    Forms\Components\Placeholder::make('period_end')
                        ->label('Next billing')
                        ->content(fn (?Site $record) => $record?->subscription?->nextBillingDate()?->format('M j, Y') ?? '—'),

                    Forms\Components\Placeholder::make('stripe_customer')
                        ->label('Stripe customer')
                        ->content(function (?Site $record): string|HtmlString {
                            $id = $record?->client?->stripeCustomerId();
                            if (! $id) {
                                return '—';
                            }

                            $prefix = StripeConfig::isTestMode() ? 'test/' : '';
                            $url    = 'https://dashboard.stripe.com/'.$prefix.'customers/'.$id;

                            return new HtmlString(
                                '<a href="'.e($url).'" target="_blank" class="text-primary-600 hover:underline">'
                                .e(substr($id, 0, 10).'…')
                                .'</a>'
                            );
                        }),
                ])
                ->columns(2)
                ->visibleOn('edit'),

            Forms\Components\Section::make('Agent Token')
                ->schema([
                    Forms\Components\Placeholder::make('agent_token_last4')
                        ->label('Token (last 4)')
                        ->content(fn ($record) => $record?->agent_token_last4
                            ? '····'.$record->agent_token_last4
                            : 'Generated on save'),

                    Forms\Components\Placeholder::make('agent_info')
                        ->label('Agent')
                        ->content(fn (?Site $record) => $record?->last_seen_at
                            ? ($record->agent_version ?? 'unknown').' · last seen '.$record->last_seen_at->diffForHumans()
                            : 'Never connected'),
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

                Tables\Columns\TextColumn::make('portal_status')
                    ->label('Portal status')
                    ->badge()
                    ->state(fn (Site $record) => $record->portalStatusLabel())
                    ->color(fn (Site $record) => $record->portalStatusColor()),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('subscription.stripe_status')
                    ->label('Subscription')
                    ->formatStateUsing(fn ($state, Site $record) => $record->subscription
                        ? $record->subscription->billingStatusLabel()
                        : 'Unpaid')
                    ->description(fn (Site $record) => $record->subscription?->nextBillingDate()?->format('M j, Y'))
                    ->color(fn (Site $record) => $record->hasPaidSubscription() ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('monitoring_paused')
                    ->label('Monitoring')
                    ->formatStateUsing(fn ($state, Site $record) => $record->monitoring_paused
                        ? 'Paused'
                        : (($record->monitor_interval_minutes ?? '—').' min'))
                    ->description(fn (Site $record) => $record->monitor_region ?? 'app server')
                    ->badge()
                    ->color(fn (Site $record) => $record->monitoring_paused ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('last_uptime_probe_at')
                    ->label('Last probe')
                    ->since()
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Agent')
                    ->since()
                    ->description(fn (Site $record) => $record->agent_version)
                    ->sortable(),

                Tables\Columns\TextColumn::make('uptime_7d')
                    ->label('7d')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1).'%' : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uptime_30d')
                    ->label('30d')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1).'%' : '—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->url(fn (Site $r) => $r->url)
                    ->openUrlInNewTab()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Probe status')
                    ->badge()
                    ->color(fn (SiteStatus $state) => $state->color())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('SSL Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->diffInDays() < 14 ? 'danger' : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('domain_expires_at')
                    ->label('Domain Expires')
                    ->date()
                    ->sortable()
                    ->description(fn (Site $record) => $record->registrar ? 'via '.$record->registrar : null)
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->diffInDays() < 30 ? 'warning' : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('whoisxml_last_checked_at')
                    ->label('WHOIS Checked')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('agent_token_last4')
                    ->label('Token')
                    ->formatStateUsing(fn ($state) => $state ? '····'.$state : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('portal_status')
                    ->label('Portal status')
                    ->options([
                        'protected' => 'Protected',
                        'setup'     => 'Setup needed',
                        'checkout'  => 'Complete checkout',
                        'warning'   => 'Needs attention',
                        'issue'     => 'Down (paid)',
                    ])
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->wherePortalStatus($data['value'])
                        : $query),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),

                Tables\Filters\TernaryFilter::make('monitoring_paused')
                    ->label('Monitoring paused'),

                Tables\Filters\Filter::make('ssl_expiring')
                    ->label('SSL expiring (< 30 days)')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('ssl_expires_at')
                        ->where('ssl_expires_at', '<=', now()->addDays(30))),

                Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid')
                    ->query(fn (Builder $query) => $query->whereUnpaid()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Probe status')
                    ->options(SiteStatus::class),

                Tables\Filters\SelectFilter::make('type')
                    ->options(SiteType::class),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->options(fn () => Client::where('tenant_id', config('app.tenant_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
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
                        $queued = app(\App\Services\SiteCommandQueueService::class)
                            ->queueBackup($record, 'manual');

                        if (! $queued) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already queued')
                                ->body('A backup command is already pending for this site.')
                                ->warning()
                                ->send();

                            return;
                        }

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
                    ->modalDescription('Queues a pre-update backup first if none exists in the last 24 hours, then runs WordPress core + plugin + theme updates.')
                    ->action(function (Site $record): void {
                        $queued = app(\App\Services\SiteCommandQueueService::class)
                            ->queueWpUpdates($record, 'manual');

                        if (! $queued) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already queued')
                                ->body('An update or pre-update backup is already pending for this site.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $isPreBackup = ($queued->type === CommandType::RUN_BACKUP);

                        \Filament\Notifications\Notification::make()
                            ->title($isPreBackup ? 'Pre-update backup queued' : 'Update queued')
                            ->body($isPreBackup
                                ? "Safety backup queued first for {$record->name}. Updates will run automatically after it completes."
                                : "WP update command queued for {$record->name}. Agent will pick it up within 5 minutes.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('trigger_rollback')
                    ->label('Rollback Last Update')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rollback from backup')
                    ->modalDescription('Restores the site from the latest pre-update backup (or most recent successful backup). Use after a broken update.')
                    ->action(function (Site $record): void {
                        $queued = app(\App\Services\SiteCommandQueueService::class)
                            ->queueRollbackRestore($record, 'manual');

                        if (! $queued) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot queue rollback')
                                ->body('No restorable backup found, or a rollback is already pending.')
                                ->warning()
                                ->send();

                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Rollback queued')
                            ->body("Restore command queued for {$record->name}. Agent will pick it up within 5 minutes.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('trigger_malware_scan')
                    ->label('Run Malware Scan')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Run malware scan')
                    ->modalDescription('Queues a malware integrity scan. WordPress sites use the agent; other sites use external reputation checks. Available for all plans (admin override).')
                    ->action(function (Site $record): void {
                        if ($record->type === SiteType::WORDPRESS) {
                            $queued = app(\App\Services\SiteCommandQueueService::class)
                                ->queueMalwareScan($record, 'manual');

                            if (! $queued) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Already queued')
                                    ->body('A malware scan is already pending for this site.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Malware scan queued')
                                ->body("Scan queued for {$record->name}. Agent will pick it up within 5 minutes.")
                                ->success()
                                ->send();

                            return;
                        }

                        RunExternalMalwareScan::dispatch($record->id, 'manual');

                        \Filament\Notifications\Notification::make()
                            ->title('External scan started')
                            ->body("Reputation scan queued for {$record->name}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('trigger_link_audit')
                    ->label('Run Link Audit')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Run broken link audit')
                    ->modalDescription('Crawls up to 200 internal links from the server and records broken URLs. Available for all plans (admin override).')
                    ->action(function (Site $record): void {
                        RunBrokenLinkAudit::dispatch($record->id, 'manual');

                        \Filament\Notifications\Notification::make()
                            ->title('Link audit queued')
                            ->body("Broken link audit queued for {$record->name}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('recheck_domain')
                    ->label('Re-check Domain')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Re-check Domain Info')
                    ->modalDescription('Calls WhoisXML API with a hard refresh (costs 5 credits) to fetch fresh WHOIS data. Use when domain info looks stale or stuck.')
                    ->action(function (Site $record): void {
                        if (! $record->domain) {
                            \Filament\Notifications\Notification::make()
                                ->title('No domain set')
                                ->body('This site has no domain configured.')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            $whoisXml = app(WhoisXmlService::class);
                            $data = $whoisXml->whois((string) $record->domain, hardRefresh: true);

                            if (isset($data['error'])) {
                                \Filament\Notifications\Notification::make()
                                    ->title('WHOIS lookup failed')
                                    ->body($data['error'])
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $updates = ['whoisxml_last_checked_at' => now()];
                            if (isset($data['expires_at'])) {
                                $updates['domain_expires_at'] = $data['expires_at'];
                            }
                            if (! empty($data['registrar'])) {
                                $updates['registrar'] = $data['registrar'];
                            }
                            $record->update($updates);

                            $msg = isset($data['expires_at'])
                                ? "Expires: {$data['expires_at']} ({$data['days_remaining']} days). Registrar: " . ($data['registrar'] ?? 'n/a')
                                : 'No expiry date in WHOIS record.';

                            \Filament\Notifications\Notification::make()
                                ->title('Domain info updated')
                                ->body($msg)
                                ->success()
                                ->send();

                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
        return [
            RelationManagers\EventsRelationManager::class,
            RelationManagers\BackupsRelationManager::class,
            RelationManagers\ReportsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
            RelationManagers\CommandsRelationManager::class,
            RelationManagers\UptimeProbesRelationManager::class,
        ];
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
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client', 'plan', 'subscription']);
    }
}
