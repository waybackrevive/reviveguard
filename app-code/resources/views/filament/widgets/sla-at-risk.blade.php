<x-filament-widgets::widget>
    <x-filament::section heading="SLA at risk" description="Emergency restore tickets due within 1 hour or overdue">
        @php $tickets = $this->getTickets(); @endphp

        @if ($tickets === [])
            <p class="text-sm text-gray-500">No emergency SLA tickets at risk right now.</p>
        @else
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($tickets as $ticket)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $ticket['subject'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $ticket['client'] }}
                                @if ($ticket['site'])
                                    · {{ $ticket['site'] }}
                                @endif
                            </p>
                            <p class="text-xs mt-1 {{ $ticket['breached'] ? 'text-danger-600 font-semibold' : 'text-warning-600' }}">
                                {{ $ticket['sla_label'] }}
                            </p>
                        </div>
                        <x-filament::button tag="a" :href="$ticket['url']" size="sm" color="gray">
                            Open ticket
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
