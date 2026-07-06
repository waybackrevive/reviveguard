<?php

namespace App\Http\Controllers\Webhook;

use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Receives Stripe billing webhooks (Checkout, subscriptions, invoices).
 */
class StripeController extends Controller
{
    public function __construct(private readonly StripeBillingService $billingService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = $request->attributes->get('stripe_event');

        if (! $event) {
            return response()->json(['error' => 'Invalid event'], Response::HTTP_BAD_REQUEST);
        }

        Log::info("Stripe webhook received: {$event->type}");

        match ($event->type) {
            'checkout.session.completed' => $this->billingService->handleCheckoutSessionCompleted($event->data->object),
            'customer.subscription.created',
            'customer.subscription.updated' => $this->billingService->handleSubscriptionEvent($event->data->object),
            'customer.subscription.deleted' => $this->billingService->handleSubscriptionDeleted($event->data->object),
            'invoice.paid' => $this->billingService->handleInvoicePaid($event->data->object),
            default => Log::debug("Stripe webhook: unhandled event '{$event->type}'"),
        };

        return response()->json(['ok' => true], Response::HTTP_OK);
    }
}
