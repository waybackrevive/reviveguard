<?php

namespace App\Http\Controllers\Portal;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles the one-time magic-link account activation flow.
 *
 * Flow:
 *  1. Client receives welcome email with signed activation URL
 *  2. GET  /portal/activate/{client}?token=xxx  → show set-password form
 *  3. POST /portal/activate/{client}?token=xxx  → validate + set password + auto-login
 */
class ActivateController extends Controller
{
    /**
     * Show the set-password activation form.
     */
    public function show(Request $request, Client $client): View|RedirectResponse
    {
        if (! $this->tokenValid($request, $client)) {
            return redirect()->route('portal.login')
                ->withErrors(['email' => 'This activation link is invalid or has expired. Use "Forgot password" to regain access.']);
        }

        return view('portal.auth.activate', [
            'client' => $client,
            'token'  => $request->query('token'),
        ]);
    }

    /**
     * Set the password, clear the activation token, and log the client in.
     */
    public function activate(Request $request, Client $client): RedirectResponse
    {
        if (! $this->tokenValid($request, $client)) {
            return redirect()->route('portal.login')
                ->withErrors(['email' => 'This activation link is invalid or has expired. Use "Forgot password" to regain access.']);
        }

        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        // Set password and clear the one-time activation token
        $client->update([
            'portal_password'       => Hash::make($request->input('password')),
            'activation_token'      => null,
            'activation_expires_at' => null,
            'is_active'             => true,
        ]);

        // Auto-login and redirect to dashboard
        Auth::guard('client')->login($client, remember: true);
        $request->session()->regenerate();

        return redirect()->route('portal.welcome-setup')
            ->with('status', 'Your account is active. Welcome to ReviveGuard!');
    }

    /**
     * Verify the plain-text token from the query string against the stored hash.
     */
    private function tokenValid(Request $request, Client $client): bool
    {
        $token = (string) $request->query('token', '');

        if ($token === '' || $client->activation_token === null) {
            return false;
        }

        if ($client->activation_expires_at === null || $client->activation_expires_at->isPast()) {
            return false;
        }

        return Hash::check($token, $client->activation_token);
    }
}
