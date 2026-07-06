<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\StripeBillingService;
use App\Support\StripeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Stripe Checkout return URL — confirms payment and welcomes the client.
 */
class CheckoutSuccessController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $billing): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('portal.sites')
                ->with('error', 'We could not verify your checkout session. Open Billing or contact support if you were charged.');
        }

        $client  = $request->user('client');
        $session = null;

        try {
            Stripe::setApiKey(StripeConfig::secretKey());
            $session = Session::retrieve($sessionId, ['expand' => ['subscription']]);

            if (($session->metadata->client_id ?? null) !== $client->id) {
                abort(403);
            }

            if ($session->payment_status === 'paid' || $session->status === 'complete') {
                $billing->handleCheckoutSessionCompleted($session);
            }
        } catch (\Throwable $e) {
            Log::error('CheckoutSuccess: failed to confirm session', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);

            try {
                Stripe::setApiKey(StripeConfig::secretKey());
                $session = Session::retrieve($sessionId);
            } catch (\Throwable) {
                $session = null;
            }

            return $this->redirectAfterPayment($client, $session, partial: true);
        }

        return $this->redirectAfterPayment($client, $session);
    }

    private function redirectAfterPayment($client, ?object $session, bool $partial = false): RedirectResponse
    {
        $siteId = $session?->metadata?->site_id ?? null;
        $site   = $siteId ? Site::with(['plan', 'subscription'])->find($siteId) : null;

        if ($site && $site->client_id === $client->id && $site->hasPaidSubscription()) {
            $planName = $site->plan?->name ?? 'your plan';

            return redirect()->route('portal.sites.show', [
                'site' => $site,
                'tab'  => $site->hasAgentConnected() ? 'overview' : 'connection',
            ])->with('checkout_welcome', [
                'site'      => $site->displayName(),
                'plan'      => $planName,
                'connected' => $site->hasAgentConnected(),
                'partial'   => $partial,
            ]);
        }

        if ($partial) {
            return redirect()->route('portal.sites')
                ->with('checkout_pending', true);
        }

        return redirect()->route('portal.sites')
            ->with('success', 'Payment received. Your protection is being activated — refresh in a moment to see your site.');
    }
}
