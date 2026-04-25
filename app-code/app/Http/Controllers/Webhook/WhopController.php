<?php

namespace App\Http\Controllers\Webhook;

use App\Services\WhopBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Receives Whop membership lifecycle webhooks.
 *
 * Events handled:
 *  membership.went_valid    — new subscription or reactivation
 *  membership.went_invalid  — payment failed / cancelled / expired
 *  membership.was_banned    — permanent ban
 *
 * The route is protected by VerifyWhopWebhook middleware (HMAC-SHA256 signature check).
 */
class WhopController extends Controller
{
    public function __construct(private readonly WhopBillingService $billingService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = $request->input('action') ?? $request->input('event') ?? '';
        $data  = $request->input('data', []);

        Log::info("Whop webhook received: {$event}");

        match ($event) {
            'membership.went_valid'   => $this->billingService->handleMembershipWentValid($data),
            'membership.went_invalid' => $this->billingService->handleMembershipWentInvalid($data),
            'membership.was_banned'   => $this->billingService->handleMembershipWasBanned($data),
            default                   => Log::debug("Whop webhook: unhandled event '{$event}'"),
        };

        // Always return 200 to Whop — never return 4xx unless signature fails
        return response()->json(['ok' => true], Response::HTTP_OK);
    }
}
