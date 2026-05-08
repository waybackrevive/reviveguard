<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientInvite;
use App\Models\PlatformSetting;
use App\Models\SiteEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * InviteService — creates, sends, validates, and manages client invite tokens.
 *
 * Security design:
 *   - Plain token = random_bytes(32) → base64url encoded (256-bit entropy)
 *   - Only SHA-256 hash stored in DB — plain token never persisted
 *   - Even if DB is leaked, tokens cannot be reversed
 *   - Tokens expire after 72 hours by default
 *   - One-time use: accepted_at set on first valid use
 */
class InviteService
{
    private string $from;
    private string $appUrl;

    public function __construct()
    {
        $this->from   = PlatformSetting::get('resend_from', config('services.resend.from', 'team@reviveguard.com')) ?? 'team@reviveguard.com';
        $this->appUrl = rtrim(config('app.url'), '/');
    }

    // ── Create invite ─────────────────────────────────────────────────────────

    /**
     * Create a single invite record without sending.
     * Returns [ClientInvite, plainToken].
     *
     * @return array{0: ClientInvite, 1: string}
     */
    public function create(
        string  $tenantId,
        string  $name,
        string  $email,
        string  $path,           // 'alumni' | 'evaluation'
        ?string $siteUrl         = null,
        ?string $evaluationId    = null,
        ?string $notes           = null,
        ?string $createdBy       = null,
        int     $ttlHours        = 72,
    ): array {
        $plainToken = $this->generateToken();
        $tokenHash  = hash('sha256', $plainToken);

        $invite = ClientInvite::create([
            'tenant_id'     => $tenantId,
            'name'          => $name,
            'email'         => strtolower(trim($email)),
            'site_url'      => $siteUrl,
            'path'          => $path,
            'evaluation_id' => $evaluationId,
            'token_hash'    => $tokenHash,
            'expires_at'    => Carbon::now()->addHours($ttlHours),
            'notes'         => $notes,
            'created_by'    => $createdBy,
        ]);

        return [$invite, $plainToken];
    }

    /**
     * Create AND send the invite email in one call.
     */
    public function createAndSend(
        string  $tenantId,
        string  $name,
        string  $email,
        string  $path,
        ?string $siteUrl      = null,
        ?string $evaluationId = null,
        ?string $notes        = null,
        ?string $createdBy    = null,
        int     $ttlHours     = 72,
    ): ClientInvite {
        [$invite, $plainToken] = $this->create(
            tenantId:    $tenantId,
            name:        $name,
            email:       $email,
            path:        $path,
            siteUrl:     $siteUrl,
            evaluationId: $evaluationId,
            notes:       $notes,
            createdBy:   $createdBy,
            ttlHours:    $ttlHours,
        );

        $this->sendEmail($invite, $plainToken);

        return $invite;
    }

    // ── Send/resend ───────────────────────────────────────────────────────────

    /**
     * Resend an expired invite — generates a brand new token and extends expiry.
     */
    public function resend(ClientInvite $invite, int $ttlHours = 72): void
    {
        $plainToken = $this->generateToken();
        $tokenHash  = hash('sha256', $plainToken);

        $invite->update([
            'token_hash'    => $tokenHash,
            'expires_at'    => Carbon::now()->addHours($ttlHours),
            'revoked_at'    => null,   // un-revoke if it was revoked
            'accepted_at'   => null,   // reset if somehow reused
            'email_sent_at' => null,
        ]);

        $this->sendEmail($invite, $plainToken);
    }

    /**
     * Revoke an invite (mark it as unusable, even if not yet expired).
     */
    public function revoke(ClientInvite $invite): void
    {
        $invite->update(['revoked_at' => Carbon::now()]);
    }

    // ── Validate token ────────────────────────────────────────────────────────

    /**
     * Validate an incoming plain token from the URL.
     *
     * Returns the ClientInvite model on success.
     * Returns null if token is invalid, expired, already used, or revoked.
     */
    public function validate(string $plainToken): ?ClientInvite
    {
        if (empty($plainToken)) {
            return null;
        }

        $hash   = hash('sha256', $plainToken);
        $invite = ClientInvite::where('token_hash', $hash)->first();

        if (! $invite) {
            Log::warning('InviteService: token not found', ['hash_prefix' => substr($hash, 0, 8)]);
            return null;
        }

        if ($invite->isRevoked()) {
            Log::warning('InviteService: token revoked', ['invite_id' => $invite->id]);
            return null;
        }

        if ($invite->isAccepted()) {
            Log::warning('InviteService: token already accepted', ['invite_id' => $invite->id]);
            return null;
        }

        if ($invite->isExpired()) {
            Log::warning('InviteService: token expired', ['invite_id' => $invite->id]);
            return null;
        }

        return $invite;
    }

