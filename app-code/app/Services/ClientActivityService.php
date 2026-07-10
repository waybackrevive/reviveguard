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
    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'support_ticket_submitted' => 'You submitted a support ticket',
            'plan_upgraded'            => 'You upgraded your plan',
            'plan_downgraded'          => 'You changed your plan',
            'monitor_settings_updated' => 'You updated monitoring settings',
            'monitoring_paused'        => 'You paused monitoring',
            'monitoring_resumed'       => 'You resumed monitoring',
            'credentials_updated'      => 'You updated hosting credentials',
            'addon_order_placed'       => 'You requested an add-on',
            'addon_order_paid'         => 'You paid for an add-on',
            default                    => str_replace('_', ' ', ucfirst($action)),
        };
    }

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
