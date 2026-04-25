<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Client;
use App\Models\Site;
use App\Models\Ticket;
use App\Services\NotificationService;
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

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Support Tickets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket Details')
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->options(fn () => Client::where('tenant_id', '00000000-0000-0000-0000-000000000001')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->options(fn () => Site::where('tenant_id', '00000000-0000-0000-0000-000000000001')
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
                            'medium' => 'Medium',
                            'high'   => 'High',
                        ])
                        ->default('medium'),

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
                    ->sortable(),

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
                Tables\Actions\EditAction::make()
                    ->after(function (Ticket $record, array $data): void {
                        // Send notification if a reply was just added or updated
                        if (! empty($data['admin_reply'])) {
                            $record->update([
                                'replied_at' => $record->replied_at ?? now(),
                            ]);
                            try {
                                (new NotificationService())->sendTicketReplied($record->fresh());
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::error('TicketResource: sendTicketReplied failed', ['error' => $e->getMessage()]);
                            }
                        }
                    }),
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
            ->where('tenant_id', '00000000-0000-0000-0000-000000000001')
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
