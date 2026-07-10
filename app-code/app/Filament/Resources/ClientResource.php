<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Support\PortalAccess;
use App\Support\StripeConfig;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Clients & revenue';

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

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Shield premium')
                ->description('Account manager and content-edit hours for Shield clients.')
                ->schema([
                    Forms\Components\Select::make('account_manager_id')
                        ->label('Account manager')
                        ->options(fn () => User::query()
                            ->where('tenant_id', config('app.tenant_id'))
                            ->where('is_super_admin', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->helperText('Shown in the Shield client portal.'),

                    Forms\Components\TextInput::make('content_minutes_remaining')
                        ->label('Content minutes remaining')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(999)
                        ->helperText('Shield plan includes 120 min/month. Reset automatically on the 1st.'),
                ])
                ->columns(2)
                ->visibleOn('edit'),

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
                        ->default(config('app.tenant_id')),
                ])->hidden(),

            Forms\Components\Section::make('Billing')
                ->schema([
                    Forms\Components\Placeholder::make('stripe_customer')
                        ->label('Stripe customer')
                        ->content(function (?Client $record): string|HtmlString {
                            $id = $record?->stripeCustomerId();
                            if (! $id) {
                                return 'Created on first checkout';
                            }

                            $prefix = StripeConfig::isTestMode() ? 'test/' : '';
                            $url    = 'https://dashboard.stripe.com/'.$prefix.'customers/'.$id;

                            return new HtmlString(
                                '<a href="'.e($url).'" target="_blank" class="text-primary-600 hover:underline">'
                                .e($record->maskedStripeCustomerId())
                                .'</a>'
                            );
                        }),
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

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sites_summary')
                    ->label('Sites')
                    ->state(fn (Client $record) => $record->sitesSummaryLabel())
                    ->description(fn (Client $record) => $record->timezone ?: 'UTC')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('sites_count', $direction)),

                Tables\Columns\TextColumn::make('open_tickets_count')
                    ->label('Open tickets')
                    ->sortable()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('accountManager.name')
                    ->label('Account mgr')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('content_minutes_remaining')
                    ->label('Content min')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stripe_customer')
                    ->label('Stripe')
                    ->state(fn (Client $record) => $record->maskedStripeCustomerId() ?? '—')
                    ->toggleable(),

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
                    ->modalHeading('Suspend client?')
                    ->modalDescription('Revokes portal login immediately. Site monitoring and Stripe billing are not changed — pause monitoring per site if needed.')
                    ->action(fn (Client $record) => $record->update(['is_active' => false])),
                Tables\Actions\Action::make('portal_access')
                    ->label('Open portal')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->visible(fn (Client $record) => $record->is_active)
                    ->url(fn (Client $record) => PortalAccess::signedLoginUrl($record))
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
        return [
            RelationManagers\SitesRelationManager::class,
            RelationManagers\SubscriptionsRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
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
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['accountManager'])
            ->withCount([
                'sites',
                'sites as paying_sites_count' => function (Builder $query): void {
                    $query->whereNotNull('subscription_id')
                        ->whereHas('subscription', function (Builder $sub): void {
                            $sub->whereIn('stripe_status', ['active', 'trialing'])
                                ->orWhere('whop_status', 'active');
                        });
                },
                'tickets as open_tickets_count' => function (Builder $query): void {
                    $query->whereIn('status', ['open', 'in_progress']);
                },
            ]);
    }
}
