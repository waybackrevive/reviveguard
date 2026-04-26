<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminAccessCode
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredCode = (string) env('ADMIN_ACCESS_CODE', '');

        // If no code is configured, do not block admin.
        if ($configuredCode === '') {
            return $next($request);
        }

        if ($request->session()->get('admin_access_granted') === true) {
            return $next($request);
        }

        return redirect()->route('admin.access.form');
    }
}
