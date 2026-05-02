<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Activity Log</h1>

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-6">
        <select wire:model.live="filterType"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Events</option>
            <option value="uptime_kuma_alert">Downtime</option>
            <option value="backup_completed">Backup</option>
            <option value="plugin_update">Updates</option>
            <option value="ssl_expiry_warning">SSL Warning</option>
            <option value="domain_expiry_warning">Domain Warning</option>
        </select>

        <select wire:model.live="filterSeverity"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Severities</option>
            <option value="critical">Critical</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
            <option value="success">Success</option>
        </select>

        <select wire:model.live="filterDays"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        @if ($events->isEmpty())
            <div class="py-16 text-center text-sm text-gray-500">
                No events found for the selected filters — that's a good sign!
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date / Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Event</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($events as $event)
                        <tr wire:click="showEvent('{{ $event->id }}')"
                            class="hover:bg-gray-50 cursor-pointer">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $event->created_at->format('M j, Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-800">{{ $event->title }}</p>
                                @if ($event->message)
                                    <p class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $event->message }}</p>
                                @endif
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 text-sm text-gray-500">
                                {{ ucwords(str_replace(['_', 'uptime kuma'], [' ', 'Downtime'], $event->type)) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                    @if ($event->severity->value === 'critical')  bg-red-100 text-red-700
                                    @elseif ($event->severity->value === 'warning') bg-amber-100 text-amber-700
                                    @elseif ($event->severity->value === 'success') bg-green-100 text-green-700
                                    @else bg-blue-50 text-blue-700 @endif">
                                    @if ($event->severity->value === 'critical') ✗ Critical
                                    @elseif ($event->severity->value === 'warning') ⚠ Warning
                                    @elseif ($event->severity->value === 'success') ✓ Success
                                    @else ℹ Info @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-gray-100">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    {{-- ── Event detail modal ──────────────────────────────────────────────── --}}
    @if ($selectedEvent)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
             wire:click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $selectedEvent->title }}</h3>
                <p class="text-xs text-gray-400 mb-4">
                    {{ $selectedEvent->created_at->format('F j, Y \a\t H:i') }} UTC
                </p>

                @if ($selectedEvent->message)
                    <p class="text-sm text-gray-700 mb-4">{{ $selectedEvent->message }}</p>
                @endif

                @if ($selectedEvent->metadata)
                    <dl class="text-sm divide-y divide-gray-100 mb-4">
                        @foreach ($selectedEvent->metadata as $key => $val)
                            <div class="flex justify-between py-2">
                                <dt class="text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="text-gray-800 font-medium">{{ $val }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <button wire:click="closeModal"
                        class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    @endif
</div>
