<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPortalAccessController extends Controller
{
    public function __invoke(Request $request, Client $client): RedirectResponse
    {
        if (! $client->is_active) {
            return redirect()->route('portal.login')
                ->withErrors(['email' => 'This client account is suspended.']);
        }

        Auth::guard('client')->logout();

        Auth::guard('client')->login($client);

        $request->session()->regenerate();

        return redirect()->route(
            $client->hasCompletedOnboarding() ? 'portal.sites' : 'portal.welcome-setup'
        );
    }
}
