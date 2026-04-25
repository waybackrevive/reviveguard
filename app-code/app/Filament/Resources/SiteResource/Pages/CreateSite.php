<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Services\UptimeKumaService;
use Filament\Resources\Pages\CreateRecord;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    /**
     * Before create: generate the initial agent token.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = '00000000-0000-0000-0000-000000000001';

        // Generate a secure agent token (stored as sha256 hash)
        $raw = bin2hex(random_bytes(32)); // 64-char hex
        $data['agent_token']       = hash('sha256', $raw);
        $data['agent_token_last4'] = substr($raw, -4);

        // Store raw token in session to display once after creation
        session()->flash('new_agent_token', $raw);

        return $data;
    }

    /**
     * After create: register the site with Uptime Kuma.
     */
    protected function afterCreate(): void
    {
        $site      = $this->record;
        $monitorId = app(UptimeKumaService::class)
            ->createMonitor($site->name, $site->url);

        if ($monitorId) {
            $site->update(['uptime_kuma_monitor_id' => $monitorId]);
        }

        // Show the token once — admin must copy it now
        $raw = session('new_agent_token');
        if ($raw) {
            \Filament\Notifications\Notification::make()
                ->title('Agent Token Generated')
                ->body("Copy this token and install it in the WordPress plugin:\n\n{$raw}\n\n(This is shown once only)")
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
