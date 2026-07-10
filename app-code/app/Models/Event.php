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

    public function isClientInitiated(): bool
    {
        return $this->type === 'client_action';
    }

    public function sourceLabel(): string
    {
        return $this->isClientInitiated() ? 'Client' : 'System';
    }

    public function sourceBadgeColor(): string
    {
        return $this->isClientInitiated() ? 'info' : 'gray';
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? str_replace('_', ' ', ucfirst((string) $this->type));
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'client_action'              => 'Something you did in the portal',
            'uptime_probe'               => 'Uptime check',
            'heartbeat_missed'           => 'Site stopped checking in',
            'site_recovered'             => 'Site came back online',
            'domain_expiry_warning'      => 'Domain expiring soon',
            'uptime_kuma_alert'          => 'Downtime alert',
            'addon_order'                => 'Add-on order',
            'update_complete'            => 'WordPress update completed',
            'update_failed'              => 'WordPress update failed',
            'update_deferred'            => 'Update waiting for backup',
            'rollback_complete'          => 'Site restored from backup',
            'rollback_failed'            => 'Automatic restore failed',
            'rollback_queued'            => 'Restore queued',
            'backup_complete'            => 'Backup completed',
            'backup_failed'              => 'Backup failed',
            'malware_scan_complete'      => 'Security scan — all clear',
            'malware_scan_alert'         => 'Security scan found issues',
            'malware_scan_failed'        => 'Security scan could not finish',
            'broken_link_audit_complete' => 'Broken link check completed',
            'broken_link_audit_failed'   => 'Broken link check failed',
            'quarterly_security_audit'   => 'Quarterly security review',
            'quarterly_seo_snapshot'     => 'Quarterly SEO review',
            'ssl_expiry_warning'         => 'SSL certificate expiring soon',
        ];
    }

    /** @return array<string, string> */
    public static function typeFilterOptions(): array
    {
        $labels = self::typeLabels();

        $fromDb = self::query()
            ->where('tenant_id', config('app.tenant_id'))
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return $fromDb
            ->mapWithKeys(fn (string $type) => [$type => $labels[$type] ?? str_replace('_', ' ', ucfirst($type))])
            ->all();
    }
}
