<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Backups</h1>
            <p class="text-sm text-gray-500">{{ $retentionCopy }}</p>
        </div>
        <a href="{{ route('portal.tickets') }}" class="text-sm font-medium text-brand hover:underline">Need a restore? Open support →</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Successful (recent)</p>
            <p class="text-2xl font-bold text-gray-900">{{ $successCount }}</p>
        </div>
        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Failed (recent)</p>
            <p class="text-2xl font-bold {{ $failedCount > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $failedCount }}</p>
        </div>
        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Last verified</p>
            <p class="text-lg font-bold text-gray-900">{{ $latestOk?->completed_at?->diffForHumans() ?? '—' }}</p>
        </div>
    </div>

    @if ($sites->isNotEmpty())
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Per site</h2>
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden mb-8">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last backup</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($sites as $row)
                        @php $site = $row['site']; @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $site->displayName() }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ $row['frequency'] }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600">
                                {{ $row['latest']?->completed_at?->diffForHumans() ?? 'None yet' }}
                            </td>
                            <td class="px-5 py-4">
                                @if ($row['ready'])
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Restore ready</span>
                                @elseif ($row['latest']?->status?->value === 'failed')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Last attempt failed</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('portal.sites.show', ['site' => $site, 'tab' => 'backups']) }}" class="text-sm font-medium text-brand hover:underline">Open →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent backup activity</h2>
    @if ($backups->isEmpty())
        <div class="bg-white rounded-[10px] border border-gray-200 p-10 text-center text-sm text-gray-500">
            No backups found yet. Backups run automatically on your plan schedule.
        </div>
    @else
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="hidden sm:table-cell px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($backups as $backup)
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-800">
                                <a href="{{ route('portal.sites.show', ['site' => $backup->site_id, 'tab' => 'backups']) }}" class="font-medium text-brand hover:underline">
                                    {{ $backup->site?->displayName() ?? 'Site' }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 whitespace-nowrap">
                                {{ ($backup->completed_at ?? $backup->created_at)?->format('M j, Y H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">{{ ucfirst((string) $backup->type) }}</td>
                            <td class="hidden sm:table-cell px-5 py-4 text-sm text-gray-600">
                                {{ $backup->size_bytes ? round($backup->size_bytes / 1048576) . ' MB' : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @if ($backup->status === \App\Enums\BackupStatus::SUCCESS)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Verified</span>
                                @elseif ($backup->status === \App\Enums\BackupStatus::FAILED)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $backup->error_message }}">Failed</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $backup->status->value ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
