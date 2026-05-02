<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out the client after 8 hours of inactivity.
 * Must run AFTER portal.auth so auth()->guard('client')->check() is already true.
 */
final class SessionTimeout
{
    /** 8 hours in seconds */
    private const TIMEOUT = 28800;

    public function handle(Request $request, Closure $next): Response
    {
        if (\Illuminate\Support\Facades\Auth::guard('client')->check()) {
            $lastActivity = session('_client_last_activity', time());

            if (time() - $lastActivity > self::TIMEOUT) {
                \Illuminate\Support\Facades\Auth::guard('client')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('portal.login')
                    ->with('status', 'Your session expired. Please sign in again.');
            }

            session(['_client_last_activity' => time()]);
        }

        return $next($request);
    }
}
