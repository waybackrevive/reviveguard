<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterSave(): void
    {
        $record = $this->record->fresh();
        $data   = $this->form->getState();

        $justClosed = $this->record->wasChanged('status')
            && in_array($record->status, ['resolved', 'closed'], true);

        if ($justClosed) {
            if (! $record->resolved_at) {
                $record->update(['resolved_at' => now()]);
            }

            if (($data['type'] ?? $record->type) === \App\Enums\TicketType::CONTENT_EDIT->value) {
                $minutes = (int) ($data['minutes_billed'] ?? $record->minutes_billed ?? 0);
                if ($minutes > 0 && $record->client) {
                    app(\App\Services\ContentHoursService::class)->deduct($record->client, $minutes);
                }
            }
        }

        if (! empty($data['admin_reply']) && $this->record->wasChanged('admin_reply')) {
            if (! $record->replied_at) {
                $record->update(['replied_at' => now()]);
            }
            try {
                (new \App\Services\NotificationService())->sendTicketReplied($record->fresh());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('EditTicket: sendTicketReplied failed', ['error' => $e->getMessage()]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
