<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    // ── Forgot password ───────────────────────────────────────────────────

    public function showForgotForm(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('clients')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'If that email is registered, a reset link has been sent.');
        }

        // Return the same generic message to prevent email enumeration
        return back()->with('status', 'If that email is registered, a reset link has been sent.');
    }

    // ── Reset password ────────────────────────────────────────────────────

    public function showResetForm(string $token): View
    {
        return view('portal.auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'                 => ['required'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('clients')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($client, string $password): void {
                $client->forceFill([
                    'portal_password' => Hash::make($password),
                    'remember_token'  => Str::random(60),
                ])->save();

                event(new PasswordReset($client));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('portal.login')
                ->with('status', 'Password reset successfully. Please sign in.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
