<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plan extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'price_monthly',
        'stripe_price_id',
        'stripe_test_price_id',
        'whop_plan_id',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features'      => 'array',
        'price_monthly' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function getBackupFrequencyAttribute(): string
    {
        return $this->features['backup_frequency'] ?? 'twice_monthly';
    }

    public function getBackupsPerMonthAttribute(): ?int
    {
        $value = $this->features['backups_per_month'] ?? null;

        return $value !== null ? (int) $value : null;
    }

    public function getRetentionDaysAttribute(): int
    {
        return $this->features['backup_retention_days'] ?? 30;
    }

    public function getSupportTicketsPerMonthAttribute(): int
    {
        return $this->features['support_tickets_per_month'] ?? 0;
    }

    public function portalSummary(): string
    {
        return match ($this->slug) {
            'monitor' => 'Uptime & SSL monitoring, 2× monthly backups, unlimited email support',
            'guard'   => 'Weekly backups, WP updates handled for you — best for most sites',
            'shield'  => 'Priority support, extended backup retention, full hands-off care',
            default   => '',
        };
    }

    public function isRecommended(): bool
    {
        return $this->slug === 'guard';
    }

    /** Stripe Price ID for the active mode (live or test). */
    public function resolvedStripePriceId(): ?string
    {
        $test = \App\Support\StripeConfig::isTestMode();

        $candidates = [
            $test ? $this->stripe_test_price_id : $this->stripe_price_id,
            $this->stripePriceFromConfig($test),
        ];

        foreach ($candidates as $id) {
            if (\App\Support\StripePriceId::isValid($id)) {
                return $id;
            }
        }

        return null;
    }

    public function hasStripeCheckout(): bool
    {
        return $this->checkoutUnavailableReason() === null;
    }

    /**
     * Why checkout cannot start for this plan, or null when ready.
     */
    public function checkoutUnavailableReason(): ?string
    {
        $id = $this->resolvedStripePriceId();

        if (empty($id)) {
            $test = \App\Support\StripeConfig::isTestMode();
            $mode = $test ? 'test' : 'live';
            $env  = match ($this->slug) {
                'monitor' => $test ? 'STRIPE_TEST_PRICE_MONITOR_ID' : 'STRIPE_PRICE_MONITOR_ID',
                'guard'   => $test ? 'STRIPE_TEST_PRICE_GUARD_ID' : 'STRIPE_PRICE_GUARD_ID',
                'shield'  => $test ? 'STRIPE_TEST_PRICE_SHIELD_ID' : 'STRIPE_PRICE_SHIELD_ID',
                default   => 'STRIPE_PRICE_*_ID',
            };

            return "Stripe {$mode} price is not set for the {$this->name} plan. Add {$env} to .env, run `php artisan config:clear` (or redeploy), then `php artisan plans:sync-stripe-prices`.";
        }

        return \App\Support\StripePriceId::describeProblem($id, "{$this->name} plan");
    }

    private function stripePriceFromConfig(bool $test): ?string
    {
        return \App\Support\PlanStripePriceSync::configPrice($this->slug, $test);
    }
}
