<div wire:poll.60000ms="refresh">

    {{-- ── Greeting ──────────────────────────────────────────────────────── --}}
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">
        Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
        {{ explode(' ', $client->name)[0] }}.
    </h1>

    @if (! $site)
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-500">
            No site found. Contact support to get your site added.
        </div>
    @else

    {{-- ── Status cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-4 mb-8">

        {{-- Site status --}}
        <div class="bg-white rounded-2xl border {{ $site->status->value === 'DOWN' ? 'border-red-200 bg-red-50' : 'border-gray-200' }} p-5">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ $site->status->value === 'DOWN' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $site->status->value === 'DOWN' ? 'bg-red-500' : 'bg-green-500' }}"></span>
                    {{ $site->status->value === 'DOWN' ? 'SITE IS DOWN' : 'SITE IS UP' }}
                </span>
            </div>
            <p class="text-sm font-medium text-gray-800 truncate">{{ $site->url ?? $site->name }}</p>
            <p class="text-xs text-gray-500 mt-1">
                @if ($site->last_seen_at)
                    Checked {{ $site->last_seen_at->diffForHumans() }}
                @else
                    No check yet
                @endif
            </p>
        </div>

        {{-- Uptime --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Uptime</p>
            <p class="text-3xl font-bold text-gray-900">
                {{ $site->uptime_30d !== null ? number_format($site->uptime_30d, 2) . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
        </div>

        {{-- SSL --}}
        <div class="bg-white rounded-2xl border {{ $site->ssl_expires_at && $site->ssl_expires_at->diffInDays(now(), false) >= -30 ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">SSL Certificate</p>
            @if ($site->ssl_expires_at)
                @php $sslDays = (int) now()->diffInDays($site->ssl_expires_at, false); @endphp
                <p class="text-2xl font-bold {{ $sslDays <= 30 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $sslDays > 0 ? $sslDays . ' days left' : 'Expired' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ $site->ssl_valid ? '✓ Valid' : '✗ Invalid' }}</p>
            @else
                <p class="text-sm text-gray-400">Not checked yet</p>
            @endif
        </div>

        {{-- Domain --}}
        <div class="bg-white rounded-2xl border {{ $site->domain_expires_at && $site->domain_expires_at->diffInDays(now(), false) >= -30 ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Domain</p>
            @if ($site->domain_expires_at)
                @php $domDays = (int) now()->diffInDays($site->domain_expires_at, false); @endphp
                <p class="text-2xl font-bold {{ $domDays <= 30 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $domDays > 0 ? $domDays . ' days left' : 'Expired' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">✓ Active</p>
            @else
                <p class="text-sm text-gray-400">Not checked yet</p>
            @endif
        </div>

        {{-- Last Backup --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last Backup</p>
            @if ($lastBackup)
                <p class="text-xl font-bold text-gray-900">
                    {{ $lastBackup->created_at->diffForHumans() }}
                </p>
                <p class="text-xs mt-1 {{ $lastBackup->status->value === 'success' ? 'text-green-600' : 'text-red-500' }}">
                    {{ ucfirst($lastBackup->status->value) }}
                    @if ($lastBackup->size_bytes)
                        · {{ number_format($lastBackup->size_bytes / 1048576, 1) }} MB
                    @endif
                </p>
            @else
                <p class="text-xl font-bold text-gray-400">—</p>
                <p class="text-xs text-gray-400 mt-1">No backups yet</p>
            @endif
        </div>

    </div>

    {{-- ── Recent Activity ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Recent Activity</h2>
        </div>

        @if ($recentEvents->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-500">
                No activity this month — that's a good sign!
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($recentEvents as $event)
                    <li class="flex items-start gap-4 px-6 py-4">
                        <span class="mt-0.5 flex-shrink-0 text-lg">
                            @if ($event->severity === 'critical') ✗
                            @elseif ($event->severity === 'warning') ⚠
                            @elseif ($event->severity === 'success') ✓
                            @else ℹ @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $event->title }}</p>
                            @if ($event->message)
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $event->message }}</p>
                            @endif
                        </div>
                        <time class="flex-shrink-0 text-xs text-gray-400">
                            {{ $event->created_at->format('M j, H:i') }}
                        </time>
                    </li>
                @endforeach
            </ul>
            <div class="px-6 py-3 border-t border-gray-100">
                <a href="{{ route('portal.events') }}" class="text-sm text-blue-600 hover:underline">View all activity →</a>
            </div>
        @endif
    </div>

    @endif {{-- site --}}

    {{-- Livewire loading indicator --}}
    <div wire:loading class="fixed bottom-4 right-4 bg-white border border-gray-200 rounded-full px-4 py-2 text-xs text-gray-500 shadow-sm">
        Refreshing…
    </div>
</div>
