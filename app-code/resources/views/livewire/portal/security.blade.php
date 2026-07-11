<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 mb-1">Security &amp; links</h1>
            <p class="text-sm text-gray-500">Malware scans and broken-link audits across your Guard and Shield sites. Detection only — open a ticket if you need a fix.</p>
        </div>
        <a href="{{ route('portal.tickets') }}" class="text-sm font-medium text-brand hover:underline">Open support →</a>
    </div>

    @if (! $hasSecurityPlan)
        <div class="bg-white rounded-[10px] border border-amber-200 bg-amber-50 px-5 py-6 text-sm text-amber-900">
            <p class="font-semibold">Security scans are included on Guard and Shield</p>
            <p class="mt-1">Upgrade a site to Guard or Shield to unlock weekly malware scans and monthly broken-link audits.</p>
            <a href="{{ route('portal.sites') }}" class="inline-block mt-3 font-semibold underline hover:no-underline">View your sites →</a>
        </div>
    @else
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Per site status</h2>
        <div class="grid gap-4 md:grid-cols-2 mb-8">
            @foreach ($rows as $row)
                @php
                    $site = $row['site'];
                    $ms = $row['malware'];
                    $la = $row['links'];
                    $broken = (int) ($la?->metadata['broken_count'] ?? 0);
                @endphp
                <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $site->displayName() }}</p>
                            <p class="text-xs text-gray-500">{{ $site->plan?->name ?? 'Plan' }}</p>
                        </div>
                        <a href="{{ route('portal.sites.show', ['site' => $site, 'tab' => 'security']) }}" class="text-xs font-medium text-brand hover:underline whitespace-nowrap">Site detail →</a>
                    </div>

                    @if ($row['canMalware'])
                        <div class="mb-3 pb-3 border-b border-gray-100">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Malware scan</p>
                                @if ($ms)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ms->type === 'malware_scan_alert' ? 'bg-amber-100 text-amber-800' : ($ms->type === 'malware_scan_failed' ? 'bg-red-100 text-red-800' : 'bg-emerald-100 text-emerald-800') }}">
                                        {{ $ms->type === 'malware_scan_alert' ? 'Issues found' : ($ms->type === 'malware_scan_failed' ? 'Scan failed' : 'Clean') }}
                                    </span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Pending</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $ms?->created_at?->diffForHumans() ?? 'First weekly scan not run yet' }}</p>
                        </div>
                    @endif

                    @if ($row['canLinks'])
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Broken links</p>
                                @if ($la)
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $broken > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $broken > 0 ? $broken.' broken' : 'All clear' }}
                                    </span>
                                @else
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Pending</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $la?->created_at?->diffForHumans() ?? 'First monthly audit not run yet' }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent security events</h2>
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden">
            @if ($events->isEmpty())
                <div class="py-12 text-center text-sm text-gray-500">
                    No security events yet. Scans run on schedule — or ask us to run one from support.
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($events as $event)
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $event->created_at->format('M j, Y H:i') }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <a href="{{ route('portal.sites.show', ['site' => $event->site_id, 'tab' => 'security']) }}" class="font-medium text-brand hover:underline">
                                        {{ $event->site?->displayName() ?? 'Site' }}
                                    </a>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800">
                                    <p class="font-medium">{{ $event->title }}</p>
                                    @if ($event->message)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ \Illuminate\Support\Str::limit($event->message, 80) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @php $sev = $event->severity instanceof \App\Enums\EventSeverity ? $event->severity->value : (string) $event->severity; @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $sev === 'critical' ? 'bg-red-100 text-red-700' : ($sev === 'warning' ? 'bg-amber-100 text-amber-700' : ($sev === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-50 text-blue-700')) }}">
                                        {{ ucfirst($sev) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
</div>
