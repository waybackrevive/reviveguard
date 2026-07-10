<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Enums\TicketType;
use App\Models\Client;
use App\Models\Site;
use App\Models\Ticket;
use App\Services\TicketSlaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Monitoring & care';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Support tickets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket Details')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(fn () => Client::where('tenant_id', config('app.tenant_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->options(fn () => Site::where('tenant_id', config('app.tenant_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('subject')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('message')
                        ->label('Client Message')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(5)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Your Reply')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Ticket type')
                        ->options(TicketType::options())
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'open'        => 'Open',
                            'in_progress' => 'In Progress',
                            'resolved'    => 'Resolved',
                            'closed'      => 'Closed',
                        ])
                        ->required(),

                    Forms\Components\Select::make('priority')
                        ->options([
                            'low'    => 'Low',
                            'normal' => 'Normal',
                            'medium' => 'Medium',
                            'high'   => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('medium'),

                    Forms\Components\Placeholder::make('sla_due_at_display')
                        ->label('SLA due')
                        ->content(fn (?Ticket $record) => $record?->sla_due_at
                            ? $record->sla_due_at->format('M j, Y g:i A T')
                            : '—')
                        ->visible(fn (?Ticket $record) => $record?->sla_due_at !== null),

                    Forms\Components\TextInput::make('minutes_billed')
                        ->label('Content minutes billed')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(480)
                        ->helperText('Deducted from client allowance when ticket is resolved/closed.')
                        ->visible(fn (Forms\Get $get) => $get('type') === TicketType::CONTENT_EDIT->value),

                    Forms\Components\Textarea::make('admin_reply')
                        ->label('Reply to Client')
                        ->rows(6)
                        ->helperText('The client will receive an email notification when you save a reply.')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Ticket $record) => ClientResource::getUrl('edit', ['record' => $record->client_id])),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('—')
                    ->url(fn (Ticket $record) => $record->site_id
                        ? SiteResource::getUrl('edit', ['record' => $record->site_id])
                        : null),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'open',
                        'primary' => 'in_progress',
                        'success' => 'resolved',
                        'gray'    => 'closed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state) => TicketType::tryFrom((string) $state)?->label() ?? $state)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sla_due_at')
                    ->label('SLA')
                    ->since()
                    ->color(fn (Ticket $record) => app(TicketSlaService::class)->isBreached($record) ? 'danger' : 'warning')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray'    => 'low',
                        'primary' => 'medium',
                        'danger'  => 'high',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('admin_reply')
                    ->label('Replied')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn (Ticket $record) => ! empty($record->admin_reply)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('replied_at')
                    ->label('Replied')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open'        => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved'    => 'Resolved',
                        'closed'      => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                    ]),

                Tables\Filters\Filter::make('unreplied')
                    ->label('Unreplied')
                    ->query(fn (Builder $q) => $q->whereNull('admin_reply')),
            ])
            ->actions([
                Tables\Actions\Action::make('client')
                    ->label('Client')
                    ->icon('heroicon-o-user')
                    ->url(fn (Ticket $record) => ClientResource::getUrl('edit', ['record' => $record->client_id])),

                Tables\Actions\Action::make('site')
                    ->label('Site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (Ticket $record) => $record->site_id
                        ? SiteResource::getUrl('edit', ['record' => $record->site_id])
                        : null)
                    ->visible(fn (Ticket $record) => $record->site_id !== null),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'edit'  => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client', 'site']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->whereIn('status', ['open', 'in_progress'])
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