    /**
     * Accept a validated invite — creates the client account and links everything.
     *
     * Returns the newly created Client.
     */
    public function accept(ClientInvite $invite, string $hashedPassword): Client
    {
        // Create the client record
        $client = Client::create([
            'tenant_id'    => $invite->tenant_id,
            'name'         => $invite->name,
            'email'        => $invite->email,
            'portal_password' => $hashedPassword,
            'path'         => $invite->path,
            'source'       => $invite->path === 'alumni' ? 'waybackrevive_restored' : 'inbound',
            'is_active'    => true,
        ]);

        // Link invite to the new client and mark accepted
        $invite->update([
            'client_id'   => $client->id,
            'accepted_at' => Carbon::now(),
        ]);

        // If Path B, link evaluation to the converted client
        if ($invite->evaluation_id) {
            SiteEvaluation::where('id', $invite->evaluation_id)->update([
                'converted_client_id' => $client->id,
                'converted_at'        => Carbon::now(),
                'status'              => 'converted',
            ]);
        }

        Log::info('InviteService: invite accepted, client created', [
            'invite_id' => $invite->id,
            'client_id' => $client->id,
            'path'      => $invite->path,
        ]);

        return $client;
    }

    // ── Bulk create from array ────────────────────────────────────────────────

    /**
     * Bulk create invites from an array of rows.
     * Each row: ['name' => ..., 'email' => ..., 'site_url' => ...]
     *
     * Returns array of [ClientInvite, plainToken] pairs (does NOT send emails).
     * Caller is responsible for queueing the sends.
     *
     * @param  array<int, array{name: string, email: string, site_url?: string}>  $rows
     * @return array<int, array{0: ClientInvite, 1: string}>
     */
    public function bulkCreate(string $tenantId, array $rows, string $path = 'alumni', ?string $createdBy = null): array
    {
        $results = [];

        foreach ($rows as $row) {
            // Skip rows with missing required fields
            if (empty($row['name']) || empty($row['email'])) {
                Log::warning('InviteService: bulkCreate skipping row missing name or email', ['row' => $row]);
                continue;
            }

            // Skip if active invite already exists for this email
            $exists = ClientInvite::where('email', strtolower(trim($row['email'])))
                ->where('tenant_id', $tenantId)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($exists) {
                Log::info('InviteService: skipping duplicate active invite', ['email' => $row['email']]);
                continue;
            }

            $results[] = $this->create(
                tenantId:  $tenantId,
                name:      $row['name'],
                email:     $row['email'],
                path:      $path,
                siteUrl:   $row['site_url'] ?? null,
                createdBy: $createdBy,
            );
        }

        return $results;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Generate a cryptographically secure URL-safe base64 token (256-bit).
     */
    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Send the invite email via Resend.
     * Template differs by path: alumni vs evaluation.
     */
    public function sendEmail(ClientInvite $invite, string $plainToken): void
    {
        $acceptUrl = $this->appUrl . '/portal/accept-invite?token=' . urlencode($plainToken);

        $view    = $invite->path === 'alumni' ? 'emails.invite-alumni' : 'emails.invite-evaluation';
        $subject = $invite->path === 'alumni'
            ? 'Your exclusive invitation to protect ' . ($invite->site_url ? parse_url($invite->site_url, PHP_URL_HOST) : 'your website')
            : 'Your ReviveGuard site evaluation proposal';

        try {
            $html = view($view, [
                'name'      => $invite->name,
                'siteUrl'   => $invite->site_url,
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $invite->expires_at->format('F j, Y \a\t g:i A') . ' UTC',
            ])->render();

            Resend::emails()->send([
                'from'    => $this->from,
                'to'      => $invite->email,
                'subject' => $subject,
                'html'    => $html,
            ]);

            $invite->update(['email_sent_at' => Carbon::now()]);

            Log::info('InviteService: invite email sent', [
                'invite_id' => $invite->id,
                'path'      => $invite->path,
                'email'     => $invite->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('InviteService: failed to send invite email', [
                'invite_id' => $invite->id,
                'email'     => $invite->email,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
