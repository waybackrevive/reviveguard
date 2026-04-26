<div>
    <h1 class="portal-title text-3xl font-extrabold mb-1">Backups</h1>
    <p class="portal-muted text-sm mb-6">{{ $retentionCopy }}</p>

    @if ($backups->isEmpty())
        <div class="portal-panel-strong rounded-3xl p-10 text-center text-sm portal-muted">
            No backups found yet. Backups run automatically on your plan schedule.
        </div>
    @else
        <div class="portal-panel-strong rounded-3xl overflow-hidden mb-6">
            <table class="min-w-full">
                <thead class="portal-table-head border-b portal-divider">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Type</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.24em]">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($backups as $backup)
                        <tr class="portal-table-row border-b last:border-b-0">
                            <td class="px-6 py-4 text-sm portal-muted whitespace-nowrap">
                                {{ $backup->completed_at?->format('M j, Y H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm portal-title font-semibold">Full backup</td>
                            <td class="hidden sm:table-cell px-6 py-4 text-sm portal-muted">
                                {{ $backup->size_bytes ? round($backup->size_bytes / 1048576) . ' MB' : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="portal-badge portal-badge-success">
                                    ✓ Verified
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="portal-panel-soft rounded-3xl p-5 text-sm portal-muted">
        Need to restore a backup?
        <a href="{{ route('portal.tickets') }}" class="portal-link font-semibold">Open a support ticket →</a>
        and we'll restore it for you.
    </div>
</div>
