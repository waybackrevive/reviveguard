<?php

namespace App\Models;

use App\Enums\SiteStatus;
use App\Enums\SiteType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'plan_id',
        'subscription_id',
        'name',
        'url',
        'type',
        'agent_token',
        'agent_token_last4',
        'agent_version',
        'agent_installed_at',
        'status',
        'last_seen_at',
        'uptime_kuma_monitor_id',
        'uptime_30d',
        'uptime_7d',
        'ssl_expires_at',
        'ssl_issuer',
        'ssl_valid',
        'domain_expires_at',
        'registrar',
        'wp_version',
        'php_version',
        'plugin_count',
        'theme_name',
        'disk_usage_mb',
        'debug_mode',
        'is_active',
        'notes',
    ];

    protected $hidden = [
        'agent_token', // never expose the hashed token
    ];

    protected $casts = [
        'status'             => SiteStatus::class,
        'type'               => SiteType::class,
        'agent_installed_at' => 'datetime',
        'last_seen_at'       => 'datetime',
        'ssl_expires_at'     => 'date',
        'domain_expires_at'  => 'date',
        'ssl_valid'          => 'boolean',
        'debug_mode'         => 'boolean',
        'is_active'          => 'boolean',
        'uptime_30d'         => 'decimal:2',
        'uptime_7d'          => 'decimal:2',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(SiteCommand::class);
    }

    public function pluginSnapshots(): HasMany
    {
        return $this->hasMany(PluginSnapshot::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function latestPluginSnapshot(): HasOne
    {
        return $this->hasOne(PluginSnapshot::class)->ofMany(['created_at' => 'max']);
    }

    public function latestBackup(): HasOne
    {
        return $this->hasOne(Backup::class)->ofMany(['created_at' => 'max']);
    }

    public function pendingCommand(): HasOne
    {
        // Using simple HasOne + where + orderBy avoids MAX/MIN(uuid) on PostgreSQL.
        // Eager loads correctly for single-site access in the heartbeat controller.
        return $this->hasOne(SiteCommand::class)
            ->where('status', 'pending')
            ->orderBy('created_at');
    }

    public function isDown(): bool
    {
        return $this->status === SiteStatus::DOWN;
    }

    public function sslExpiresInDays(): ?int
    {
        if (!$this->ssl_expires_at) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->ssl_expires_at, false);
    }

    public function domainExpiresInDays(): ?int
    {
        if (!$this->domain_expires_at) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->domain_expires_at, false);
    }
}
