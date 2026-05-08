<div wire:poll.60000ms="refresh">

{{-- ══════════════════════════════════════════════════════════════════════
     OVERVIEW MODE
══════════════════════════════════════════════════════════════════════ --}}
@if ($view === 'overview')

    {{-- Header --}}
    <div class="mb-7">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', $client->name)[0] }}.
        </h1>
        <p class="text-sm text-gray-500 mt-1">Here's an overview of all your websites.</p>
    </div>

    {{-- Summary strip --}}
    @if ($allSites->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-7">
        <div class="bg-white rounded-2xl border border-gray-200 px-5 py-5 shadow-sm">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Total Sites</p>
            <p class="text-4xl font-bold text-gray-900 leading-none">{{ $allSites->count() }}</p>
            <p class="text-xs text-gray-400 mt-2">under your plan</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $summaryDown > 0 ? 'border-red-200' : 'border-gray-200' }} px-5 py-5 shadow-sm {{ $summaryDown > 0 ? 'bg-red-50/40' : '' }}">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Sites Down</p>
            <p class="text-4xl font-bold leading-none {{ $summaryDown > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $summaryDown }}</p>
            <p class="text-xs mt-2 {{ $summaryDown > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $summaryDown > 0 ? 'requires attention' : 'all online' }}</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $summarySslSoon > 0 ? 'border-amber-200' : 'border-gray-200' }} px-5 py-5 shadow-sm {{ $summarySslSoon > 0 ? 'bg-amber-50/40' : '' }}">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">SSL Expiring</p>
            <p class="text-4xl font-bold leading-none {{ $summarySslSoon > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $summarySslSoon }}</p>
            <p class="text-xs mt-2 {{ $summarySslSoon > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $summarySslSoon > 0 ? 'renew soon' : 'all valid' }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 px-5 py-5 shadow-sm">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Monitored</p>
            <p class="text-4xl font-bold text-gray-900 leading-none">{{ $allSites->filter(fn($s) => $s->uptime_30d !== null)->count() }}</p>
            <p class="text-xs text-gray-400 mt-2">active sites</p>
        </div>
    </div>
    @endif

    {{-- No sites empty state --}}
    @if ($allSites->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Add your first website</h2>
            <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Connect a website and we'll monitor uptime, SSL, domain expiry, and backups 24/7.</p>
            <a href="{{ route('portal.my-websites') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Website
            </a>
        </div>

    {{-- Site cards grid --}}
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
            @foreach ($allSites as $s)
                @php
                    $sv       = $s->status->value;
                    $isDown   = $sv === 'down';
                    $isWarn   = $sv === 'warning';
                    $isActive = $sv === 'active';
                    $uptime   = $s->uptime_30d !== null ? number_format((float)$s->uptime_30d, 1).'%' : '—';
                    $sslDays  = $s->ssl_expires_at ? (int) now()->diffInDays($s->ssl_expires_at, false) : null;
                    $domDays  = $s->domain_expires_at ? (int) now()->diffInDays($s->domain_expires_at, false) : null;
                    $lastSeen = $s->last_seen_at;
                    $lastSeenOld = $lastSeen && $lastSeen->diffInHours(now()) > 24;
                @endphp
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col
                    {{ $isDown ? 'border-l-4 border-l-red-500' : ($isWarn ? 'border-l-4 border-l-amber-400' : '') }}">

                    {{-- Card header --}}
                    <div class="px-5 pt-5 pb-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $s->name }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ parse_url($s->url, PHP_URL_HOST) }}</p>
                        </div>
                        <span class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $isDown ? 'bg-red-100 text-red-700' : ($isWarn ? 'bg-amber-100 text-amber-700' : ($isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500')) }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isDown ? 'bg-red-500 animate-pulse' : ($isWarn ? 'bg-amber-500' : ($isActive ? 'bg-emerald-500' : 'bg-gray-400')) }}"></span>
                            @if($isDown) Down @elseif($isWarn) Warning @elseif($isActive) Online @else Pending @endif
                        </span>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 mx-5"></div>

                    {{-- Metrics row --}}
                    <div class="grid grid-cols-4 px-5 py-3 gap-0">
                        <div class="pr-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                Uptime
                            </p>
                            <p class="text-sm font-bold {{ (float)($s->uptime_30d ?? 100) < 99 ? 'text-amber-600' : 'text-gray-900' }}">{{ $uptime }}</p>
                        </div>
                        <div class="px-3 border-l border-gray-100">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                SSL
                            </p>
                            <p class="text-sm font-bold {{ $sslDays !== null && $sslDays <= 14 ? 'text-red-600' : ($sslDays !== null && $sslDays <= 30 ? 'text-amber-600' : 'text-gray-900') }}">{{ $sslDays !== null ? $sslDays.'d' : '—' }}</p>
                        </div>
                        <div class="px-3 border-l border-gray-100">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3"/></svg>
                                Domain
                            </p>
                            <p class="text-sm font-bold {{ $domDays !== null && $domDays <= 14 ? 'text-red-600' : ($domDays !== null && $domDays <= 60 ? 'text-amber-600' : 'text-gray-900') }}">{{ $domDays !== null ? $domDays.'d' : '—' }}</p>
                        </div>
                        <div class="pl-3 border-l border-gray-100">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Last Seen
                            </p>
                            <p class="text-sm font-bold {{ $lastSeenOld ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $lastSeen ? $lastSeen->diffForHumans(null, true, true) : '—' }}
                            </p>
                        </div>
                    </div>

                    {{-- Plugin not connected warning --}}
                    @if (! $s->agent_installed_at)
                        <div class="mx-5 mb-3 flex items-center gap-2 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-xs text-amber-700">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            Plugin not connected —
                            <a href="{{ route('portal.my-websites') }}" class="font-semibold underline decoration-amber-400 hover:text-amber-900">install it</a>
                            to enable monitoring.
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-auto border-t border-gray-100 px-5 py-3 flex items-center gap-3">
                        <a href="{{ route('portal.dashboard', ['site_id' => $s->id]) }}"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 border border-emerald-200 bg-white hover:bg-emerald-50 px-3 py-1.5 rounded-lg transition-colors">
                            View Dashboard
                        </a>
                        <a href="{{ route('portal.events') }}" class="text-xs text-gray-400 hover:text-gray-700 transition-colors">Events</a>
                        <a href="{{ route('portal.reports') }}" class="text-xs text-gray-400 hover:text-gray-700 transition-colors">Reports</a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent Activity --}}
        @if (isset($recentEvents) && $recentEvents->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Recent Activity</h2>
                <a href="{{ route('portal.events') }}" class="text-xs text-emerald-600 hover:underline font-medium">View all activity →</a>
            </div>
            <ul class="divide-y divide-gray-50">
                @foreach ($recentEvents as $event)
                    @php
                        $sev      = $event->severity->value;
                        $icon     = $sev === 'critical' ? '✗' : ($sev === 'warning' ? '⚠' : ($sev === 'success' ? '✓' : 'ℹ'));
                        $dotColor = $sev === 'critical' ? 'bg-red-500' : ($sev === 'warning' ? 'bg-amber-500' : ($sev === 'success' ? 'bg-emerald-500' : 'bg-gray-400'));
                        $iconColor= $sev === 'critical' ? 'text-red-500' : ($sev === 'warning' ? 'text-amber-500' : ($sev === 'success' ? 'text-emerald-600' : 'text-gray-400'));
                    @endphp
                    <li class="flex items-center gap-3 px-6 py-3.5">
                        <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center rounded-full {{ $sev === 'success' ? 'bg-emerald-50' : ($sev === 'critical' ? 'bg-red-50' : ($sev === 'warning' ? 'bg-amber-50' : 'bg-gray-50')) }}">
                            <span class="text-xs {{ $iconColor }}">{{ $icon }}</span>
                        </span>
                        <p class="flex-1 text-sm text-gray-800 truncate">{{ $event->title }}</p>
                        <time class="flex-shrink-0 text-xs text-gray-400">{{ $event->created_at->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    @endif


{{-- ══════════════════════════════════════════════════════════════════════
     DETAIL MODE
══════════════════════════════════════════════════════════════════════ --}}
@elseif ($view === 'detail')

    {{-- No site at all --}}
    @if (! $site && $allSites->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50">
                <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Welcome to ReviveGuard!</h2>
            <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Add your first website to start monitoring uptime, SSL, backups, and domain expiry — 24/7.</p>
            <a href="{{ route('portal.my-websites') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
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

    {{-- ── Page header ─────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4 mb-7">
        <div class="min-w-0">
            @if ($allSites->count() > 1)
                <button wire:click="backToOverview"
                    class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-emerald-600 mb-2.5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    All websites
                </button>
            @endif
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $site->name }}</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                    {{ $isDown ? 'bg-red-100 text-red-700' : ($isWarning ? 'bg-amber-100 text-amber-700' : ($isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500')) }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isDown ? 'bg-red-500 animate-pulse' : ($isWarning ? 'bg-amber-500' : ($isActive ? 'bg-emerald-500' : 'bg-gray-400')) }}"></span>
                    @if($isDown) Down @elseif($isWarning) Warning @elseif($isActive) Online @else Setting up @endif
                </span>
            </div>
            <a href="{{ $site->url }}" target="_blank" rel="noopener"
               class="text-xs text-gray-400 hover:text-emerald-600 mt-1 inline-flex items-center gap-1 transition-colors">
                {{ $site->url }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>

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

    {{-- ── Plugin not connected banner ─────────────────────────────── --}}
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

    {{-- ── 5 stat cards ────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

        <div class="lg:col-span-1 bg-white rounded-2xl border shadow-sm {{ $isDown ? 'border-red-200 bg-red-50/40' : ($isWarning ? 'border-amber-200 bg-amber-50/40' : 'border-gray-200') }} p-5">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Site Status</p>
            <p class="text-base font-bold {{ $isDown ? 'text-red-600' : ($isWarning ? 'text-amber-600' : ($isActive ? 'text-emerald-600' : 'text-gray-500')) }}">
                @if($isDown) Site is Down @elseif($isWarning) Warning @elseif($isActive) Online @elseif($isPending) Setting Up @else Unknown @endif
            </p>
            <p class="text-xs text-gray-400 mt-2 leading-relaxed">
                @if($isDown && $site->last_seen_at) Last seen {{ $site->last_seen_at->diffForHumans() }}
                @elseif($isActive && $site->last_seen_at) Checked {{ $site->last_seen_at->diffForHumans() }}
                @elseif($isPending) Waiting for plugin
                @else &nbsp; @endif
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Uptime (30 days)</p>
            @if ($hasUptime)
                @php $up = (float) $site->uptime_30d; @endphp
                <p class="text-3xl font-bold {{ $up < 95 ? 'text-red-600' : ($up < 99 ? 'text-amber-600' : 'text-gray-900') }}">{{ number_format($up, 1) }}%</p>
                <p class="text-xs mt-1.5 {{ $up >= 99.9 ? 'text-emerald-600' : ($up >= 99 ? 'text-gray-400' : 'text-amber-500') }}">{{ $up >= 99.9 ? '✓ Excellent' : ($up >= 99 ? 'Good' : ($up >= 95 ? 'Fair' : '✗ Poor')) }}</p>
            @elseif ($agentConnected)
                <p class="text-xl font-semibold text-gray-400 mt-1">Collecting…</p>
                <p class="text-xs text-gray-400 mt-1.5">Ready within 24 h</p>
            @else
                <p class="text-3xl font-bold text-gray-200 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1.5">Plugin required</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border shadow-sm {{ $sslDays !== null && $sslDays <= 14 ? 'border-red-200' : ($sslDays !== null && $sslDays <= 30 ? 'border-amber-200' : 'border-gray-200') }} p-5">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">SSL Certificate</p>
            @if ($sslDays !== null)
                <p class="text-3xl font-bold {{ $sslDays <= 14 ? 'text-red-600' : ($sslDays <= 30 ? 'text-amber-600' : 'text-gray-900') }}">{{ $sslDays > 0 ? $sslDays.'d' : 'Expired' }}</p>
                <p class="text-xs mt-1.5 {{ $site->ssl_valid ? 'text-emerald-600' : 'text-red-500' }}">{{ $site->ssl_valid ? '✓ Valid' : '✗ Invalid' }}@if($sslDays > 0 && $sslDays <= 30) · Renew soon @endif</p>
            @else
                <p class="text-xl font-semibold text-gray-400 mt-1">Pending</p>
                <p class="text-xs text-gray-400 mt-1.5">Checked daily</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border shadow-sm {{ $domDays !== null && $domDays <= 14 ? 'border-red-200' : ($domDays !== null && $domDays <= 60 ? 'border-amber-200' : 'border-gray-200') }} p-5">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Domain Expiry</p>
            @if ($domDays !== null)
                <p class="text-3xl font-bold {{ $domDays <= 14 ? 'text-red-600' : ($domDays <= 60 ? 'text-amber-600' : 'text-gray-900') }}">{{ $domDays > 0 ? $domDays.'d' : 'Expired' }}</p>
                <p class="text-xs text-gray-400 mt-1.5">@if($site->registrar) {{ Str::limit($site->registrar, 22) }} @else ✓ Active @endif</p>
            @else
                <p class="text-xl font-semibold text-gray-400 mt-1">Pending</p>
                <p class="text-xs text-gray-400 mt-1.5">Checked daily</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border shadow-sm {{ $lastBackup && $lastBackup->status->value === 'failed' ? 'border-red-200' : 'border-gray-200' }} p-5">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Last Backup</p>
            @if ($lastBackup)
                <p class="text-xl font-bold text-gray-900 leading-tight">{{ $lastBackup->created_at->diffForHumans(null, true, true) }} ago</p>
                <p class="text-xs mt-1.5 {{ $lastBackup->status->value === 'success' ? 'text-emerald-600' : 'text-red-500' }}">{{ $lastBackup->status->value === 'success' ? '✓ Successful' : '✗ '.ucfirst($lastBackup->status->value) }}@if($lastBackup->size_bytes) · {{ number_format($lastBackup->size_bytes / 1048576, 0) }} MB @endif</p>
            @elseif ($agentConnected)
                <p class="text-xl font-semibold text-gray-500 mt-1">Scheduled</p>
                <p class="text-xs text-gray-400 mt-1.5">First backup tonight</p>
            @else
                <p class="text-3xl font-bold text-gray-200 mt-1">—</p>
                <p class="text-xs text-gray-400 mt-1.5">Plugin required</p>
            @endif
        </div>

    </div>

    {{-- ── Quick stats ──────────────────────────────────────────────── --}}
    @if ($agentConnected && $site->wp_version)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 1.5a8.5 8.5 0 110 17 8.5 8.5 0 010-17z"/></svg>
                <div><p class="text-[10px] text-gray-400 uppercase tracking-wide">WordPress</p><p class="text-sm font-semibold text-gray-800">{{ $site->wp_version }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                <div><p class="text-[10px] text-gray-400 uppercase tracking-wide">PHP</p><p class="text-sm font-semibold text-gray-800">{{ $site->php_version ?? '—' }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <div><p class="text-[10px] text-gray-400 uppercase tracking-wide">Plugins</p><p class="text-sm font-semibold text-gray-800">{{ $site->plugin_count ?? '—' }}</p></div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                <div><p class="text-[10px] text-gray-400 uppercase tracking-wide">Disk</p><p class="text-sm font-semibold text-gray-800">{{ $site->disk_usage_mb ? number_format($site->disk_usage_mb).' MB' : '—' }}</p></div>
            </div>
        </div>
    @endif

    {{-- ── Recent Activity ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Recent Activity</h2>
            <a href="{{ route('portal.events') }}" class="text-xs text-emerald-600 hover:underline font-medium">View all →</a>
        </div>
        @if ($recentEvents->isEmpty())
            <div class="px-6 py-10 text-center">
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
                        $iconColor= $sev === 'critical' ? 'text-red-500' : ($sev === 'warning' ? 'text-amber-500' : ($sev === 'success' ? 'text-emerald-600' : 'text-gray-400'));
                    @endphp
                    <li class="flex items-center gap-3 px-6 py-3.5">
                        <span class="w-6 h-6 flex-shrink-0 flex items-center justify-center rounded-full {{ $sev === 'success' ? 'bg-emerald-50' : ($sev === 'critical' ? 'bg-red-50' : ($sev === 'warning' ? 'bg-amber-50' : 'bg-gray-50')) }}">
                            <span class="text-xs {{ $iconColor }}">{{ $icon }}</span>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 truncate">{{ $event->title }}</p>
                            @if ($event->message)<p class="text-xs text-gray-400 truncate">{{ $event->message }}</p>@endif
                        </div>
                        <time class="flex-shrink-0 text-xs text-gray-400">{{ $event->created_at->format('M j, H:i') }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @endif {{-- site --}}
@endif {{-- view --}}

<div wire:loading class="fixed bottom-4 right-4 z-50 bg-white border border-gray-100 rounded-full px-4 py-2 text-xs text-gray-400 shadow-lg">
    Refreshing…
</div>

</div>