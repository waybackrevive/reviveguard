<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Validates that incoming Uptime Kuma webhooks include the shared secret header.
 * Configured in Uptime Kuma: add header X-Webhook-Secret = {UPTIME_KUMA_WEBHOOK_SECRET}.
 */
class VerifyUptimeKumaWebhook
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = PlatformSetting::get('uptime_kuma_webhook_secret', config('services.uptime_kuma.webhook_secret', '')) ?? '';

        // If no secret is configured, skip validation (dev/unconfigured mode)
        if (empty($secret)) {
            return $next($request);
        }

        $incoming = (string) $request->header('X-Webhook-Secret', '');

        if (! hash_equals($secret, $incoming)) {
            Log::warning('UptimeKuma webhook: invalid secret', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
