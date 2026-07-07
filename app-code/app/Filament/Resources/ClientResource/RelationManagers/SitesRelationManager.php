<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    protected static ?string $title = 'Sites';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['plan', 'subscription'])->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('portal_status')
                    ->label('Portal status')
                    ->badge()
                    ->state(fn (Site $record) => $record->portalStatusLabel())
                    ->color(fn (Site $record) => $record->portalStatusColor()),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('subscription.stripe_status')
                    ->label('Subscription')
                    ->formatStateUsing(fn ($state, Site $record) => $record->subscription
                        ? $record->subscription->billingStatusLabel()
                        : 'Unpaid'),

                Tables\Columns\TextColumn::make('uptime_7d')
                    ->label('7d')
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 1).'%' : '—'),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Agent')
                    ->since()
                    ->placeholder('Never'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Site $record) => SiteResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
