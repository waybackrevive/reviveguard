<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Jobs\OnboardClientJob;
use App\Services\InviteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * AcceptInviteController — handles GET and POST for /portal/accept-invite
 *
 * GET  /portal/accept-invite?token=PLAIN_TOKEN
 *   → Show a set-password form if token is valid.
 *   → Show error if token is invalid/expired/revoked.
 *
 * POST /portal/accept-invite
 *   → Validate password, call InviteService::accept(), run OnboardClientJob,
 *     log the client in, redirect to portal dashboard.
 */
class AcceptInviteController extends Controller
{
    public function __construct(private readonly InviteService $inviteService) {}

    // ── GET ───────────────────────────────────────────────────────────────────

    public function show(Request $request)
    {
        $plainToken = $request->query('token', '');

        $invite = $this->inviteService->validate($plainToken);

        if (! $invite) {
            return view('portal.accept-invite-invalid');
        }

        return view('portal.accept-invite', [
            'invite'     => $invite,
            'token'      => $plainToken,
        ]);
    }

    // ── POST ──────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $plainToken = $request->input('token');
        $invite     = $this->inviteService->validate($plainToken);

        if (! $invite) {
            return back()->withErrors(['token' => 'This invite link is no longer valid. Please contact support.']);
        }

        $hashedPassword = Hash::make($request->input('password'));
        $client         = $this->inviteService->accept($invite, $hashedPassword);

        // Kick off async onboarding: create site record, add Uptime Kuma monitor, send welcome email
        if ($invite->site_url) {
            OnboardClientJob::dispatch($client, $invite->site_url, isNewClient: true);
        }

        // Log the client into the portal guard
        auth('client')->login($client);

        return redirect()->route('portal.dashboard')
            ->with('success', 'Welcome to ReviveGuard! Your account is all set.');
    }
}
