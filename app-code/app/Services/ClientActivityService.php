<?php

namespace App\Services;

use App\Enums\EventSeverity;
use App\Models\Client;
use App\Models\Event;
use App\Models\Site;

/**
 * Portal audit trail — client-initiated actions visible in Activity tab.
 */
class ClientActivityService
{
    public function log(
        Client $client,
        string $action,
        string $title,
        ?string $message = null,
        ?Site $site = null,
        array $metadata = [],
    ): Event {
        return Event::create([
            'tenant_id' => $client->tenant_id,
            'site_id'   => $site?->id,
            'type'      => 'client_action',
            'severity'  => EventSeverity::INFO,
            'title'     => $title,
            'message'   => $message,
            'metadata'  => array_merge([
                'action'    => $action,
                'client_id' => $client->id,
            ], $metadata),
            'resolved'  => true,
        ]);
    }
}
