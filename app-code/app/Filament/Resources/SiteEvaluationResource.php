<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteEvaluationResource\Pages;
use App\Models\Plan;
use App\Models\SiteEvaluation;
use App\Services\EvaluationService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SiteEvaluationResource extends Resource
{
    protected static ?string $model = SiteEvaluation::class;
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Pre-sales';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Site evaluations';
    protected static ?string $recordTitleAttribute = 'prospect_email';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Prospect info ────────────────────────────────────────────────
            Section::make('Prospect')
                ->columns(2)
                ->schema([
                    TextInput::make('prospect_name')->label('Name')->disabled(),
                    TextInput::make('prospect_email')->label('Email')->disabled(),
                    TextInput::make('site_url')->label('Site URL')->disabled()->columnSpanFull(),
                    TextInput::make('site_type')->label('Site type')->disabled(),
                    Textarea::make('concern')->label('Concern')->disabled()->rows(3)->columnSpanFull(),
                ]),

            // ── Admin controls ───────────────────────────────────────────────
            Section::make('Admin')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending'   => 'Pending',
                            'reviewing' => 'Reviewing',
                            'proposed'  => 'Proposed',
                            'converted' => 'Converted',
                            'declined'  => 'Declined',
                            'expired'   => 'Expired',
                        ])
                        ->required(),

                    Select::make('recommended_plan_id')
                        ->label('Recommended plan')
                        ->options(fn () => Plan::pluck('name', 'id'))
                        ->nullable(),

                    Textarea::make('admin_notes')
                        ->label('Internal notes')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            // ── External scan results ────────────────────────────────────────
            Section::make('External Scan')
                ->collapsible()
                ->columns(3)
                ->schema([
                    Placeholder::make('scan_status_label')
                        ->label('Scan status')
                        ->content(fn (SiteEvaluation $record) => ucfirst($record->scan_status ?? 'pending')),

                    Placeholder::make('scan_ran_at_label')
                        ->label('Scanned at')
                        ->content(fn (SiteEvaluation $record) => $record->scan_ran_at?->diffForHumans() ?? '—'),

                    Placeholder::make('risk_level_label')
                        ->label('Risk level')
                        ->content(fn (SiteEvaluation $record) => ucfirst($record->scan_results['risk_level'] ?? '—')),

                    // SSL
                    Placeholder::make('ssl_valid')
                        ->label('SSL valid')
                        ->content(fn (SiteEvaluation $record) => match($record->scan_results['ssl']['valid'] ?? null) {
                            true  => '✅ Yes',
                            false => '❌ No',
                            default => '—',
                        }),

                    Placeholder::make('ssl_expiry')
                        ->label('SSL expiry')
                        ->content(fn (SiteEvaluation $record) => isset($record->scan_results['ssl']['valid_to'])
                            ? $record->scan_results['ssl']['valid_to'] . ' (' . ($record->scan_results['ssl']['days_remaining'] ?? '?') . ' days)'
                            : '—'),

                    Placeholder::make('ssl_issuer')
                        ->label('SSL issuer')
                        ->content(fn (SiteEvaluation $record) => $record->scan_results['ssl']['issuer'] ?? '—'),

                    // HTTP
                    Placeholder::make('http_up')
                        ->label('Site reachable')
                        ->content(fn (SiteEvaluation $record) => match($record->scan_results['http']['up'] ?? null) {
                            true  => '✅ Yes (' . ($record->scan_results['http']['status_code'] ?? '') . ')',
                            false => '❌ No',
                            default => '—',
                        }),

                    Placeholder::make('http_response_time')
                        ->label('Response time')
                        ->content(fn (SiteEvaluation $record) => isset($record->scan_results['http']['response_time_ms'])
                            ? $record->scan_results['http']['response_time_ms'] . ' ms'
                            : '—'),

                    Placeholder::make('http_https')
                        ->label('HTTPS')
                        ->content(fn (SiteEvaluation $record) => match($record->scan_results['http']['is_https'] ?? null) {
                            true  => '✅ Yes',
                            false => '⚠️ No',
                            default => '—',
                        }),

                    // Security headers
                    Placeholder::make('headers_grade')
                        ->label('Security headers grade')
                        ->content(fn (SiteEvaluation $record) => $record->scan_results['security_headers']['grade'] ?? '—'),

                    Placeholder::make('headers_missing')
                        ->label('Missing headers')
                        ->content(fn (SiteEvaluation $record) => implode(', ', $record->scan_results['security_headers']['missing'] ?? []) ?: 'None'),

                    // CMS
                    Placeholder::make('cms_detected')
                        ->label('CMS detected')
                        ->content(fn (SiteEvaluation $record) => ucfirst($record->scan_results['cms']['detected'] ?? '—')),

                    // WHOIS
                    Placeholder::make('domain_expiry')
                        ->label('Domain expiry')
                        ->content(fn (SiteEvaluation $record) => $record->scan_results['whois']['expiry_date'] ?? '—'),

                    Placeholder::make('domain_registrar')
                        ->label('Registrar')
                        ->content(fn (SiteEvaluation $record) => $record->scan_results['whois']['registrar'] ?? '—'),
                ]),

            // ── Plugin deep scan ─────────────────────────────────────────────
            Section::make('Plugin Deep Scan')
                ->collapsible()
                ->columns(3)
                ->schema([
                    Placeholder::make('plugin_report_at_label')
                        ->label('Report received')
                        ->content(fn (SiteEvaluation $record) => $record->plugin_report_at?->diffForHumans() ?? 'Not yet submitted'),

                    Placeholder::make('wp_version')
                        ->label('WP version')
                        ->content(fn (SiteEvaluation $record) => $record->plugin_report['wp_version'] ?? '—'),

                    Placeholder::make('wp_update')
                        ->label('WP update available')
                        ->content(fn (SiteEvaluation $record) => match($record->plugin_report['wp_update_available'] ?? null) {
                            true  => '⚠️ Yes',
                            false => '✅ No',
                            default => '—',
                        }),

                    Placeholder::make('php_version')
                        ->label('PHP version')
                        ->content(fn (SiteEvaluation $record) => $record->plugin_report['php_version'] ?? '—'),

                    Placeholder::make('plugin_count')
                        ->label('Total plugins')
                        ->content(fn (SiteEvaluation $record) => $record->plugin_report['total_plugins'] ?? '—'),

                    Placeholder::make('plugins_needing_update')
                        ->label('Plugins needing update')
                        ->content(fn (SiteEvaluation $record) => isset($record->plugin_report['plugins_needing_update'])
                            ? ($record->plugin_report['plugins_needing_update'] === 0 ? '✅ None' : '⚠️ ' . $record->plugin_report['plugins_needing_update'])
                            : '—'),

                    Placeholder::make('backup_plugins')
                        ->label('Backup plugins')
                        ->content(fn (SiteEvaluation $record) => collect($record->plugin_report['backup_plugins'] ?? [])
                            ->pluck('name')->implode(', ') ?: '⚠️ None detected'),

                    Placeholder::make('security_plugins')
                        ->label('Security plugins')
                        ->content(fn (SiteEvaluation $record) => collect($record->plugin_report['security_plugins'] ?? [])
                            ->pluck('name')->implode(', ') ?: '⚠️ None detected'),

                    Placeholder::make('ssl_active_plugin')
                        ->label('SSL active (plugin)')
                        ->content(fn (SiteEvaluation $record) => match($record->plugin_report['ssl_active'] ?? null) {
                            true  => '✅ Yes',
                            false => '❌ No',
                            default => '—',
                        }),

                    Placeholder::make('admin_user_count')
                        ->label('Admin users')
                        ->content(fn (SiteEvaluation $record) => $record->plugin_report['admin_user_count'] ?? '—'),

                    Placeholder::make('db_size_mb')
                        ->label('DB size (MB)')
                        ->content(fn (SiteEvaluation $record) => isset($record->plugin_report['db_size_mb'])
                            ? number_format((float) $record->plugin_report['db_size_mb'], 2) . ' MB'
                            : '—'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prospect_name')->label('Name')->searchable()->sortable(),
                TextColumn::make('prospect_email')->label('Email')->searchable()->sortable(),
                TextColumn::make('site_url')->label('Site URL')->limit(35)->toggleable(),
                TextColumn::make('site_type')->label('Type')->badge()->toggleable(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray'    => 'pending',
                        'info'    => 'reviewing',
                        'warning' => 'proposed',
                        'success' => 'converted',
                        'danger'  => fn ($state) => in_array($state, ['declined', 'expired']),
                    ]),

                BadgeColumn::make('scan_status')
                    ->label('Scan')
                    ->colors([
                        'gray'    => 'pending',
                        'info'    => 'running',
                        'success' => 'done',
                        'danger'  => 'failed',
                    ])
                    ->toggleable(),

                IconColumn::make('plugin_report')
                    ->label('Deep scan')
                    ->boolean()
                    ->getStateUsing(fn (SiteEvaluation $record) => $record->plugin_report !== null)
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('waitlisted')
                    ->label('Waitlist')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(),

                TextColumn::make('created_at')->label('Submitted')->dateTime('M j, Y')->sortable(),
                TextColumn::make('proposal_sent_at')->label('Proposal sent')->dateTime('M j, Y')->sortable()->toggleable(),
                TextColumn::make('month_slot')->label('Month slot')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'reviewing' => 'Reviewing',
                        'proposed'  => 'Proposed',
                        'converted' => 'Converted',
                        'declined'  => 'Declined',
                        'expired'   => 'Expired',
                    ]),

                SelectFilter::make('site_type')
                    ->options([
                        'wordpress' => 'WordPress',
                        'html'      => 'HTML',
                        'other'     => 'Other',
                    ]),
            ])
            ->actions([
                Action::make('start_review')
                    ->label('Start Review')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn (SiteEvaluation $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (SiteEvaluation $record) {
                        $record->update(['status' => 'reviewing']);
                        Notification::make()->title('Marked as reviewing')->success()->send();
                    }),

                Action::make('send_proposal')
                    ->label('Send proposal')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (SiteEvaluation $record) => in_array($record->status, ['pending', 'reviewing']))
                    ->form([
                        Select::make('plan_id')
                            ->label('Recommended plan')
                            ->options(fn () => Plan::pluck('name', 'id'))
                            ->nullable(),
                    ])
                    ->action(function (SiteEvaluation $record, array $data, EvaluationService $evaluationService) {
                        $record->update(['status' => 'reviewing', 'admin_notes' => $record->admin_notes]);
                        $evaluationService->sendProposal($record, $data['plan_id'] ?? null, auth()->id());

                        Notification::make()
                            ->title('Proposal sent to ' . $record->prospect_email)
                            ->success()
                            ->send();
                    }),

                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SiteEvaluation $record) => in_array($record->status, ['pending', 'reviewing', 'proposed']))
                    ->requiresConfirmation()
                    ->action(function (SiteEvaluation $record, EvaluationService $evaluationService) {
                        $evaluationService->decline($record);
                        Notification::make()->title('Evaluation declined')->success()->send();
                    }),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteEvaluations::route('/'),
            'edit'  => Pages\EditSiteEvaluation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id', '00000000-0000-0000-0000-000000000001'));
    }
}
