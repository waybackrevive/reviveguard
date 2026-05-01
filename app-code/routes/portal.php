<?php

use App\Http\Controllers\Portal\AcceptInviteController;
use App\Http\Controllers\Portal\ActivateController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\PasswordResetController;
use Illuminate\Support\Facades\Route;

// ── Post-payment confirmation (no auth required) ──────────────────────────────
// Whop redirects here after successful checkout. Just a "check your email" page.
Route::get('/welcome', [AuthController::class, 'welcome'])->name('portal.welcome');

// ── Account activation (magic link, no auth required) ────────────────────────
// Token is validated inside the controller (Hash::check against stored hash).
Route::get('/activate/{client}',  [ActivateController::class, 'show'])->name('portal.activate');
Route::post('/activate/{client}', [ActivateController::class, 'activate'])->name('portal.activate.submit');

// ── Invite acceptance (new invite-first onboarding, no auth required) ─────────
// Plain token from email URL; token is validated via SHA-256 lookup in InviteService.
Route::get('/accept-invite',  [AcceptInviteController::class, 'show'])->name('portal.accept-invite');
Route::post('/accept-invite', [AcceptInviteController::class, 'store'])->name('portal.accept-invite.submit');

// ── Guest-only routes (redirect to dashboard if already logged in) ────────────
Route::middleware('portal.guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [AuthController::class, 'login'])->name('portal.login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('portal.password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('portal.password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('portal.password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('portal.password.update');
});

// ── Authenticated portal routes ───────────────────────────────────────────────
Route::middleware('portal.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('portal.logout');

    Route::get('/dashboard',    \App\Livewire\Portal\Dashboard::class)->name('portal.dashboard');
    Route::get('/my-websites',  \App\Livewire\Portal\MyWebsites::class)->name('portal.my-websites');
    Route::get('/events',       \App\Livewire\Portal\Events::class)->name('portal.events');
    Route::get('/reports',      \App\Livewire\Portal\Reports::class)->name('portal.reports');
    Route::get('/backups',      \App\Livewire\Portal\Backups::class)->name('portal.backups');
    Route::get('/tickets',      \App\Livewire\Portal\Tickets::class)->name('portal.tickets');
    Route::get('/account',      \App\Livewire\Portal\Account::class)->name('portal.account');
});
