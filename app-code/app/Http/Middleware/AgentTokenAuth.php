<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (empty($bearer)) {
            return response()->json(['error' => 'Missing token'], 401);
        }

        $hashed = hash('sha256', $bearer);

        $site = Site::where('agent_token', $hashed)
            ->where('is_active', true)
            ->first();

        if (! $site) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Inject the resolved Site model so controllers don't re-query
        $request->attributes->set('site', $site);

        return $next($request);
    }
}
