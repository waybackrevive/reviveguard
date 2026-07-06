<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guest-only portal routes — redirect already-authenticated clients to dashboard.
 */
class RedirectIfClientAuthenticated
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::guard('client')->check()) {
            $client = Auth::guard('client')->user();

            return redirect()->route(
                $client->hasCompletedOnboarding() ? 'portal.sites' : 'portal.welcome-setup'
            );
        }

        return $next($request);
    }
}
