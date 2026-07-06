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
}
