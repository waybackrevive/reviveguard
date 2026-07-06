<?php

use App\Http\Controllers\Portal\AcceptInviteController;
use App\Http\Controllers\Portal\ActivateController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\CheckoutSuccessController;
use App\Http\Controllers\Portal\PasswordResetController;
use Illuminate\Support\Facades\Route;

// ── Post-payment confirmation (no auth required) ──────────────────────────────
Route::get('/welcome', [AuthController::class, 'welcome'])->name('portal.welcome');

// ── Account activation (magic link, no auth required) ────────────────────────
Route::get('/activate/{client}',  [ActivateController::class, 'show'])->name('portal.activate');
Route::post('/activate/{client}', [ActivateController::class, 'activate'])->name('portal.activate.submit');

// ── Invite acceptance ─────────────────────────────────────────────────────────
Route::get('/accept-invite',  [AcceptInviteController::class, 'show'])->name('portal.accept-invite');
Route::post('/accept-invite', [AcceptInviteController::class, 'store'])->name('portal.accept-invite.submit');

// ── Guest-only routes ─────────────────────────────────────────────────────────
Route::middleware('portal.guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [AuthController::class, 'login'])->name('portal.login.submit');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('portal.password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('portal.password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('portal.password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('portal.password.update');
});

// ── Authenticated — welcome setup (before onboarding gate) ───────────────────
Route::middleware(['portal.auth', 'portal.timeout'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('portal.logout');
    Route::get('/welcome-setup', \App\Livewire\Portal\WelcomeWizard::class)->name('portal.welcome-setup');
});

// ── Authenticated portal (requires welcome setup complete) ───────────────────
Route::middleware(['portal.auth', 'portal.timeout', 'portal.onboarded'])->group(function () {
    Route::redirect('/dashboard', '/portal/sites')->name('portal.dashboard');
    Route::redirect('/my-websites', '/portal/sites')->name('portal.my-websites');

    Route::get('/sites', \App\Livewire\Portal\MyWebsites::class)->name('portal.sites');
    Route::get('/sites/add', \App\Livewire\Portal\AddSite::class)->name('portal.sites.add');
    Route::get('/plugin/download', \App\Http\Controllers\Portal\PluginDownloadController::class)->name('portal.plugin.download');
    Route::get('/checkout/success', CheckoutSuccessController::class)->name('portal.checkout.success');
    Route::get('/sites/{site}', \App\Livewire\Portal\SiteShow::class)->name('portal.sites.show');

    Route::get('/alerts', \App\Livewire\Portal\Events::class)->name('portal.alerts');
    Route::redirect('/events', '/portal/alerts')->name('portal.events');

    Route::get('/reports', \App\Livewire\Portal\Reports::class)->name('portal.reports');
    Route::get('/tickets', \App\Livewire\Portal\Tickets::class)->name('portal.tickets');
    Route::get('/billing', \App\Livewire\Portal\Account::class)->name('portal.billing');
    Route::redirect('/account', '/portal/billing')->name('portal.account');

    Route::redirect('/backups', '/portal/sites')->name('portal.backups');
});
