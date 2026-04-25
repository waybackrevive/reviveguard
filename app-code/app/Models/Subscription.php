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
        'plan_id',
        'whop_membership_id',
        'whop_plan_id',
        'whop_status',
        'whop_valid_until',
        'activated_at',
        'cancelled_at',
        'suspended_at',
    ];

    protected $casts = [
        'whop_valid_until' => 'datetime',
        'activated_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
        'suspended_at'     => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->whop_status === 'active';
    }
}


