<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteLoginToken;
use Illuminate\Support\Str;

/**
 * One-click WordPress admin login (WP Umbrella pattern).
 *
 * Platform mints a short-lived token; the installed agent plugin consumes it and
 * signs the user into wp-admin — no WP passwords stored on ReviveGuard.
 */
class WordPressSsoService
{
    public function canLogin(Site $site): bool
    {
        return $site->hasPaidSubscription()
            && $site->hasAgentConnected()
            && ! empty($site->url);
    }

    public function createLoginUrl(Site $site, ?string $clientId): string
    {
        if (! $this->canLogin($site)) {
            throw new \RuntimeException('Site must be connected and on an active plan before opening WordPress admin.');
        }

        $plain = Str::random(48);

        SiteLoginToken::create([
            'site_id'    => $site->id,
            'client_id'  => $clientId,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(2),
        ]);

        $base = rtrim((string) $site->url, '/');

        return $base . '/?reviveguard_sso=' . urlencode($plain);
    }

    public function consume(string $plainToken, Site $site): bool
    {
        $login = SiteLoginToken::where('site_id', $site->id)
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $login || ! $login->isValid()) {
            return false;
        }

        $login->update(['used_at' => now()]);

        return true;
    }
}
