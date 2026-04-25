<?php

namespace App\Models;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCommand extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'type',
        'status',
        'params',
        'result',
        'error_message',
        'queued_at',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'type'         => CommandType::class,
        'status'       => CommandStatus::class,
        'params'       => 'array',
        'result'       => 'array',
        'queued_at'    => 'datetime',
        'sent_at'      => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
