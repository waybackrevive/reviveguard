<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\ToastStack;
use Livewire\Livewire;
use Tests\TestCase;

class ToastStackTest extends TestCase
{
    /** @test */
    public function load_session_flashes_is_callable_from_client(): void
    {
        session()->flash('success', 'Saved.');

        Livewire::test(ToastStack::class)
            ->call('loadSessionFlashes')
            ->assertSet('toasts.0.message', 'Saved.')
            ->assertSet('toasts.0.type', 'success');
    }

    /** @test */
    public function portal_toast_event_adds_message(): void
    {
        Livewire::test(ToastStack::class)
            ->dispatch('portal-toast', type: 'success', message: 'Monitoring settings saved.')
            ->assertSet('toasts.0.message', 'Monitoring settings saved.');
    }
}
