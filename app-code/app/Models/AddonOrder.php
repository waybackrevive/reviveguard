<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonOrder extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'site_id',
        'addon_slug',
        'addon_name',
        'price_label',
        'amount_cents',
        'stripe_checkout_session_id',
        'paid_at',
        'quantity',
        'client_notes',
        'status',
        'team_update',
        'team_updated_at',
        'completed_at',
    ];

    protected $casts = [
        'team_updated_at' => 'datetime',
        'completed_at'    => 'datetime',
        'paid_at'         => 'datetime',
        'quantity'        => 'integer',
        'amount_cents'    => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isAwaitingPayment(): bool
    {
        return in_array($this->status, ['awaiting_payment', 'pending'], true) && $this->paid_at === null;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_payment', 'pending' => 'Awaiting payment',
            'in_progress'                 => 'Paid — in progress',
            'completed'                   => 'Completed',
            'cancelled'                   => 'Cancelled',
            default                       => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'awaiting_payment', 'pending' => 'bg-amber-100 text-amber-800',
            'in_progress'                 => 'bg-blue-100 text-blue-800',
            'completed'                   => 'bg-emerald-100 text-emerald-800',
            'cancelled'                   => 'bg-gray-100 text-gray-600',
            default                       => 'bg-gray-100 text-gray-700',
        };
    }

    public function formattedAmount(): ?string
    {
        if ($this->amount_cents === null) {
            return null;
        }

        return '$' . number_format($this->amount_cents / 100, 2);
    }
}
