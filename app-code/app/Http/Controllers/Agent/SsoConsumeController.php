<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\WordPressSsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plugin calls this to validate a one-time SSO token before wp-admin login.
 */
class SsoConsumeController extends Controller
{
    public function __invoke(Request $request, WordPressSsoService $sso): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $plain = (string) $request->input('login_token', '');

        if ($plain === '' || ! $sso->consume($plain, $site)) {
            return response()->json(['ok' => false, 'error' => 'Invalid or expired login link'], 403);
        }

        return response()->json(['ok' => true]);
    }
}
