<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * SiteEvaluation — tracks a Path B (new client) evaluation lifecycle.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $prospect_name
 * @property string      $prospect_email
 * @property string      $site_url
 * @property string      $site_type
 * @property string|null $concern
 * @property string      $status
 * @property string|null $admin_notes
 * @property string|null $recommended_plan_id
 * @property string|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $proposal_token_hash
 * @property \Carbon\Carbon|null $proposal_sent_at
 * @property \Carbon\Carbon|null $proposal_expires_at
 * @property string|null $converted_client_id
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon|null $declined_at
 * @property \Carbon\Carbon|null $expired_at
 * @property \Carbon\Carbon|null $followup_sent_at
 * @property bool        $waitlisted
 * @property string|null $month_slot
 * @property string|null $ip_address
 * @property string|null $referrer_url
 */
class SiteEvaluation extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'prospect_name',
        'prospect_email',
        'site_url',
        'site_type',
        'concern',
        'status',
        'admin_notes',
        'recommended_plan_id',
        'reviewed_by',
        'reviewed_at',
        'proposal_token_hash',
        'proposal_sent_at',
        'proposal_expires_at',
        'converted_client_id',
        'converted_at',
        'declined_at',
        'expired_at',
        'followup_sent_at',
        'waitlisted',
        'month_slot',
        'ip_address',
        'referrer_url',
    ];

    protected $casts = [
        'reviewed_at'         => 'datetime',
        'proposal_sent_at'    => 'datetime',
        'proposal_expires_at' => 'datetime',
        'converted_at'        => 'datetime',
        'declined_at'         => 'datetime',
        'expired_at'          => 'datetime',
        'followup_sent_at'    => 'datetime',
        'waitlisted'          => 'boolean',
    ];

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isReviewing(): bool  { return $this->status === 'reviewing'; }
    public function isProposed(): bool   { return $this->status === 'proposed'; }
    public function isConverted(): bool  { return $this->status === 'converted'; }
    public function isDeclined(): bool   { return $this->status === 'declined'; }
    public function isExpired(): bool    { return $this->status === 'expired'; }

    public function proposalIsValid(): bool
    {
        return $this->isProposed()
            && $this->proposal_token_hash !== null
            && $this->proposal_expires_at !== null
            && $this->proposal_expires_at->isFuture();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recommendedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'recommended_plan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    public function invite(): HasOne
    {
        return $this->hasOne(ClientInvite::class, 'evaluation_id');
    }
}
