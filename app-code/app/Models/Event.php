<?php

namespace App\Models;

use App\Enums\EventSeverity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'type',
        'severity',
        'title',
        'message',
        'metadata',
        'resolved',
        'resolved_at',
        // 'occurred_at' does not exist — column is created_at
    ];

    protected $casts = [
        'severity'    => EventSeverity::class,
        'metadata'    => 'array',
        'resolved'    => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', EventSeverity::CRITICAL->value);
    }
}
