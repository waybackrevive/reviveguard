<div wire:poll.60000ms="refresh">

{{-- ═══════════════════════════════════════════════════════════════════════════
     OVERVIEW MODE — shown when client has multiple sites
═══════════════════════════════════════════════════════════════════════════ --}}
@if ($view === 'overview')

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
                {{ explode(' ', $client->name)[0] }}.
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Here's an overview of all your websites.</p>
        </div>
        <a href="{{ route('portal.my-websites') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Website
        </a>
    </div>

    {{-- Summary strip --}}
    @if ($allSites->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $allSites->count() }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total Sites</p>
        </div>
        <div class="bg-white rounded-xl border {{ $summaryDown > 0 ? 'border-red-200 bg-red-50' : 'border-gray-200' }} px-4 py-3 text-center">
            <p class="text-2xl font-bold {{ $summaryDown > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $summaryDown }}</p>
            <p class="text-xs {{ $summaryDown > 0 ? 'text-red-500' : 'text-gray-500' }} mt-0.5">Sites Down</p>
        </div>
        <div class="bg-white rounded-xl border {{ $summarySslSoon > 0 ? 'border-amber-200 bg-amber-50' : 'border-gray-200' }} px-4 py-3 text-center">
            <p class="text-2xl font-bold {{ $summarySslSoon > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $summarySslSoon }}</p>
            <p class="text-xs {{ $summarySslSoon > 0 ? 'text-amber-600' : 'text-gray-500' }} mt-0.5">SSL Expiring</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">
                {{ $allSites->filter(fn($s) => $s->uptime_30d !== null)->count() }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5">Monitored</p>
        </div>
    </div>
    @endif

    {{-- No sites yet --}}
    @if ($allSites->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Add your first website</h2>
            <p class="text-sm text-gray-500 mb-5 max-w-sm mx-auto">Connect a website and we'll monitor uptime, SSL, domain expiry, and backups 24/7.</p>
            <a href="{{ route('portal.my-websites') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Website
            </a>
        </div>

    {{-- Site cards grid --}}
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($allSites as $s)
                @php
                    $sv       = $s->status->value;
                    $isDown   = $sv === 'down';
                    $isWarn   = $sv === 'warning';
                    $isActive = $sv === 'active';
                    $uptime   = $s->uptime_30d !== null ? number_format((float)$s->uptime_30d, 1).'%' : '—';
                    $sslDays  = $s->ssl_expires_at ? (int) now()->diffInDays($s->ssl_expires_at, false) : null;
                    $domDays  = $s->domain_expires_at ? (int) now()->diffInDays($s->domain_expires_at, false) : null;
                @endphp
                <div class="bg-white rounded-2xl border {{ $isDown ? 'border-red-200' : ($isWarn ? 'border-amber-200' : 'border-gray-200') }} p-5 flex flex-col gap-4">

                    {{-- Site header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full flex-shrink-0
                                    {{ $isDown ? 'bg-red-500 animate-pulse' : ($isWarn ? 'bg-amber-500' : ($isActive ? 'bg-emerald-500' : 'bg-gray-400')) }}"></span>
                                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $s->name }}</h3>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5 truncate pl-4">{{ parse_url($s->url, PHP_URL_HOST) }}</p>
                        </div>
                        <span class="flex-shrink-0 inline-block px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $isDown ? 'bg-red-100 text-red-700' : ($isWarn ? 'bg-amber-100 text-amber-700' : ($isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500')) }}">
                            @if($isDown) Down
                            @elseif($isWarn) Warning
                            @elseif($isActive) Online
                            @else Pending @endif
                        </span>
                    </div>

                    {{-- Key metrics row --}}
                    <div class="grid grid-cols-4 divide-x divide-gray-100 text-center">
                        <div class="px-2">
                            <p class="text-base font-bold {{ (float)($s->uptime_30d ?? 100) < 99 ? 'text-amber-600' : 'text-gray-900' }}">{{ $uptime }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Uptime</p>
                        </div>
                        <div class="px-2">
                            <p class="text-base font-bold {{ $sslDays !== null && $sslDays <= 14 ? 'text-red-600' : ($sslDays !== null && $sslDays <= 30 ? 'text-amber-600' : 'text-gray-900') }}">
                                {{ $sslDays !== null ? $sslDays.'d' : '—' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">SSL</p>
                        </div>
                        <div class="px-2">
                            <p class="text-base font-bold {{ $domDays !== null && $domDays <= 14 ? 'text-red-600' : ($domDays !== null && $domDays <= 60 ? 'text-amber-600' : 'text-gray-900') }}">
                                {{ $domDays !== null ? $domDays.'d' : '—' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Domain</p>
                        </div>
                        <div class="px-2">
                            <p class="text-base font-bold text-gray-900">
                                {{ $s->last_seen_at ? $s->last_seen_at->diffForHumans(null, true, true) : '—' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Last seen</p>
                        </div>
                    </div>

                    {{-- No agent inline warning --}}
                    @if (! $s->agent_installed_at)
                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                            ⚠ Plugin not connected —
                            <a href="{{ route('portal.my-websites') }}" class="underline font-medium">install it</a>
                            to enable monitoring.
                        </p>
                    @endif

                    {{-- Action --}}
                    <button wire:click="viewSite('{{ $s->id }}')"
                        class="mt-auto w-full py-2 text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors">
                        View Details →
                    </button>
                </div>
            @endforeach
        </div>
    @endif


{{-- ═══════════════════════════════════════════════════════════════════════════
     DETAIL MODE — single site full dashboard
═══════════════════════════════════════════════════════════════════════════ --}}
@elseif ($view === 'detail')

    {{-- No site at all --}}
    @if (! $site && $allSites->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Welcome to ReviveGuard!</h2>
            <p class="text-sm text-gray-500 mb-5 max-w-sm mx-auto">Add your first website to start monitoring uptime, SSL, backups, and domain expiry — 24/7.</p>
            <a href="{{ route('portal.my-websites') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Your First Website
            </a>
        </div>

    @elseif ($site)

    @php
        $sv             = $site->status->value;
        $isDown         = $sv === 'down';
        $isWarning      = $sv === 'warning';
        $isActive       = $sv === 'active';
        $isPending      = $sv === 'pending';
        $agentConnected = ! is_null($site->agent_installed_at);
        $hasUptime      = $site->uptime_30d !== null;
        $sslDays        = $site->ssl_expires_at ? (int) now()->diffInDays($site->ssl_expires_at, false) : null;
        $domDays        = $site->domain_expires_at ? (int) now()->diffInDays($site->domain_expires_at, false) : null;
    @endphp

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            @if ($allSites->count() > 1)
                <button wire:click="backToOverview"
                    class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-emerald-600 mb-2 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    All websites
                </button>
            @endif
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $isDown ? 'bg-red-500 animate-pulse' : ($isWarning ? 'bg-amber-500' : ($isActive ? 'bg-emerald-500' : 'bg-gray-400')) }}"></span>
                <h1 class="text-xl font-semibold text-gray-900">{{ $site->name }}</h1>
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ $isDown ? 'bg-red-100 text-red-700' : ($isWarning ? 'bg-amber-100 text-amber-700' : ($isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500')) }}">
                    @if($isDown) Down
                    @elseif($isWarning) Warning
                    @elseif($isActive) Online
                    @else Setting up @endif
                </span>
            </div>
            <a href="{{ $site->url }}" target="_blank" rel="noopener"
               class="text-xs text-gray-400 hover:text-emerald-600 mt-1 inline-flex items-center gap-1 transition-colors">
                {{ $site->url }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>

        {{-- Site switcher pills (multi-site only) --}}
        @if ($allSites->count() > 1)
            <div class="flex-shrink-0 flex flex-wrap gap-2 justify-end">
                @foreach ($allSites as $s)
                    <button wire:click="viewSite('{{ $s->id }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                            {{ $s->id === $site->id
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-400 hover:text-emerald-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $s->status->value === 'down' ? 'bg-red-400' : ($s->status->value === 'active' ? 'bg-emerald-400' : 'bg-gray-300') }}"></span>
                        {{ Str::limit($s->name, 18) }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Plugin not connected banner ──────────────────────────────── --}}
    @if (! $agentConnected)
        <div class="flex items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6">
            <div class="flex-shrink-0 flex h-9 w-9 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-800">Install the ReviveGuard plugin to start monitoring</p>
                <p class="text-xs text-amber-700 mt-0.5">Once installed, you'll see live uptime, backups, and alerts here.</p>
            </div>
            <a href="{{ route('portal.my-websites') }}"
               class="flex-shrink-0 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition-colors">
                Install plugin →
            </a>
        </div>
    @endif

    {{-- ── 5 stat cards ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-5">

        {{-- 1 · Site Status --}}
        <div class="lg:col-span-1 bg-white rounded-2xl border {{ $isDown ? 'border-red-200 bg-red-50' : ($isWarning ? 'border-amber-200 bg-amber-50' : 'border-gray-200') }} p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Site Status</p>
            <p class="text-base font-bold {{ $isDown ? 'text-red-600' : ($isWarning ? 'text-amber-600' : ($isActive ? 'text-emerald-600' : 'text-gray-500')) }}">
                @if($isDown) Site is Down
                @elseif($isWarning) Warning
                @elseif($isActive) Online
                @elseif($isPending) Setting Up
                @else Unknown @endif
            </p>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                @if($isDown && $site->last_seen_at) Last seen {{ $site->last_seen_at->diffForHumans() }}
                @elseif($isActive && $site->last_seen_at) Checked {{ $site->last_seen_at->diffForHumans() }}
                @elseif($isPending) Waiting for plugin
                @else &nbsp; @endif
            </p>
        </div>

        {{-- 2 · Uptime --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Uptime (30 days)</p>
            @if ($hasUptime)
                @php $up = (float) $site->uptime_30d; @endphp
                <p class="text-3xl font-bold {{ $up < 95 ? 'text-red-600' : ($up < 99 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ number_format($up, 1) }}%
                </p>
                <p class="text-xs mt-1 {{ $up >= 99.9 ? 'text-emerald-600' : ($up >= 99 ? 'text-gray-500' : 'text-amber-600') }}">
                    {{ $up >= 99.9 ? '✓ Excellent' : ($up >= 99 ? 'Good' : ($up >= 95 ? 'Fair' : '✗ Poor')) }}
                </p>
            @elseif ($agentConnected)
                <p class="text-lg font-semibold text-gray-400">Collecting…</p>
                <p class="text-xs text-gray-400 mt-1">Ready within 24 h</p>
            @else
                <p class="text-2xl font-bold text-gray-200">—</p>
                <p class="text-xs text-gray-400 mt-1">Plugin required</p>
            @endif
        </div>

        {{-- 3 · SSL --}}
        <div class="bg-white rounded-2xl border {{ $sslDays !== null && $sslDays <= 14 ? 'border-red-200' : ($sslDays !== null && $sslDays <= 30 ? 'border-amber-200' : 'border-gray-200') }} p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">SSL Certificate</p>
            @if ($sslDays !== null)
                <p class="text-3xl font-bold {{ $sslDays <= 14 ? 'text-red-600' : ($sslDays <= 30 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ $sslDays > 0 ? $sslDays.' days' : 'Expired' }}
                </p>
                <p class="text-xs mt-1 {{ $site->ssl_valid ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $site->ssl_valid ? '✓ Valid' : '✗ Invalid' }}
                    @if($sslDays > 0 && $sslDays <= 30) · Renew soon @endif
                </p>
            @else
                <p class="text-xl font-semibold text-gray-400">Pending</p>
                <p class="text-xs text-gray-400 mt-1">Checked daily</p>
            @endif
        </div>

        {{-- 4 · Domain --}}
        <div class="bg-white rounded-2xl border {{ $domDays !== null && $domDays <= 14 ? 'border-red-200' : ($domDays !== null && $domDays <= 60 ? 'border-amber-200' : 'border-gray-200') }} p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Domain Expiry</p>
            @if ($domDays !== null)
                <p class="text-3xl font-bold {{ $domDays <= 14 ? 'text-red-600' : ($domDays <= 60 ? 'text-amber-600' : 'text-gray-900') }}">
                    {{ $domDays > 0 ? $domDays.' days' : 'Expired' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if($site->registrar) {{ Str::limit($site->registrar, 22) }} @else ✓ Active @endif
                </p>
            @else
                <p class="text-xl font-semibold text-gray-400">Pending</p>
                <p class="text-xs text-gray-400 mt-1">Checked daily</p>
            @endif
        </div>

        {{-- 5 · Last Backup --}}
        <div class="bg-white rounded-2xl border {{ $lastBackup && $lastBackup->status->value === 'failed' ? 'border-red-200' : 'border-gray-200' }} p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Last Backup</p>
            @if ($lastBackup)
                <p class="text-lg font-bold text-gray-900 leading-tight">
                    {{ $lastBackup->created_at->diffForHumans(null, true, true) }} ago
                </p>
                <p class="text-xs mt-1 {{ $lastBackup->status->value === 'success' ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $lastBackup->status->value === 'success' ? '✓ Successful' : '✗ '.ucfirst($lastBackup->status->value) }}
                    @if($lastBackup->size_bytes) · {{ number_format($lastBackup->size_bytes / 1048576, 0) }} MB @endif
                </p>
            @elseif ($agentConnected)
                <p class="text-lg font-semibold text-gray-500">Scheduled</p>
                <p class="text-xs text-gray-400 mt-1">First backup tonight</p>
            @else
                <p class="text-2xl font-bold text-gray-200">—</p>
                <p class="text-xs text-gray-400 mt-1">Plugin required</p>
            @endif
        </div>

    </div>

    {{-- ── Quick stats (only if agent is sending data) ──────────────── --}}
    @if ($agentConnected && $site->wp_version)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 1.5a8.5 8.5 0 110 17 8.5 8.5 0 010-17z"/></svg>
                <div><p class="text-xs text-gray-400">WordPress</p><p class="text-sm font-semibold text-gray-800">{{ $site->wp_version }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                <div><p class="text-xs text-gray-400">PHP</p><p class="text-sm font-semibold text-gray-800">{{ $site->php_version ?? '—' }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <div><p class="text-xs text-gray-400">Plugins</p><p class="text-sm font-semibold text-gray-800">{{ $site->plugin_count ?? '—' }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                <div><p class="text-xs text-gray-400">Disk</p><p class="text-sm font-semibold text-gray-800">{{ $site->disk_usage_mb ? number_format($site->disk_usage_mb).' MB' : '—' }}</p></div>
            </div>
        </div>
    @endif

    {{-- ── Recent Activity ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recent Activity</h2>
            <a href="{{ route('portal.events') }}" class="text-xs text-emerald-600 hover:underline font-medium">View all →</a>
        </div>

        @if ($recentEvents->isEmpty())
            <div class="px-6 py-8 text-center">
                <p class="text-sm text-gray-400">
                    @if ($agentConnected) No events yet — everything is running smoothly. ✓
                    @else Install the plugin to start receiving activity alerts.
                    @endif
                </p>
            </div>
        @else
            <ul class="divide-y divide-gray-50">
                @foreach ($recentEvents as $event)
                    @php
                        $sev      = $event->severity->value;
                        $icon     = $sev === 'critical' ? '✗' : ($sev === 'warning' ? '⚠' : ($sev === 'success' ? '✓' : 'ℹ'));
                        $iconColor= $sev === 'critical' ? 'text-red-500' : ($sev === 'warning' ? 'text-amber-500' : ($sev === 'success' ? 'text-emerald-500' : 'text-gray-400'));
                    @endphp
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center text-sm {{ $iconColor }}">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 truncate">{{ $event->title }}</p>
                            @if ($event->message)
                                <p class="text-xs text-gray-400 truncate">{{ $event->message }}</p>
                            @endif
                        </div>
                        <time class="flex-shrink-0 text-xs text-gray-400">{{ $event->created_at->format('M j, H:i') }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @endif {{-- site --}}
@endif {{-- view --}}

{{-- Refresh indicator --}}
<div wire:loading class="fixed bottom-4 right-4 z-50 bg-white border border-gray-100 rounded-full px-4 py-2 text-xs text-gray-400 shadow-md">
    Refreshing…
</div>

</div>
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
