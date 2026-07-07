<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Subscription;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Subscriptions';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['site', 'plan'])->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stripe_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state, Subscription $record) => $record->billingStatusLabel())
                    ->badge()
                    ->color(fn (Subscription $record) => $record->isActive() ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('stripe_current_period_end')
                    ->label('Period end')
                    ->date('M j, Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stripe_subscription_id')
                    ->label('Stripe sub')
                    ->formatStateUsing(fn (?string $state) => $state ? substr($state, 0, 12).'…' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
