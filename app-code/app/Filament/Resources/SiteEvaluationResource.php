<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteEvaluationResource\Pages;
use App\Models\Plan;
use App\Models\SiteEvaluation;
use App\Services\EvaluationService;
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
    protected static ?string $navigationGroup = 'Clients';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'prospect_email';

    // ── Form (edit admin notes only — prospect fields are read-only) ──────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('prospect_name')->label('Name')->disabled(),
            TextInput::make('prospect_email')->label('Email')->disabled(),
            TextInput::make('site_url')->label('Site URL')->disabled(),
            TextInput::make('site_type')->label('Site type')->disabled(),
            Textarea::make('concern')->label('Concern')->disabled()->rows(3),

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
                ->nullable(),
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
