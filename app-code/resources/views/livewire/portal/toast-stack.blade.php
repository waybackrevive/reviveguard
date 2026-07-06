<div
    class="fixed top-4 right-4 z-[200] flex flex-col gap-2 w-[calc(100%-2rem)] max-w-sm pointer-events-none sm:top-6 sm:right-6"
    aria-live="polite"
    aria-atomic="true"
    x-data
    x-on:livewire:navigated.window="$wire.hydrateFromSession()"
>
    @foreach ($toasts as $toast)
        <div
            wire:key="portal-toast-{{ $toast['id'] }}"
            x-data="{ show: true }"
            x-init="setTimeout(() => { show = false; $wire.dismiss('{{ $toast['id'] }}') }, 5200)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 translate-x-4"
            class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white px-4 py-3 shadow-lg {{ $toast['type'] === 'success' ? 'border-emerald-200' : 'border-red-200' }}"
            role="status"
        >
            @if ($toast['type'] === 'success')
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            @else
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                </span>
            @endif
            <p class="text-sm text-gray-800 leading-snug flex-1 pt-0.5">{{ $toast['message'] }}</p>
            <button
                type="button"
                wire:click="dismiss('{{ $toast['id'] }}')"
                x-on:click="show = false"
                class="shrink-0 rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                aria-label="Dismiss"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endforeach
</div>
