<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'site_id',
        'subject',
        'message',
        'status',
        'priority',
        'type',
        'sla_due_at',
        'minutes_billed',
        'admin_reply',
        'replied_at',
        'resolved_at',
    ];

    protected $casts = [
        'replied_at'   => 'datetime',
        'resolved_at'  => 'datetime',
        'sla_due_at'   => 'datetime',
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

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress']);
    }

    public function isEmergencyRestore(): bool
    {
        return $this->type === 'emergency_restore';
    }

    public function isContentEdit(): bool
    {
        return $this->type === 'content_edit';
    }
}


