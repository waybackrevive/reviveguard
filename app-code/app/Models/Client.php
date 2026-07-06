<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use HasUuids, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'portal_password',
        'activation_token',
        'activation_expires_at',
        'company_name',
        'workspace_name',
        'account_type',
        'sites_managed_range',
        'phone',
        'timezone',
        'whop_member_id',
        'stripe_id',
        'stripe_test_id',
        'is_active',
        'path',
        'source',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'portal_password',
        'activation_token',
        'remember_token',
    ];

    protected $casts = [
        'is_active'                => 'boolean',
        'last_login_at'            => 'datetime',
        'activation_expires_at'    => 'datetime',
        'onboarding_completed_at'  => 'datetime',
    ];

    /**
     * Map portal auth to portal_password field (Laravel <11 contract).
     */
    public function getAuthPassword(): string
    {
        return $this->portal_password ?? '';
    }

    /**
     * Laravel 11 auth contract — explicit password column name.
     */
    public function getAuthPasswordName(): string
    {
        return 'portal_password';
    }

    /**
     * Always return the UUID as a plain string so the session guard stores
     * only the UUID (never the serialised model object).
     */
    public function getAuthIdentifier(): string
    {
        return (string) $this->getKey();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

  /**
     * Best plan across paid sites — used for support tier (email vs phone).
     */
    public function bestSupportPlan(): ?Plan
    {
        $rank = ['monitor' => 1, 'guard' => 2, 'shield' => 3];

        $fromSites = $this->sites()
            ->with('plan')
            ->whereHas('subscription', function ($q) {
                $q->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            })
            ->get()
            ->map(fn (Site $site) => $site->plan)
            ->filter()
            ->sortByDesc(fn (Plan $plan) => $rank[$plan->slug] ?? 0)
            ->first();

        return $fromSites ?? optional($this->activeSubscription)->plan;
    }

    public function activeSubscription(): HasOne
    {
        // Note: ofMany() generates MAX(id) which fails on PostgreSQL UUID columns.
        // Use a plain HasOne ordered by created_at instead.
        return $this->hasOne(Subscription::class)
            ->where(function ($q) {
                $q->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            })
            ->orderByDesc('created_at');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(ClientInvite::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /** Stripe Customer ID for the active billing mode. */
    public function stripeCustomerId(): ?string
    {
        if (\App\Support\StripeConfig::isTestMode()) {
            return $this->stripe_test_id ?: null;
        }

        return $this->stripe_id;
    }

    public function stripeCustomerIdColumn(): string
    {
        return \App\Support\StripeConfig::isTestMode() ? 'stripe_test_id' : 'stripe_id';
    }
}
