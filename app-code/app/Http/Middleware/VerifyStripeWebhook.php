<?php

namespace App\Http\Middleware;

use App\Support\StripeConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            return response('Missing signature', Response::HTTP_BAD_REQUEST);
        }

        $secrets = $this->webhookSecrets();

        if ($secrets === []) {
            return response('Webhook secret not configured', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $event = null;
        foreach ($secrets as $secret) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $signature,
                    $secret
                );
                break;
            } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException) {
                continue;
            }
        }

        if (! $event) {
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $request->attributes->set('stripe_event', $event);

        return $next($request);
    }

    /** @return list<string> */
    private function webhookSecrets(): array
    {
        $fromSettings = array_filter([
            \App\Models\PlatformSetting::get('stripe_test_webhook_secret'),
            \App\Models\PlatformSetting::get('stripe_webhook_secret'),
            config('services.stripe.test_webhook_secret'),
            config('services.stripe.webhook_secret'),
        ]);

        return array_values(array_unique($fromSettings));
    }
}
