<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    /** Always eager-load plan so portal pages don't do extra queries */
    protected $with = ['plan'];

    protected $fillable = [
        'tenant_id',
        'client_id',
        'site_id',
        'plan_id',
        'stripe_subscription_id',
        'stripe_status',
        'stripe_current_period_end',
        'whop_membership_id',
        'whop_plan_id',
        'whop_status',
        'whop_valid_until',
        'activated_at',
        'cancelled_at',
        'suspended_at',
    ];

    protected $casts = [
        'whop_valid_until'          => 'datetime',
        'stripe_current_period_end' => 'datetime',
        'activated_at'              => 'datetime',
        'cancelled_at'              => 'datetime',
        'suspended_at'              => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if ($this->stripe_status) {
            return in_array($this->stripe_status, ['active', 'trialing'], true);
        }

        return $this->whop_status === 'active';
    }

    /** Client-facing billing status label */
    public function billingStatusLabel(): string
    {
        $status = $this->stripe_status ?? $this->whop_status ?? 'pending';

        return match ($status) {
            'active', 'trialing' => 'Active',
            'past_due', 'unpaid' => 'Past due',
            'canceled', 'cancelled' => 'Canceled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function nextBillingDate(): ?\Carbon\Carbon
    {
        return $this->stripe_current_period_end ?? $this->whop_valid_until;
    }

    public function stripeDashboardUrl(): ?string
    {
        if (! $this->stripe_subscription_id) {
            return null;
        }

        return \App\Support\StripeDashboard::subscriptionUrl($this->stripe_subscription_id);
    }

    public function billingStatusBadgeColor(): string
    {
        $status = $this->stripe_status ?? $this->whop_status ?? 'pending';

        return match ($status) {
            'active', 'trialing' => 'success',
            'past_due', 'unpaid' => 'warning',
            'canceled', 'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
