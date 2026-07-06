<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Clients';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Client Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('company_name')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\Select::make('timezone')
                        ->options(collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz]))
                        ->searchable()
                        ->default('UTC'),

                    Forms\Components\TextInput::make('whop_member_id')
                        ->label('Whop Member ID')
                        ->maxLength(100)
                        ->helperText('From Whop dashboard — used for webhook matching'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Portal Access')
                ->schema([
                    Forms\Components\TextInput::make('portal_password')
                        ->label('Portal Password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Leave blank to keep existing password when editing'),
                ]),

            Forms\Components\Section::make('Internal Notes')
                ->schema([
                    Forms\Components\Hidden::make('tenant_id')
                        ->default('00000000-0000-0000-0000-000000000001'),
                ])->hidden(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sites_count')
                    ->counts('sites')
                    ->label('Sites')
                    ->sortable(),

                Tables\Columns\TextColumn::make('activeSubscription.stripe_status')
                    ->label('Subscription')
                    ->formatStateUsing(fn ($state, $record) => $record->activeSubscription?->billingStatusLabel() ?? '—')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active'    => 'success',
                        'past_due'  => 'warning',
                        'canceled'  => 'danger',
                        default     => 'gray',
                    })
                    ->default('—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Clients'),
                Tables\Filters\SelectFilter::make('path')
                    ->label('Path')
                    ->options([
                        'alumni'     => 'Alumni (WaybackRevive)',
                        'evaluation' => 'Evaluation (new client)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Client $record) => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(fn (Client $record) => $record->update(['is_active' => true])),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Client $record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalDescription('This will revoke portal access immediately.')
                    ->action(fn (Client $record) => $record->update(['is_active' => false])),
                Tables\Actions\Action::make('impersonate')
                    ->label('Portal Login Link')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Client $record) => url('/portal/login'))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', '00000000-0000-0000-0000-000000000001')
            ->with(['sites', 'activeSubscription']);
    }
}
