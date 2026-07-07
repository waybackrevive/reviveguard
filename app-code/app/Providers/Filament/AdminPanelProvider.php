<?php

namespace App\Providers\Filament;

use App\Support\StripeConfig;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->authGuard('web')
            ->colors([
                // Emerald green as primary accent — professional on Filament's default light background
                'primary' => Color::hex('#059669'),
            ])
            ->brandName(StripeConfig::isTestMode()
                ? '⚠ STRIPE TEST MODE — ReviveGuard Admin'
                : 'ReviveGuard Admin • WaybackRevive')
            ->favicon(asset('favicon.svg'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Clients & revenue',
                'Pre-sales',
                'Monitoring & care',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\NeedsAttentionWidget::class,
                \App\Filament\Widgets\SiteHealthOverview::class,
                \App\Filament\Widgets\SiteEventsChart::class,
                \App\Filament\Widgets\ClientActivityChart::class,
                \App\Filament\Widgets\NewSubscriptionsChart::class,
                \App\Filament\Widgets\ProbeFailuresChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\RequireAdminAccessCode::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
