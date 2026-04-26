<div wire:poll.60000ms="refresh">

    {{-- ── Greeting ──────────────────────────────────────────────────────── --}}
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">
        Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] portal-soft">Overview</p>
                <h1 class="portal-title mt-2 text-3xl font-extrabold">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
                    {{ explode(' ', $client->name)[0] }}.
                </h1>
                <p class="portal-muted mt-2 text-sm">Everything important about your website care plan, clearly visible in one place.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                <div class="portal-panel-soft rounded-2xl px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide portal-soft">Coverage</p>
                    <p class="portal-title mt-1 text-sm font-bold">Monitoring + backup visibility</p>
                </div>
                <div class="portal-panel-soft rounded-2xl px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide portal-soft">Service</p>
                    <p class="portal-title mt-1 text-sm font-bold">Transparent managed care</p>
                </div>
            </div>
        </div>
            No site found. Contact support to get your site added.
        </div>
            <div class="portal-panel-strong rounded-3xl p-8 text-center portal-muted">

    {{-- ── Status cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-4 mb-8">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5 mb-8">
            <div class="flex items-center gap-2 mb-2">
            <div class="portal-panel-strong rounded-3xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="portal-badge {{ $site->status->value === 'DOWN' ? 'portal-badge-danger' : 'portal-badge-success' }}">
            </div>
            <p class="text-sm font-medium text-gray-800 truncate">{{ $site->url ?? $site->name }}</p>
            <p class="text-xs text-gray-500 mt-1">
                @if ($site->last_seen_at)
                <p class="portal-title text-sm font-bold truncate">{{ $site->url ?? $site->name }}</p>
                <p class="portal-muted text-xs mt-1">
                    No check yet
                @endif
            </p>
        </div>

        {{-- Uptime --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Uptime</p>
            <div class="portal-panel-strong rounded-3xl p-5">
                <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em] mb-2">Uptime</p>
                <p class="portal-title text-3xl font-extrabold">
        </div>

                <p class="portal-muted text-xs mt-1">Last 30 days</p>
        <div class="bg-white rounded-2xl border {{ $site->ssl_expires_at && $site->ssl_expires_at->diffInDays(now(), false) >= -30 ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">SSL Certificate</p>
            <div class="portal-panel-strong rounded-3xl p-5">
                <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em] mb-2">SSL Certificate</p>
                    {{ $sslDays > 0 ? $sslDays . ' days left' : 'Expired' }}
                </p>
                    <p class="text-2xl font-extrabold {{ $sslDays <= 30 ? 'text-amber-600' : 'portal-title' }}">
            @else
                <p class="text-sm text-gray-400">Not checked yet</p>
                    <p class="text-xs mt-1 {{ $site->ssl_valid ? 'text-emerald-600' : 'text-rose-600' }}">{{ $site->ssl_valid ? 'Valid certificate' : 'SSL needs attention' }}</p>
        </div>
                    <p class="portal-muted text-sm">Not checked yet</p>
        {{-- Domain --}}
        <div class="bg-white rounded-2xl border {{ $site->domain_expires_at && $site->domain_expires_at->diffInDays(now(), false) >= -30 ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Domain</p>
            <div class="portal-panel-strong rounded-3xl p-5">
                <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em] mb-2">Domain</p>
                    {{ $domDays > 0 ? $domDays . ' days left' : 'Expired' }}
                </p>
                    <p class="text-2xl font-extrabold {{ $domDays <= 30 ? 'text-amber-600' : 'portal-title' }}">
            @else
                <p class="text-sm text-gray-400">Not checked yet</p>
                    <p class="text-xs mt-1 text-emerald-600">Domain active</p>
        </div>
                    <p class="portal-muted text-sm">Not checked yet</p>
        {{-- Last Backup --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last Backup</p>
            <div class="portal-panel-strong rounded-3xl p-5">
                <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em] mb-2">Last Backup</p>
                </p>
                    <p class="portal-title text-xl font-extrabold">
                    {{ ucfirst($lastBackup->status->value) }}
                    @if ($lastBackup->size_bytes)
                    <p class="text-xs mt-1 {{ $lastBackup->status->value === 'success' ? 'text-emerald-600' : 'text-rose-600' }}">
                    @endif
                </p>
            @else
                <p class="text-xl font-bold text-gray-400">—</p>
                <p class="text-xs text-gray-400 mt-1">No backups yet</p>
            @endif
                    <p class="portal-soft text-xl font-extrabold">—</p>
                    <p class="portal-muted text-xs mt-1">No backups yet</p>
    </div>

    {{-- ── Recent Activity ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="grid grid-cols-1 xl:grid-cols-[1.5fr,0.9fr] gap-6">
            <div class="portal-panel-strong rounded-3xl overflow-hidden">
                <div class="px-6 py-5 border-b portal-divider flex items-center justify-between">
                    <div>
                        <h2 class="portal-title text-base font-extrabold">Recent Activity</h2>
                        <p class="portal-muted text-xs mt-1">Latest system events and alerts for your website.</p>
                    </div>
                    <a href="{{ route('portal.events') }}" class="portal-link text-sm font-semibold">Full log</a>
                </div>

                @if ($recentEvents->isEmpty())
                    <div class="px-6 py-12 text-center text-sm portal-muted">
                        No activity this month. That usually means everything is stable.
                    </div>
                @else
                    <ul>
                        @foreach ($recentEvents as $event)
                            <li class="portal-table-row border-b last:border-b-0 px-6 py-4 flex items-start gap-4">
                                <span class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-2xl portal-panel-soft text-lg">
                                    @if ($event->severity === 'critical') ✗
                                    @elseif ($event->severity === 'warning') ⚠
                                    @elseif ($event->severity === 'success') ✓
                                    @else ℹ @endif
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="portal-title text-sm font-bold truncate">{{ $event->title }}</p>
                                    @if ($event->message)
                                        <p class="portal-muted text-sm mt-1">{{ $event->message }}</p>
                                    @endif
                                </div>
                                <time class="portal-soft flex-shrink-0 text-xs">
                                    {{ $event->created_at->format('M j, H:i') }}
                                </time>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="space-y-4">
                <div class="portal-panel-strong rounded-3xl p-6">
                    <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em]">Visibility</p>
                    <h2 class="portal-title mt-2 text-lg font-extrabold">What you can verify here</h2>
                    <ul class="mt-4 space-y-3 text-sm portal-muted">
                        <li>Uptime and recent health events are always visible from the dashboard.</li>
                        <li>Backups and reports remain accessible from the left navigation.</li>
                        <li>Support requests stay tied to your account and current plan.</li>
                    </ul>
                </div>

                <div class="portal-panel-soft rounded-3xl p-6">
                    <p class="portal-soft text-xs font-bold uppercase tracking-[0.24em]">Need help?</p>
                    <h3 class="portal-title mt-2 text-base font-extrabold">Support and recovery actions</h3>
                    <p class="portal-muted mt-2 text-sm">If you need a backup restored or have a technical issue, use the support area for tracked help.</p>
                    <a href="{{ route('portal.tickets') }}" class="portal-link mt-4 inline-flex text-sm font-semibold">Open support area →</a>
                </div>
            </div>
        </div>

    {{-- Livewire loading indicator --}}
    <div wire:loading class="fixed bottom-4 right-4 bg-white border border-gray-200 rounded-full px-4 py-2 text-xs text-gray-500 shadow-sm">
        <div wire:loading class="portal-toast fixed bottom-4 right-4 rounded-full px-4 py-2 text-xs">
</div>
