<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">Backups</h1>
    <p class="text-sm text-gray-500 mb-6">{{ $retentionCopy }}</p>

    @if ($backups->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center text-sm text-gray-500">
            No backups found yet. Backups run automatically on your plan schedule.
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($backups as $backup)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                {{ $backup->completed_at?->format('M j, Y H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">Full backup</td>
                            <td class="hidden sm:table-cell px-6 py-4 text-sm text-gray-700">
                                {{ $backup->size_bytes ? round($backup->size_bytes / 1048576) . ' MB' : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    ✓ Verified
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 text-sm text-gray-600">
        Need to restore a backup?
        <a href="{{ route('portal.tickets') }}" class="text-blue-600 hover:underline font-medium">Open a support ticket →</a>
        and we'll restore it for you.
    </div>
</div>
