<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('portal')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'agent.auth'           => \App\Http\Middleware\AgentTokenAuth::class,
            'uptime_kuma.webhook'  => \App\Http\Middleware\VerifyUptimeKumaWebhook::class,
            'portal.auth'          => \App\Http\Middleware\AuthenticateClient::class,
            'portal.guest'         => \App\Http\Middleware\RedirectIfClientAuthenticated::class,
            'portal.timeout'       => \App\Http\Middleware\SessionTimeout::class,
            'portal.onboarded'     => \App\Http\Middleware\EnsurePortalOnboarding::class,
            'whop.webhook'         => \App\Http\Middleware\VerifyWhopWebhook::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
