<?php

namespace App\Livewire\Concerns;

use App\Livewire\Portal\ToastStack;

trait DispatchesPortalToast
{
    protected function toast(string $type, string $message): void
    {
        $this->dispatch('portal-toast', type: $type, message: $message)
            ->to(ToastStack::class);
    }

    protected function toastSuccess(string $message): void
    {
        $this->toast('success', $message);
    }

    protected function toastError(string $message): void
    {
        $this->toast('error', $message);
    }
}
