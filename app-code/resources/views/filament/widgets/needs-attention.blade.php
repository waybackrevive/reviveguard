<x-filament-widgets::widget>
    <x-filament::section
        heading="Needs attention today"
        description="Paid sites down, expiring certs, stale tickets, and abandoned checkout"
    >
        @php($items = $this->getItems())

        @if ($items === [])
            <div class="flex items-center gap-3 rounded-lg bg-success-50 px-4 py-3 dark:bg-success-500/10">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-5 w-5 text-success-600 dark:text-success-400"
                />
                <p class="text-sm text-success-700 dark:text-success-300">
                    Nothing needs attention right now.
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($items as $item)
                    <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 items-start gap-3">
                            <x-filament::badge :color="$item['badge']['color']" class="mt-0.5 shrink-0">
                                {{ $item['badge']['label'] }}
                            </x-filament::badge>

                            <div class="min-w-0">
                                <p class="font-medium text-gray-950 dark:text-white">
                                    {{ $item['label'] }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item['detail'] }}
                                </p>
                            </div>
                        </div>

                        @if (! empty($item['links']))
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                @foreach ($item['links'] as $link)
                                    <x-filament::button
                                        tag="a"
                                        :href="$link['url']"
                                        size="xs"
                                        color="gray"
                                    >
                                        {{ $link['label'] }}
                                    </x-filament::button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
