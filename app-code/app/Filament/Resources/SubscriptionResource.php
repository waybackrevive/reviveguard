<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Clients & revenue';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state, Subscription $record) => $record->billingStatusLabel())
                    ->badge()
                    ->color(fn (Subscription $record) => $record->billingStatusBadgeColor()),

                Tables\Columns\TextColumn::make('stripe_current_period_end')
                    ->label('Period end')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('Canceled at')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activated_at')
                    ->label('Activated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_status')
                    ->label('Stripe status')
                    ->options([
                        'active'   => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past due',
                        'canceled' => 'Canceled',
                        'unpaid'   => 'Unpaid',
                    ]),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::where('tenant_id', config('app.tenant_id'))
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
                Tables\Actions\Action::make('stripe')
                    ->label('Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Subscription $record) => $record->stripeDashboardUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Subscription $record) => $record->stripeDashboardUrl() !== null),

                Tables\Actions\Action::make('site')
                    ->label('Site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (Subscription $record) => $record->site_id
                        ? SiteResource::getUrl('edit', ['record' => $record->site_id])
                        : null)
                    ->visible(fn (Subscription $record) => $record->site_id !== null),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', config('app.tenant_id'))
            ->with(['client', 'site', 'plan']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
