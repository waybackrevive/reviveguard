<div wire:poll.60000ms="refresh">

    {{-- ── Greeting ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
            {{ explode(' ', $client->name)[0] }}.
        </h1>
        @if ($allSites->count() > 1)
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-gray-500 font-medium">Viewing:</span>
                @foreach ($allSites as $s)
                    <button wire:click="switchSite('{{ $s->id }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                            {{ $site && $s->id === $site->id
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:border-emerald-400 hover:text-emerald-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                            {{ $s->status->value === 'down' ? 'bg-red-500' : ($s->status->value === 'warning' ? 'bg-amber-500' : ($s->status->value === 'active' ? 'bg-green-500' : 'bg-gray-400')) }}"></span>
                        {{ $s->name }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── No site yet ──────────────────────────────────────────────────── --}}
    @if (! $site)
        <div class="bg-white rounded-2xl border border-gray-200 p-8 sm:p-12 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Welcome to ReviveGuard!</h2>
            <p class="text-sm text-gray-500 mb-3 max-w-md mx-auto">Your account is active. Add your first website to start monitoring uptime, SSL, domain expiry, backups, and more.</p>
            <p class="text-xs text-gray-400 mb-6">It only takes 2 minutes — we'll walk you through installing the monitoring plugin.</p>
            <a href="{{ route('portal.my-websites') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add your first website
            </a>
        </div>
    @else

    @php
        $statusVal       = $site->status->value;
        $isDown          = $statusVal === 'down';
        $isWarning       = $statusVal === 'warning';
        $isActive        = $statusVal === 'active';
        $isPending       = $statusVal === 'pending';
        $agentConnected  = ! is_null($site->agent_installed_at);
        $hasUptimeData   = $site->uptime_30d !== null;
    @endphp

    {{-- ── Agent not connected banner ───────────────────────────────────── --}}
    @if (! $agentConnected)
        <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4">
            <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-amber-800">Install the ReviveGuard plugin to start monitoring</p>
                <p class="text-xs text-amber-700 mt-0.5">Your site has been added but the monitoring agent isn't connected yet. Once installed, you'll see live uptime, backups, and alerts here.</p>
            </div>
            <a href="{{ route('portal.my-websites') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition-colors">
                Install plugin →
            </a>
        </div>
    @endif

    {{-- ── Status cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">

        {{-- Site status --}}
        <div class="sm:col-span-2 lg:col-span-1 bg-white rounded-2xl border
            {{ $isDown ? 'border-red-200 bg-red-50' : ($isWarning ? 'border-amber-200 bg-amber-50' : 'border-gray-200') }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Site Status</p>
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ $isDown ? 'bg-red-100 text-red-700' : ($isWarning ? 'bg-amber-100 text-amber-700' : ($isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600')) }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isDown ? 'bg-red-500 animate-pulse' : ($isWarning ? 'bg-amber-500' : ($isActive ? 'bg-green-500' : 'bg-gray-400')) }}"></span>
                    @if ($isDown) Site is Down
                    @elseif ($isWarning) Warning
                    @elseif ($isActive) Site is Up
                    @elseif ($isPending) Setting Up
                    @else Unknown @endif
                </span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed">
                @if ($isDown)
                    Your site is not responding.
                    @if ($site->last_seen_at) Last seen {{ $site->last_seen_at->diffForHumans() }}. @endif
                    Our team may already be investigating.
                @elseif ($isWarning)
                    Something needs attention.
                    @if ($site->last_seen_at) Checked {{ $site->last_seen_at->diffForHumans() }}. @endif
                @elseif ($isActive)
                    Everything looks good.
                    @if ($site->last_seen_at) Last checked {{ $site->last_seen_at->diffForHumans() }}. @endif
                @elseif ($isPending)
                    Waiting for the monitoring plugin to connect.
                @else
                    Status unknown — agent may not be active.
                @endif
            </p>
        </div>

        {{-- Uptime --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Uptime (30 days)</p>
            @if ($hasUptimeData)
                @php $uptime = (float) $site->uptime_30d; @endphp
                <p class="text-3xl font-bold {{ $uptime < 95 ? 'text-red-600' : ($uptime < 99 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ number_format($uptime, 2) }}%
                </p>
                <p class="text-xs mt-1 {{ $uptime >= 99.9 ? 'text-green-600' : ($uptime >= 99 ? 'text-gray-500' : 'text-amber-600') }}">
                    {{ $uptime >= 99.9 ? 'Excellent' : ($uptime >= 99 ? 'Good' : ($uptime >= 95 ? 'Fair' : 'Poor')) }}
                </p>
            @elseif ($agentConnected)
                <p class="text-lg font-semibold text-gray-400 mt-1">Collecting…</p>
                <p class="text-xs text-gray-400 mt-1">Data arrives within 24 h</p>
            @else
                <p class="text-lg font-semibold text-gray-300 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1">Agent not connected</p>
            @endif
        </div>

        {{-- SSL --}}
        @php
            $sslWarning = $site->ssl_expires_at && (int) now()->diffInDays($site->ssl_expires_at, false) <= 30;
        @endphp
        <div class="bg-white rounded-2xl border {{ $sslWarning ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">SSL Certificate</p>
            @if ($site->ssl_expires_at)
                @php $sslDays = (int) now()->diffInDays($site->ssl_expires_at, false); @endphp
                <p class="text-2xl font-bold {{ $sslDays <= 14 ? 'text-red-600' : ($sslDays <= 30 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ $sslDays > 0 ? $sslDays . ' days' : 'Expired' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $site->ssl_valid ? '✓ Valid' : '✗ Invalid' }}
                    @if ($sslDays > 0 && $sslDays <= 30) · Renew soon @endif
                </p>
            @else
                <p class="text-sm text-gray-400 mt-1">Not checked yet</p>
                <p class="text-xs text-gray-400 mt-0.5">Checked daily once connected</p>
            @endif
        </div>

        {{-- Domain --}}
        @php
            $domWarning = $site->domain_expires_at && (int) now()->diffInDays($site->domain_expires_at, false) <= 60;
        @endphp
        <div class="bg-white rounded-2xl border {{ $domWarning ? 'border-amber-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Domain Expiry</p>
            @if ($site->domain_expires_at)
                @php $domDays = (int) now()->diffInDays($site->domain_expires_at, false); @endphp
                <p class="text-2xl font-bold {{ $domDays <= 14 ? 'text-red-600' : ($domDays <= 60 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ $domDays > 0 ? $domDays . ' days' : 'Expired' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if ($site->registrar) via {{ Str::limit($site->registrar, 20) }} @else ✓ Active @endif
                </p>
            @else
                <p class="text-sm text-gray-400 mt-1">Not checked yet</p>
                <p class="text-xs text-gray-400 mt-0.5">Checked daily automatically</p>
            @endif
        </div>

        {{-- Last Backup --}}
        <div class="bg-white rounded-2xl border {{ $lastBackup && $lastBackup->status->value !== 'success' ? 'border-red-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last Backup</p>
            @if ($lastBackup)
                <p class="text-xl font-bold text-gray-900 leading-tight">
                    {{ $lastBackup->created_at->diffForHumans() }}
                </p>
                <p class="text-xs mt-1 {{ $lastBackup->status->value === 'success' ? 'text-green-600' : 'text-red-500' }}">
                    {{ $lastBackup->status->value === 'success' ? '✓ Successful' : '✗ ' . ucfirst($lastBackup->status->value) }}
                    @if ($lastBackup->size_bytes)
                        · {{ number_format($lastBackup->size_bytes / 1048576, 1) }} MB
                    @endif
                </p>
            @elseif ($agentConnected)
                <p class="text-sm font-medium text-gray-400 mt-1">Scheduled</p>
                <p class="text-xs text-gray-400 mt-0.5">First backup runs tonight</p>
            @else
                <p class="text-sm font-medium text-gray-300 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-0.5">Requires plugin connection</p>
            @endif
        </div>

    </div>

    {{-- ── Quick stats row ─────────────────────────────────────────────────── --}}
    @if ($agentConnected && $site->wp_version)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400 mb-0.5">WordPress</p>
                <p class="text-sm font-semibold text-gray-800">{{ $site->wp_version ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400 mb-0.5">PHP</p>
                <p class="text-sm font-semibold text-gray-800">{{ $site->php_version ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400 mb-0.5">Plugins</p>
                <p class="text-sm font-semibold text-gray-800">{{ $site->plugin_count ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400 mb-0.5">Disk Usage</p>
                <p class="text-sm font-semibold text-gray-800">
                    {{ $site->disk_usage_mb ? number_format($site->disk_usage_mb) . ' MB' : '—' }}
                </p>
            </div>
        </div>
    @endif

    {{-- ── Recent Activity ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Recent Activity</h2>
            <a href="{{ route('portal.events') }}" class="text-xs text-emerald-600 hover:underline font-medium">View all →</a>
        </div>

        @if ($recentEvents->isEmpty())
            <div class="px-6 py-10 text-center">
                <p class="text-sm text-gray-500">
                    @if ($agentConnected) No events recorded yet — that's a great sign! ✓
                    @else Connect the monitoring plugin to start receiving alerts.
                    @endif
                </p>
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($recentEvents as $event)
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        @php
                            $sevVal = $event->severity->value;
                            $iconClass = $sevVal === 'critical' ? 'text-red-500' : ($sevVal === 'warning' ? 'text-amber-500' : ($sevVal === 'success' ? 'text-green-500' : 'text-gray-400'));
                        @endphp
                        <span class="mt-0.5 flex-shrink-0 text-base {{ $iconClass }}">
                            @if ($sevVal === 'critical') ✗
                            @elseif ($sevVal === 'warning') ⚠
                            @elseif ($sevVal === 'success') ✓
                            @else ℹ @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $event->title }}</p>
                            @if ($event->message)
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $event->message }}</p>
                            @endif
                        </div>
                        <time class="flex-shrink-0 text-xs text-gray-400 mt-0.5">
                            {{ $event->created_at->format('M j, H:i') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @endif {{-- site --}}

    {{-- Livewire loading indicator --}}
    <div wire:loading class="fixed bottom-4 right-4 z-50 bg-white border border-gray-200 rounded-full px-4 py-2 text-xs text-gray-500 shadow-md">
        Refreshing…
    </div>
</div>
