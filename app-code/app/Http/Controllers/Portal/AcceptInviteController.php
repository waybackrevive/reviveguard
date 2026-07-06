<?php

namespace App\Http\Controllers\Portal;

use App\Enums\SiteStatus;
use App\Http\Controllers\Controller;
use App\Models\Site;
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

        // Create site record now if the invite included a site URL.
        // Agent token is generated here so the client can install the plugin immediately.
        // Uptime Kuma monitor is created when the first heartbeat arrives (or on Whop webhook).
        if ($invite->site_url) {
            $rawToken = bin2hex(random_bytes(32));
            Site::create([
                'tenant_id'         => $client->tenant_id,
                'client_id'         => $client->id,
                'name'              => parse_url($invite->site_url, PHP_URL_HOST) ?? $client->name . "'s Website",
                'url'               => rtrim($invite->site_url, '/'),
                'status'            => SiteStatus::PENDING,
                'agent_token'       => hash('sha256', $rawToken),
                'agent_token_last4' => substr($rawToken, -4),
                'is_active'         => true,
            ]);
        }

        // Log the client into the portal guard
        auth('client')->login($client);
        $request->session()->regenerate();

        return redirect()->route('portal.welcome-setup')
            ->with('success', 'Welcome to ReviveGuard! Your account is all set.');
    }
}
