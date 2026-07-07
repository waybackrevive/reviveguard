<?php

namespace App\Models;

use App\Enums\BackupStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'status',
        'type',
        'b2_file_key',
        'b2_bucket',
        'size_bytes',
        'checksum_sha256',
        'error_message',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'status'       => BackupStatus::class,
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getSizeHumanAttribute(): string
    {
        if (! $this->size_bytes) {
            return 'Unknown';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size_bytes;
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function canDownload(): bool
    {
        return $this->status === BackupStatus::SUCCESS
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
}


