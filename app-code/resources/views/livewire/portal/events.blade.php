<div>
    <div class="mb-6">
        <h1 class="portal-title text-3xl font-extrabold">Activity Log</h1>
        <p class="portal-muted mt-2 text-sm">Filter alerts, maintenance, backup activity, and warnings without losing readability in either theme.</p>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <select wire:model.live="filterType"
                class="portal-select rounded-xl px-3 py-2.5 text-sm font-medium">
            <option value="">All Events</option>
            <option value="uptime_kuma_alert">Downtime</option>
            <option value="backup_completed">Backup</option>
            <option value="plugin_update">Updates</option>
            <option value="ssl_expiry_warning">SSL Warning</option>
            <option value="domain_expiry_warning">Domain Warning</option>
        </select>

        <select wire:model.live="filterSeverity"
        class="portal-select rounded-xl px-3 py-2.5 text-sm font-medium">
            <option value="">All Severities</option>
            <option value="critical">Critical</option>
            <option value="warning">Warning</option>
            <option value="info">Info</option>
            <option value="success">Success</option>
        </select>

        <select wire:model.live="filterDays"
                class="portal-select rounded-xl px-3 py-2.5 text-sm font-medium">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <div class="portal-panel-strong rounded-3xl overflow-hidden">
        @if ($events->isEmpty())
            <div class="py-16 text-center text-sm portal-muted">
                No events found for the selected filters.
            </div>
        @else
            <table class="min-w-full">
                <thead class="portal-table-head border-b portal-divider">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Date / Time</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Event</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr wire:click="showEvent('{{ $event->id }}')"
                            class="portal-table-row portal-table-row-hover cursor-pointer border-b last:border-b-0">
                            <td class="px-6 py-4 text-sm portal-muted whitespace-nowrap">
                                {{ $event->created_at->format('M j, Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="portal-title text-sm font-bold">{{ $event->title }}</p>
                                @if ($event->message)
                                    <p class="portal-muted text-xs mt-0.5 truncate max-w-xs">{{ $event->message }}</p>
                                @endif
                            </td>
                            <td class="hidden sm:table-cell px-6 py-4 text-sm portal-muted">
                                {{ ucwords(str_replace(['_', 'uptime kuma'], [' ', 'Downtime'], $event->type)) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="portal-badge @if ($event->severity === 'critical') portal-badge-danger @elseif ($event->severity === 'warning') portal-badge-warning @elseif ($event->severity === 'success') portal-badge-success @else portal-badge-neutral @endif">
                                    @if ($event->severity === 'critical') ✗ Critical
                                    @elseif ($event->severity === 'warning') ⚠ Warning
                                    @elseif ($event->severity === 'success') ✓ Success
                                    @else ℹ Info @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t portal-divider">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    @if ($selectedEvent)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
             wire:click.self="closeModal">
            <div class="portal-modal-panel rounded-3xl max-w-md w-full p-6">
                <h3 class="portal-title text-base font-extrabold mb-1">{{ $selectedEvent->title }}</h3>
                <p class="portal-soft text-xs mb-4">
                    {{ $selectedEvent->created_at->format('F j, Y \a\t H:i') }} UTC
                </p>

                @if ($selectedEvent->message)
                    <p class="portal-muted text-sm mb-4">{{ $selectedEvent->message }}</p>
                @endif

                @if ($selectedEvent->metadata)
                    <dl class="text-sm mb-4 border-t portal-divider">
                        @foreach ($selectedEvent->metadata as $key => $val)
                            <div class="flex justify-between gap-4 py-3 border-b portal-divider">
                                <dt class="portal-muted">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="portal-title font-bold text-right">{{ $val }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <button wire:click="closeModal"
                        class="portal-btn-secondary w-full py-2.5 text-sm font-semibold rounded-xl transition-colors">
                    Close
                </button>
            </div>
        </div>
    @endif
</div>
