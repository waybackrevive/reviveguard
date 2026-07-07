<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\SiteResource;
use App\Filament\Resources\TicketResource;
use App\Support\AdminDashboard;
use Filament\Widgets\Widget;

class NeedsAttentionWidget extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.needs-attention';

    public function getItems(): array
    {
        $badges = [
            'down'     => ['label' => 'Down', 'color' => 'danger'],
            'ssl'      => ['label' => 'SSL', 'color' => 'warning'],
            'domain'   => ['label' => 'Domain', 'color' => 'warning'],
            'ticket'   => ['label' => 'Ticket', 'color' => 'info'],
            'checkout' => ['label' => 'Checkout', 'color' => 'gray'],
        ];

        return AdminDashboard::attentionItems()
            ->map(function (array $item) use ($badges): array {
                $item['badge'] = $badges[$item['type']] ?? ['label' => 'Alert', 'color' => 'gray'];

                if ($item['type'] === 'ticket') {
                    $item['links'] = array_values(array_filter([
                        ['label' => 'Ticket', 'url' => TicketResource::getUrl('edit', ['record' => $item['ticket_id']])],
                        ['label' => 'Client', 'url' => ClientResource::getUrl('edit', ['record' => $item['client_id']])],
                        $item['site_id']
                            ? ['label' => 'Site', 'url' => SiteResource::getUrl('edit', ['record' => $item['site_id']])]
                            : null,
                    ]));

                    return $item;
                }

                $item['links'] = $item['site_id']
                    ? [['label' => 'Open', 'url' => SiteResource::getUrl('edit', ['record' => $item['site_id']])]]
                    : [];

                return $item;
            })
            ->all();
    }
}
