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
        'quantity'        => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'     => 'Order received',
            'in_progress' => 'In progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
            default       => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'     => 'bg-amber-100 text-amber-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed'   => 'bg-emerald-100 text-emerald-800',
            'cancelled'   => 'bg-gray-100 text-gray-600',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
