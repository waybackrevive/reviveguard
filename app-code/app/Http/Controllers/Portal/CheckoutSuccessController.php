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
 * Stripe Checkout return URL — confirms payment and sends the client to their site.
 */
class CheckoutSuccessController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $billing): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('portal.sites')
                ->with('error', 'Missing checkout session. If you completed payment, check Sites in a moment.');
        }

        $client = $request->user('client');

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

            return redirect()->route('portal.sites')
                ->with('error', 'Payment received — we are still confirming it. Refresh Sites in a minute or contact support if this persists.');
        }

        $site = Site::with('plan')->find($session->metadata->site_id ?? null);

        if ($site && $site->client_id === $client->id) {
            $planName = $site->plan?->name ?? 'your plan';
            $message  = $site->hasAgentConnected()
                ? "Payment confirmed! {$site->displayName()} is now on {$planName} and connected."
                : "Payment confirmed! {$planName} is active — finish connecting the plugin on the Connection tab.";

            return redirect()->route('portal.sites.show', ['site' => $site, 'tab' => $site->hasAgentConnected() ? 'overview' : 'connection'])
                ->with('success', $message);
        }

        return redirect()->route('portal.sites')
            ->with('success', 'Payment confirmed! Your site will appear as active shortly.');
    }
}
