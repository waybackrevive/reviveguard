<?php

namespace App\Support;

use App\Models\Client;
use Illuminate\Support\Facades\URL;

class PortalAccess
{
    /** Short-lived signed URL for ops to open the client portal as that client. */
    public static function signedLoginUrl(Client $client, int $ttlMinutes = 30): string
    {
        return URL::temporarySignedRoute(
            'portal.admin-access',
            now()->addMinutes($ttlMinutes),
            ['client' => $client->id],
        );
    }
}
