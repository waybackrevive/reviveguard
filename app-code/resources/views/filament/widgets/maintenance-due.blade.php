<x-filament-widgets::widget>
    <x-filament::section heading="Maintenance due today" description="Sites that will receive automated tasks on the next daily scheduler pass (03:00 UTC)">
        @php $counts = $this->getCounts(); @endphp

        @if ($counts['total'] === 0)
            <p class="text-sm text-gray-500">Nothing queued for the next maintenance run — all sites are up to date.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 text-sm">
                <div class="rounded-lg border border-gray-200 dark:border-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['backups'] }}</p>
                    <p class="text-xs text-gray-500">Backups</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['updates'] }}</p>
                    <p class="text-xs text-gray-500">WP updates</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['malware_scans'] }}</p>
                    <p class="text-xs text-gray-500">Malware scans</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['broken_links'] }}</p>
                    <p class="text-xs text-gray-500">Link audits</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-white/10 px-4 py-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['quarterly'] }}</p>
                    <p class="text-xs text-gray-500">Quarterly audits</p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
