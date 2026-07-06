<?php

use App\Http\Controllers\Agent\CommandResultController;
use App\Http\Controllers\Agent\EventController;
use App\Http\Controllers\Agent\HeartbeatController;
use App\Http\Controllers\Agent\PluginListController;
use App\Http\Controllers\Agent\SsoConsumeController;
use App\Http\Controllers\Webhook\UptimeKumaController;
use App\Http\Controllers\Webhook\StripeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes — /api/v1/agent/*
|--------------------------------------------------------------------------
| All routes are stateless (api middleware group = no session/CSRF).
| AgentTokenAuth validates Bearer token → sha256 → Site model.
| Rate limiter 'agent' = 60 req/min per token (AppServiceProvider).
*/
Route::prefix('v1/agent')
    ->middleware(['agent.auth', 'throttle:agent'])
    ->group(function () {
        Route::post('heartbeat',       HeartbeatController::class);
        Route::post('command-result',  CommandResultController::class);
        Route::post('plugin-list',     PluginListController::class);
        Route::post('event',           EventController::class);
        Route::post('sso-consume',     SsoConsumeController::class);
    });

// Uptime Kuma status webhooks — validated by shared secret header
Route::post('v1/webhooks/uptime-kuma', UptimeKumaController::class)
    ->middleware(['uptime_kuma.webhook', 'throttle:60,1']);

// Stripe billing webhooks — validated by Stripe-Signature header
Route::post('v1/webhooks/stripe', StripeController::class)
    ->middleware(['stripe.webhook', 'throttle:60,1']);
