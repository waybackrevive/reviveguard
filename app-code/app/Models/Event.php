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
            'client_action'         => 'Client action',
            'uptime_probe'          => 'Uptime probe',
            'heartbeat_missed'      => 'Heartbeat missed',
            'site_recovered'        => 'Site recovered',
            'domain_expiry_warning' => 'Domain expiry',
            'uptime_kuma_alert'     => 'Uptime Kuma alert',
            'addon_order'           => 'Add-on order',
            'update_complete'       => 'Update complete',
            'ssl_expiry_warning'    => 'SSL expiry',
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
