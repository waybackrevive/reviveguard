<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user('client');

        if ($client && ! $client->hasCompletedOnboarding()) {
            return redirect()->route('portal.welcome-setup');
        }

        return $next($request);
    }
}
