<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClientInvite — signed invite token record.
 *
 * Only the SHA-256 hash of the plain token is stored here.
 * The plain token is sent in the invite email URL and never persisted.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $name
 * @property string      $email
 * @property string|null $site_url
 * @property string      $path          'alumni' | 'evaluation'
 * @property string|null $evaluation_id
 * @property string|null $client_id
 * @property string      $token_hash
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $revoked_at
 * @property \Carbon\Carbon|null $email_sent_at
 * @property string|null $notes
 * @property string|null $created_by
 */
class ClientInvite extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'site_url',
        'path',
        'evaluation_id',
        'client_id',
        'token_hash',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'email_sent_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'accepted_at'   => 'datetime',
        'revoked_at'    => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isRevoked())  return 'revoked';
        if ($this->isAccepted()) return 'accepted';
        if ($this->isExpired())  return 'expired';
        return 'pending';
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(SiteEvaluation::class, 'evaluation_id');
    }
}
