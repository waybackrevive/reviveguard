<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Protect portal routes — redirects unauthenticated clients to the login page.
 */
class AuthenticateClient
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::guard('client')->check()) {
            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
