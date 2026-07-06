<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $portalUrl = config('services.reviveguard.api_url');
        if (! empty($portalUrl)) {
            URL::forceRootUrl(rtrim($portalUrl, '/'));
        }

        // 60 requests per minute per agent token on all agent API endpoints
        RateLimiter::for('agent', function (Request $request) {
            $token = $request->bearerToken() ?? $request->ip();
            return Limit::perMinute(60)->by($token);
        });
    }
}

