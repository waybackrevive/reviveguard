<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientInviteResource\Pages;
use App\Models\ClientInvite;
use App\Services\InviteService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ClientInviteResource extends Resource
{
    protected static ?string $model = ClientInvite::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationGroup = 'Clients';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'email';

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Recipient name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('site_url')
                ->label('Site URL')
                ->url()
                ->nullable()
                ->maxLength(500)
                ->placeholder('https://example.com'),

            Select::make('path')
                ->label('Invite path')
                ->options([
                    'alumni'     => 'Alumni (WaybackRevive restored)',
                    'evaluation' => 'Evaluation (approved new client)',
                ])
                ->required()
                ->default('alumni'),

            Textarea::make('notes')
                ->label('Internal notes')
                ->nullable()
                ->rows(3)
                ->maxLength(1000),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('site_url')
                    ->label('Site URL')
                    ->limit(40)
                    ->toggleable(),

                BadgeColumn::make('path')
                    ->label('Path')
                    ->colors([
                        'primary' => 'alumni',
                        'success' => 'evaluation',
                    ]),

                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'accepted' => 'success',
                        'expired'  => 'gray',
                        'revoked'  => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(false),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('email_sent_at')
                    ->label('Sent')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('path')
                    ->options([
                        'alumni'     => 'Alumni',
                        'evaluation' => 'Evaluation',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'accepted' => 'Accepted',
                        'expired'  => 'Expired',
                        'revoked'  => 'Revoked',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        return match ($data['value']) {
                            'pending'  => $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '>', now()),
                            'accepted' => $query->whereNotNull('accepted_at'),
                            'expired'  => $query->whereNull('accepted_at')->whereNull('revoked_at')->where('expires_at', '<=', now()),
                            'revoked'  => $query->whereNotNull('revoked_at'),
                            default    => $query,
                        };
                    }),
            ])
            ->actions([
                // Show invite link (copy)
                Action::make('view_link')
                    ->label('Invite Link')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->visible(fn (ClientInvite $record) => $record->isPending())
                    ->modalHeading('Invite Link')
                    ->modalDescription('This link was sent in the invite email. The plain token is NOT stored here — to send a new link, use Resend.')
                    ->modalContent(fn (ClientInvite $record) => view('filament.components.invite-link-info', ['invite' => $record]))
                    ->modalSubmitAction(false),

                // Resend (generates new token, sends email)
                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (ClientInvite $record) => ! $record->isAccepted() && ! $record->isRevoked())
                    ->requiresConfirmation()
                    ->action(function (ClientInvite $record, InviteService $inviteService) {
                        $inviteService->resend($record);
                        Notification::make()->title('Invite resent to ' . $record->email)->success()->send();
                    }),

                // Revoke
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ClientInvite $record) => $record->isPending() || $record->isExpired())
                    ->requiresConfirmation()
                    ->action(function (ClientInvite $record, InviteService $inviteService) {
                        $inviteService->revoke($record);
                        Notification::make()->title('Invite revoked')->success()->send();
                    }),
            ])
            ->bulkActions([
                // Bulk send emails for un-sent invites
                BulkAction::make('bulk_send')
                    ->label('Send invite emails')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records, InviteService $inviteService) {
                        $sent = 0;
                        foreach ($records as $invite) {
                            /** @var ClientInvite $invite */
                            if ($invite->isPending() && $invite->email_sent_at === null) {
                                $inviteService->resend($invite);
                                $sent++;
                            }
                        }
                        Notification::make()->title("Sent {$sent} invite email(s)")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('import_csv')
                    ->label('Import from CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('csv_file')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                            ->required()
                            ->helperText('Required columns: name, email. Optional: site_url'),
                        Select::make('csv_path')
                            ->label('Invite path for all rows')
                            ->options(['alumni' => 'Alumni', 'evaluation' => 'Evaluation'])
                            ->default('alumni')
                            ->required(),
                    ])
                    ->action(function (array $data, InviteService $inviteService) {
                        $csvPath  = storage_path('app/' . $data['csv_file']);
                        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
                        $adminId  = auth()->id();

                        $reader = \League\Csv\Reader::createFromPath($csvPath, 'r');
                        $reader->setHeaderOffset(0);

                        $rows = iterator_to_array($reader->getRecords());
                        $results = $inviteService->bulkCreate($tenantId, $rows, $data['csv_path'], $adminId);

                        Notification::make()
                            ->title(count($results) . ' invite(s) created. Use "Send invite emails" to send them.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClientInvites::route('/'),
            'create' => Pages\CreateClientInvite::route('/create'),
            'edit'   => Pages\EditClientInvite::route('/{record}/edit'),
        ];
    }

    // ── Mutate before create ──────────────────────────────────────────────────

    /**
     * Intercept the Filament create form to use InviteService (token generation + email).
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id', '00000000-0000-0000-0000-000000000001'));
    }
}
