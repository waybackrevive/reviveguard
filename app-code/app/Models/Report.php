<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'client_id',
        'type',
        'period',
        'status',
        'b2_file_key',
        'b2_bucket',
        'size_bytes',
        'error_message',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'email_sent'    => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function canDownload(): bool
    {
        return $this->status === 'completed'
            && filled($this->b2_bucket)
            && filled($this->b2_file_key);
    }

    public function signedDownloadUrl(int $ttlSeconds = 3600): ?string
    {
        if (! $this->canDownload()) {
            return null;
        }

        $url = app(\App\Services\BackblazeService::class)
            ->getSignedUrl($this->b2_bucket, $this->b2_file_key, $ttlSeconds);

        return $url !== '#' ? $url : null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'completed'  => 'Completed',
            'generating' => 'Generating',
            'failed'     => 'Failed',
            'pending'    => 'Pending',
            'ready'      => 'Ready',
            default      => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'completed', 'ready' => 'success',
            'failed'            => 'danger',
            'generating'        => 'warning',
            default             => 'gray',
        };
    }
}


