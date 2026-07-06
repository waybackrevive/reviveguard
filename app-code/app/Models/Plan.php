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
        return $this->features['backup_frequency'] ?? 'monthly';
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
            'monitor' => 'Uptime & SSL monitoring, monthly backups, email alerts',
            'guard'   => 'Daily backups, WP updates handled for you — best for most sites',
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

        $fromDb = $test ? $this->stripe_test_price_id : $this->stripe_price_id;

        if (! empty($fromDb)) {
            return $fromDb;
        }

        return $this->stripePriceFromEnv($test);
    }

    public function hasStripeCheckout(): bool
    {
        return ! empty($this->resolvedStripePriceId());
    }

    private function stripePriceFromEnv(bool $test): ?string
    {
        $envKey = match ($this->slug) {
            'monitor' => $test ? 'STRIPE_TEST_PRICE_MONITOR_ID' : 'STRIPE_PRICE_MONITOR_ID',
            'guard'   => $test ? 'STRIPE_TEST_PRICE_GUARD_ID' : 'STRIPE_PRICE_GUARD_ID',
            'shield'  => $test ? 'STRIPE_TEST_PRICE_SHIELD_ID' : 'STRIPE_PRICE_SHIELD_ID',
            default   => null,
        };

        if (! $envKey) {
            return null;
        }

        $value = env($envKey);

        return $value !== null && $value !== '' ? $value : null;
    }
}
