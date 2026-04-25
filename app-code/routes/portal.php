<?php

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\PasswordResetController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/dashboard', \App\Livewire\Portal\Dashboard::class)->name('portal.dashboard');
    Route::get('/events',    \App\Livewire\Portal\Events::class)->name('portal.events');
    Route::get('/reports',   \App\Livewire\Portal\Reports::class)->name('portal.reports');
    Route::get('/backups',   \App\Livewire\Portal\Backups::class)->name('portal.backups');
    Route::get('/tickets',   \App\Livewire\Portal\Tickets::class)->name('portal.tickets');
    Route::get('/account',   \App\Livewire\Portal\Account::class)->name('portal.account');
});
