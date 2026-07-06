<div>
    <div class="mb-6">
        <a href="{{ route('portal.sites', ['list' => 1]) }}" class="text-sm text-gray-500 hover:text-brand inline-flex items-center gap-1 mb-3">
            ← All sites
        </a>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $site->displayName() }}</h1>
                @if ($site->client_label)
                    <p class="text-sm text-gray-500">{{ $site->name }}</p>
                @endif
                <a href="{{ $site->url }}" target="_blank" rel="noopener" class="text-sm text-brand hover:underline">{{ $site->url }}</a>
            </div>
            @php $ps = $site->portalStatusKey(); @endphp
            <span class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1 rounded-full
                {{ $ps === 'protected' ? 'bg-emerald-100 text-emerald-800' : ($ps === 'issue' ? 'bg-red-100 text-red-800' : ($ps === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600')) }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $ps === 'protected' ? 'bg-emerald-500' : ($ps === 'issue' ? 'bg-red-500 animate-pulse' : ($ps === 'warning' ? 'bg-amber-500' : 'bg-gray-400')) }}"></span>
                {{ $site->portalStatusLabel() }}
            </span>
        </div>
    </div>

    @if ($site->portalStatusKey() === 'setup')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <strong>One step left:</strong> install the ReviveGuard plugin on your site to start protection.
            <a href="{{ route('portal.sites', ['open' => 1]) }}" class="underline font-semibold ml-1">View connection guide</a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Uptime (30d)</p>
            <p class="text-2xl font-bold text-gray-900">{{ $site->uptime_30d !== null ? number_format($site->uptime_30d, 1) . '%' : '—' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">SSL</p>
            @php $ssl = $site->sslExpiresInDays(); @endphp
            <p class="text-2xl font-bold {{ $ssl !== null && $ssl < 30 ? 'text-amber-600' : 'text-gray-900' }}">
                {{ $ssl !== null ? ($ssl < 0 ? 'Expired' : $ssl . ' days') : '—' }}
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Domain</p>
            @php $dom = $site->domainExpiresInDays(); @endphp
            <p class="text-2xl font-bold text-gray-900">{{ $dom !== null ? ($dom < 0 ? 'Expired' : $dom . ' days') : '—' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Last backup</p>
            <p class="text-lg font-bold text-gray-900">{{ $latestBackup?->created_at?->diffForHumans() ?? '—' }}</p>
        </div>
    </div>

    @if ($site->plan && in_array($site->plan->slug, ['guard', 'shield']))
        <div class="mb-8 rounded-xl border border-emerald-100 bg-emerald-50/50 px-5 py-4">
            <p class="text-sm font-semibold text-emerald-900">Managed by ReviveGuard</p>
            <p class="text-sm text-emerald-800 mt-1">WordPress updates and backups are handled by our team on your {{ $site->plan->name }} plan.</p>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recent activity</h2>
            <a href="{{ route('portal.alerts') }}" class="text-xs text-brand font-medium hover:underline">View all</a>
        </div>
        <ul class="divide-y divide-gray-100">
            @forelse ($recentEvents as $event)
                <li class="px-5 py-3 text-sm">
                    <p class="font-medium text-gray-800">{{ $event->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $event->created_at->diffForHumans() }}</p>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-gray-500">Activity will appear here once your site is connected.</li>
            @endforelse
        </ul>
    </div>
</div>
