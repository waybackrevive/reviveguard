<?php

namespace App\Support;

/**
 * Canonical base URL for the client portal (emails, invites, signed links).
 * Prefer REVIVEGUARD_API_URL so links use app.reviveguard.com, not the marketing domain.
 */
class PortalUrl
{
    public static function base(): string
    {
        return rtrim(config('services.reviveguard.api_url', config('app.url')), '/');
    }

    public static function to(string $path = ''): string
    {
        if ($path === '') {
            return self::base();
        }

        return self::base() . '/' . ltrim($path, '/');
    }
}
