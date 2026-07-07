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
        'client_label',
        'url',
        'type',
        'agent_token',
        'agent_token_last4',
        'agent_version',
        'agent_installed_at',
        'status',
        'last_seen_at',
        'uptime_kuma_monitor_id',
        'monitor_interval_minutes',
        'monitor_region',
        'monitoring_paused',
        'monitoring_paused_at',
        'last_uptime_probe_at',
        'uptime_30d',
        'uptime_7d',
        'ssl_expires_at',
        'ssl_issuer',
        'ssl_valid',
        'domain_expires_at',
        'registrar',
        'whoisxml_last_checked_at',
        'wp_version',
        'php_version',
        'plugin_count',
        'theme_name',
        'disk_usage_mb',
        'debug_mode',
        'is_active',
        'notes',
        'hosting_credentials',
    ];

    protected $hidden = [
        'agent_token', // never expose the hashed token
    ];

    protected $casts = [
        'status'             => SiteStatus::class,
        'type'               => SiteType::class,
        'agent_installed_at' => 'datetime',
        'last_seen_at'       => 'datetime',
        'last_uptime_probe_at' => 'datetime',
        'monitoring_paused_at' => 'datetime',
        'monitoring_paused'    => 'boolean',
        'ssl_expires_at'     => 'date',
        'domain_expires_at'          => 'date',
        'whoisxml_last_checked_at'    => 'datetime',
        'ssl_valid'          => 'boolean',
        'debug_mode'         => 'boolean',
        'is_active'          => 'boolean',
        'uptime_30d'              => 'decimal:2',
        'uptime_7d'               => 'decimal:2',
        'hosting_credentials'     => 'encrypted:array',
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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function uptimeProbes(): HasMany
    {
        return $this->hasMany(SiteUptimeProbe::class);
    }

    public function latestPluginSnapshot(): HasOne
    {
        return $this->hasOne(PluginSnapshot::class)->ofMany(['created_at' => 'max']);
    }

    /**
     * Latest backup for this site.
     *
     * Avoids ofMany()/latestOfMany() — those generate MAX(id) which fails on PostgreSQL UUID columns.
     */
    public function latestBackup(): HasOne
    {
        return $this->hasOne(Backup::class)->orderByDesc('created_at');
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

    public function hasAgentConnected(): bool
    {
        return $this->last_seen_at !== null;
    }

    public function hasPaidSubscription(): bool
    {
        return $this->subscription_id !== null
            && $this->subscription?->isActive();
    }

    /**
     * Client-facing status bucket — never "down" for sites still in setup.
     *
     * @return 'setup'|'protected'|'warning'|'issue'|'checkout'
     */
    public function portalStatusKey(): string
    {
        if (! $this->hasPaidSubscription()) {
            return $this->hasAgentConnected() ? 'checkout' : 'setup';
        }

        if (! $this->hasAgentConnected()) {
            return 'setup';
        }

        return match ($this->status) {
            SiteStatus::DOWN      => 'issue',
            SiteStatus::WARNING   => 'warning',
            SiteStatus::ACTIVE    => 'protected',
            SiteStatus::SUSPENDED => 'setup',
            default               => 'setup',
        };
    }

    public function portalStatusLabel(): string
    {
        return match ($this->portalStatusKey()) {
            'checkout'  => 'Complete checkout',
            'setup'     => 'Setup needed',
            'protected' => 'Protected',
            'warning'   => 'Needs attention',
            'issue'     => "We're on it",
            default     => 'Setup needed',
        };
    }

    public function portalStatusColor(): string
    {
        return match ($this->portalStatusKey()) {
            'protected' => 'success',
            'warning'   => 'warning',
            'issue'     => 'danger',
            'checkout'  => 'info',
            default     => 'gray',
        };
    }

    public function displayName(): string
    {
        return $this->client_label ?: $this->name;
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

    public function hostname(): ?string
    {
        $host = parse_url((string) $this->url, PHP_URL_HOST);

        return $host ? strtolower($host) : null;
    }

    /** Domain used for RDAP / registrar expiry (strips www.). */
    public function registrableDomain(): ?string
    {
        $host = $this->hostname();

        return $host ? preg_replace('/^www\./i', '', $host) : null;
    }

    /** Paid site — metric not populated yet (first scan running). */
    public function metricSyncing(string $metric): bool
    {
        if (! $this->hasPaidSubscription()) {
            return false;
        }

        return match ($metric) {
            'uptime' => $this->uptime_30d === null,
            'ssl'    => $this->ssl_expires_at === null,
            'domain' => $this->domain_expires_at === null,
            default  => false,
        };
    }

    public function healthMetricsSyncing(): bool
    {
        return $this->metricSyncing('uptime')
            || $this->metricSyncing('ssl')
            || $this->metricSyncing('domain');
    }

    public function scopeProtected($query)
    {
        return $query->whereHas('subscription', fn ($q) => $q->whereIn('stripe_status', ['active', 'trialing']));
    }

    public function scopeWherePaid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('subscription_id')
            ->whereHas('subscription', function (\Illuminate\Database\Eloquent\Builder $sub): void {
                $sub->whereIn('stripe_status', ['active', 'trialing'])
                    ->orWhere('whop_status', 'active');
            });
    }

    public function scopeWhereUnpaid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function (\Illuminate\Database\Eloquent\Builder $inner): void {
            $inner->whereNull('subscription_id')
                ->orWhereHas('subscription', function (\Illuminate\Database\Eloquent\Builder $sub): void {
                    $sub->where(function (\Illuminate\Database\Eloquent\Builder $stripe): void {
                        $stripe->whereNotIn('stripe_status', ['active', 'trialing'])
                            ->orWhereNull('stripe_status');
                    })->where(function (\Illuminate\Database\Eloquent\Builder $whop): void {
                        $whop->where('whop_status', '!=', 'active')
                            ->orWhereNull('whop_status');
                    });
                });
        });
    }

    public function scopeWherePortalStatus(\Illuminate\Database\Eloquent\Builder $query, string $key): \Illuminate\Database\Eloquent\Builder
    {
        return match ($key) {
            'protected' => $query->wherePaid()
                ->whereNotNull('last_seen_at')
                ->where('status', SiteStatus::ACTIVE),
            'warning' => $query->wherePaid()
                ->whereNotNull('last_seen_at')
                ->where('status', SiteStatus::WARNING),
            'issue' => $query->wherePaid()
                ->whereNotNull('last_seen_at')
                ->where('status', SiteStatus::DOWN),
            'checkout' => $query->whereUnpaid()
                ->whereNotNull('last_seen_at'),
            'setup' => $query->where(function (\Illuminate\Database\Eloquent\Builder $inner): void {
                $inner->where(fn (\Illuminate\Database\Eloquent\Builder $q) => $q->whereUnpaid()->whereNull('last_seen_at'))
                    ->orWhere(fn (\Illuminate\Database\Eloquent\Builder $q) => $q->wherePaid()->whereNull('last_seen_at'))
                    ->orWhere(fn (\Illuminate\Database\Eloquent\Builder $q) => $q->wherePaid()
                        ->whereNotNull('last_seen_at')
                        ->whereIn('status', [SiteStatus::SUSPENDED, SiteStatus::PENDING]));
            }),
            default => $query,
        };
    }

    public function scopeMonitoringActive($query)
    {
        return $query->where('monitoring_paused', false);
    }

    public function isMonitoringPaused(): bool
    {
        return (bool) $this->monitoring_paused;
    }
}
