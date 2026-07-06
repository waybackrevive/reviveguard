<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ClientActivityService;
use App\Services\StripeBillingService;
use App\Support\StripeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class AddonCheckoutSuccessController extends Controller
{
    public function __invoke(
        Request $request,
        StripeBillingService $billing,
        ClientActivityService $activity,
    ): RedirectResponse {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('portal.addons')
                ->with('error', 'We could not verify your payment. Contact support if you were charged.');
        }

        $client = $request->user('client');

        try {
            Stripe::setApiKey(StripeConfig::secretKey());
            $session = Session::retrieve($sessionId);

            if (($session->metadata->client_id ?? null) !== $client->id) {
                abort(403);
            }

            if ($billing->handleAddonCheckoutCompleted($session)) {
                $order = \App\Models\AddonOrder::find($session->metadata->addon_order_id ?? null);

                if ($order) {
                    $site = $order->site;
                    $activity->log(
                        $client,
                        'addon_order_paid',
                        "Add-on paid: {$order->addon_name}",
                        $order->formattedAmount() . ' received — our team will begin work shortly.',
                        $site,
                        ['addon_order_id' => $order->id, 'addon_slug' => $order->addon_slug],
                    );
                }

                return redirect()->route('portal.addons')
                    ->with('success', 'Payment received. Our team has been notified and will start your order.');
            }
        } catch (\Throwable $e) {
            Log::error('AddonCheckoutSuccess: failed', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()->route('portal.addons')
            ->with('success', 'Payment received. Your order will update here in a moment.');
    }
}
