<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\On;
use Livewire\Component;

class ToastStack extends Component
{
    /** @var list<array{id: string, type: string, message: string}> */
    public array $toasts = [];

    public function mount(): void
    {
        $this->hydrateFromSession();
    }

    public function hydrateFromSession(): void
    {
        $map = [
            'success'      => 'success',
            'error'        => 'error',
            'ticket_error' => 'error',
        ];

        foreach ($map as $key => $type) {
            if ($message = session($key)) {
                $this->push($type, $message);
            }
        }
    }

    #[On('portal-toast')]
    public function onToast(string $type, string $message): void
    {
        $this->push($type, $message);
    }

    protected function push(string $type, string $message): void
    {
        $message = trim($message);

        if ($message === '') {
            return;
        }

        foreach ($this->toasts as $toast) {
            if ($toast['type'] === $type && $toast['message'] === $message) {
                return;
            }
        }

        $this->toasts[] = [
            'id'      => uniqid('toast_', true),
            'type'    => $type === 'error' ? 'error' : 'success',
            'message' => $message,
        ];
    }

    public function dismiss(string $id): void
    {
        $this->toasts = array_values(array_filter(
            $this->toasts,
            fn (array $toast) => $toast['id'] !== $id,
        ));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.portal.toast-stack');
    }
}
