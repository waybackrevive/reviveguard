<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Validates Whop webhook signatures.
 *
 * Whop sends a `whop-signature` header that is a HMAC-SHA256 hex digest
 * of the raw request body, signed with the webhook secret.
 */
class VerifyWhopWebhook
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $secret = config('services.whop.webhook_secret', '');

        if (empty($secret)) {
            Log::error('VerifyWhopWebhook: WHOP_WEBHOOK_SECRET is not configured');
            return response()->json(['error' => 'Webhook not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $signature = $request->header('whop-signature', '');
        $rawBody   = $request->getContent();

        $expected = hash_hmac('sha256', $rawBody, $secret);

        if (! hash_equals($expected, strtolower((string) $signature))) {
            Log::warning('VerifyWhopWebhook: invalid signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
