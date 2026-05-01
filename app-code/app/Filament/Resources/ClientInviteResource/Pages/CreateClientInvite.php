<?php

namespace App\Filament\Resources\ClientInviteResource\Pages;

use App\Filament\Resources\ClientInviteResource;
use App\Services\InviteService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClientInvite extends CreateRecord
{
    protected static string $resource = ClientInviteResource::class;

    /**
     * Override the default Eloquent create() to route through InviteService.
     * This ensures the token is generated securely and the email is sent.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $tenantId = config('app.tenant_id', '00000000-0000-0000-0000-000000000001');
        $adminId  = auth()->id();

        /** @var InviteService $inviteService */
        $inviteService = app(InviteService::class);

        $invite = $inviteService->createAndSend(
            tenantId:    $tenantId,
            name:        $data['name'],
            email:       $data['email'],
            path:        $data['path'],
            siteUrl:     $data['site_url'] ?? null,
            notes:       $data['notes'] ?? null,
            createdBy:   $adminId,
        );

        Notification::make()
            ->title("Invite created and sent to {$invite->email}")
            ->success()
            ->send();

        return $invite;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
