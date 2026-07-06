<div>
    @if (session('checkout_welcome'))
        @php $welcome = session('checkout_welcome'); @endphp
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">You're all set</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        <strong>{{ $welcome['plan'] }}</strong> is active for this site. Uptime, SSL, and domain monitoring are being enabled now
                        @if ($welcome['connected'] ?? false)
                            — metrics will populate shortly.
                        @else
                            — connect the plugin on the Connection tab to unlock full care.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-5">
        <a href="{{ route('portal.sites') }}" class="text-sm text-gray-500 hover:text-brand inline-flex items-center gap-1 mb-3">← All sites</a>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $site->displayName() }}</h1>
                @if ($site->client_label && $site->name !== $site->client_label)
                    <p class="text-sm text-gray-500">{{ $site->name }}</p>
                @endif
                <a href="{{ $site->url }}" target="_blank" rel="noopener" class="text-sm text-brand hover:underline">{{ $site->url }}</a>
            </div>
            @php $ps = $site->portalStatusKey(); @endphp
            <div class="flex items-center gap-2">
                @if ($ps === 'checkout')
                    <button wire:click="setTab('plan')" class="text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 px-3 py-1.5 rounded-lg">
                        Complete checkout →
                    </button>
                @elseif ($ps === 'setup')
                    <button wire:click="setTab('connection')" class="text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-3 py-1.5 rounded-lg">
                        Connect site →
                    </button>
                @endif
                @if ($canOpenWpAdmin ?? false)
                    <button wire:click="openWordPressAdmin"
                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:border-gray-400 px-3 py-1.5 rounded-lg shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2z"/></svg>
                        WP Admin
                    </button>
                @endif
                @if ($site->plan)
                    <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full font-medium">
                        {{ $site->plan->name }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-1 rounded-full
                    {{ $ps === 'protected' ? 'bg-emerald-100 text-emerald-800' : ($ps === 'issue' ? 'bg-red-100 text-red-800' : ($ps === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600')) }}">
                    {{ $site->portalStatusLabel() }}
                </span>
            </div>
        </div>
    </div>

    @if ($ps === 'checkout')
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Next:</strong> Choose your plan (if you haven't), then pay to activate protection. You can connect the plugin before or after payment.
            <button wire:click="setTab('plan')" class="ml-2 font-semibold text-amber-800 underline hover:no-underline">Go to Plan →</button>
        </div>
    @elseif ($ps === 'setup')
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <strong>Next:</strong> Download the plugin and paste your connection code.
            <button wire:click="setTab('connection')" class="ml-2 font-semibold text-emerald-800 underline hover:no-underline">Connection steps →</button>
        </div>
    @endif

    {{-- Sub-nav tabs --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200 overflow-x-auto">
        @foreach ([
            'overview' => 'Overview',
            'monitoring' => 'Monitoring',
            'activity' => 'Activity',
            'backups' => 'Backups',
            'reports' => 'Reports',
            'connection' => 'Connection',
            'plan' => 'Plan',
        ] as $key => $label)
            <button wire:click="setTab('{{ $key }}')"
                class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors
                    {{ $tab === $key ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    @if ($tab === 'overview')
        @if (! $site->hasPaidSubscription())
            <div class="mb-6 rounded-[10px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-semibold">Protection not active yet</p>
                <p class="mt-1">Your site can connect before payment, but monitoring, backups, and alerts start after you complete checkout.</p>
                <button wire:click="setTab('plan')" class="mt-3 text-sm font-semibold text-amber-800 underline hover:no-underline">Go to Plan →</button>
            </div>
        @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Uptime (30d)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $site->uptime_30d !== null ? number_format($site->uptime_30d, 1) . '%' : '—' }}</p>
            </div>
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">SSL certificate</p>
                @php $ssl = $site->sslExpiresInDays(); @endphp
                <p class="text-2xl font-bold {{ $ssl !== null && $ssl < 30 ? ($ssl < 0 ? 'text-red-600' : 'text-amber-600') : 'text-gray-900' }}">
                    {{ $ssl !== null ? ($ssl < 0 ? 'Expired' : $ssl . ' days left') : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Expires {{ $site->ssl_expires_at?->format('M j, Y') ?? 'not detected yet' }}
                </p>
            </div>
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Domain registration</p>
                @php $dom = $site->domainExpiresInDays(); @endphp
                <p class="text-2xl font-bold {{ $dom !== null && $dom < 30 ? ($dom < 0 ? 'text-red-600' : 'text-amber-600') : 'text-gray-900' }}">
                    {{ $dom !== null ? ($dom < 0 ? 'Expired' : $dom . ' days left') : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Expires {{ $site->domain_expires_at?->format('M j, Y') ?? 'not detected yet' }}
                </p>
            </div>
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Last backup</p>
                <p class="text-lg font-bold text-gray-900">{{ $latestBackup?->completed_at?->diffForHumans() ?? '—' }}</p>
            </div>
        </div>

        @if ($site->plan && in_array($site->plan->slug, ['guard', 'shield']))
            <div class="mb-6 rounded-[10px] border border-emerald-100 bg-emerald-50/50 px-5 py-4">
                <p class="text-sm font-semibold text-emerald-900">Managed by ReviveGuard</p>
                <p class="text-sm text-emerald-800 mt-1">Updates and backups are handled by our team on your {{ $site->plan->name }} plan.</p>
            </div>
        @endif

        <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Recent activity</h2>
                <button wire:click="setTab('activity')" class="text-xs text-brand font-medium hover:underline">View all</button>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($overviewEvents as $event)
                    <li class="px-5 py-3 text-sm">
                        <p class="font-medium text-gray-800">{{ $event->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $event->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-500">No notable activity yet. Your site is running smoothly.</li>
                @endforelse
            </ul>
        </div>
        @endif
    @endif

    {{-- Monitoring --}}
    @if ($tab === 'monitoring')
        @if (! $site->hasPaidSubscription())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Monitoring starts after you complete checkout on the Plan tab.
            </div>
        @else
            @php
                $isOnline = $lastProbe && $lastProbe->is_up && $site->status !== \App\Enums\SiteStatus::DOWN;
                $intervalLabel = \App\Support\MonitorSettings::intervalLabel((int) $site->monitor_interval_minutes);
                $regionLabel = \App\Support\MonitorSettings::regionLabel((string) $site->monitor_region);
                $sslM = $site->sslExpiresInDays();
                $domM = $site->domainExpiresInDays();
                $uptime7d = $monitoringSummary['uptime_7d'] ?? null;
                $hasIncidents = $uptimeIncidents->isNotEmpty();
                $sslOk = $sslM !== null && $sslM >= 30;
                $domOk = $domM !== null && $domM >= 30;
                $allClear = $isOnline && ! $site->monitoring_paused && ($uptime7d === null || $uptime7d >= 99) && ($sslM === null || $sslM >= 0) && ($domM === null || $domM >= 0);
            @endphp

            @if ($site->monitoring_paused)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-amber-950">Monitoring is paused</p>
                        <p class="text-sm text-amber-900 mt-1">We are not watching this site right now. Turn monitoring back on to get alerts if something goes wrong.</p>
                    </div>
                    <button wire:click="toggleMonitoringPause" class="text-sm font-semibold text-amber-900 bg-white border border-amber-300 px-4 py-2.5 rounded-lg hover:bg-amber-100 shrink-0">
                        Resume monitoring
                    </button>
                </div>
            @endif

            {{-- Hero — peace of mind first --}}
            <div class="mb-6 rounded-2xl border overflow-hidden {{ $site->monitoring_paused ? 'border-gray-200 bg-gray-50' : ($isOnline ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white' : 'border-red-200 bg-gradient-to-br from-red-50 via-white to-white') }}">
                <div class="px-6 py-8 sm:px-8 sm:py-10 text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                        <div class="mx-auto sm:mx-0 flex h-16 w-16 shrink-0 items-center justify-center rounded-full {{ $site->monitoring_paused ? 'bg-gray-200' : ($isOnline ? 'bg-emerald-100' : 'bg-red-100') }}">
                            @if ($site->monitoring_paused)
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif ($isOnline)
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            @else
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            @if ($site->monitoring_paused)
                                <h2 class="text-2xl font-bold text-gray-900">Your site is not being watched</h2>
                                <p class="text-gray-600 mt-2 max-w-xl mx-auto sm:mx-0">Resume monitoring when you are ready — we will pick up where we left off.</p>
                            @elseif ($isOnline)
                                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Your site is online — we&apos;re watching it</h2>
                                <p class="text-gray-600 mt-2 text-base max-w-xl mx-auto sm:mx-0 leading-relaxed">
                                    ReviveGuard checks <strong class="font-semibold text-gray-800">{{ $site->displayName() }}</strong> every {{ $intervalLabel }}.
                                    If anything goes wrong, we will alert you right away — you do not need to keep checking.
                                </p>
                                @if ($lastProbe)
                                    <p class="text-sm text-emerald-700 mt-3 font-medium">
                                        Last confirmed online {{ $lastProbe->checked_at->diffForHumans() }}
                                    </p>
                                @endif
                            @elseif ($lastProbe && ! $lastProbe->is_up)
                                <h2 class="text-2xl font-bold text-red-900">We could not reach your site</h2>
                                <p class="text-red-800/90 mt-2 max-w-xl">Our team has been notified. Check your email for details, or open a support ticket if you need help.</p>
                                @if ($lastProbe)
                                    <p class="text-sm text-red-700/80 mt-3">Detected {{ $lastProbe->checked_at->diffForHumans() }}</p>
                                @endif
                            @else
                                <h2 class="text-2xl font-bold text-gray-900">Setting up your monitoring</h2>
                                <p class="text-gray-600 mt-2">Your first check will run within {{ $intervalLabel }}. This page will update automatically.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Three things business owners care about --}}
            <div class="grid gap-4 sm:grid-cols-3 mb-6">
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $isOnline && ! $site->monitoring_paused ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </span>
                        <p class="text-sm font-semibold text-gray-900">Website</p>
                    </div>
                    <p class="text-lg font-bold {{ $isOnline && ! $site->monitoring_paused ? 'text-emerald-700' : ($site->monitoring_paused ? 'text-gray-500' : 'text-red-600') }}">
                        @if ($site->monitoring_paused)
                            Paused
                        @elseif ($isOnline)
                            Online &amp; reachable
                        @else
                            Needs attention
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        @if ($uptime7d !== null)
                            Up {{ number_format($uptime7d, 1) }}% this week
                        @else
                            Building your first week of history
                        @endif
                    </p>
                </div>

                <div class="bg-white rounded-2xl border {{ $sslM !== null && $sslM < 30 ? ($sslM < 0 ? 'border-red-200' : 'border-amber-200') : 'border-gray-200' }} p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $sslOk ? 'bg-emerald-100 text-emerald-600' : ($sslM === null ? 'bg-gray-100 text-gray-400' : 'bg-amber-100 text-amber-700') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <p class="text-sm font-semibold text-gray-900">Secure connection</p>
                    </div>
                    <p class="text-lg font-bold {{ $sslM === null ? 'text-gray-400' : ($sslM < 0 ? 'text-red-600' : ($sslM < 30 ? 'text-amber-600' : 'text-emerald-700')) }}">
                        @if ($sslM === null)
                            Checking…
                        @elseif ($sslM < 0)
                            Certificate expired
                        @elseif ($sslM < 30)
                            Renew soon
                        @else
                            Protected
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        @if ($site->ssl_expires_at)
                            Valid until {{ $site->ssl_expires_at->format('M j, Y') }}
                        @else
                            We check this daily for you
                        @endif
                    </p>
                </div>

                <div class="bg-white rounded-2xl border {{ $domM !== null && $domM < 30 ? ($domM < 0 ? 'border-red-200' : 'border-amber-200') : 'border-gray-200' }} p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $domOk ? 'bg-emerald-100 text-emerald-600' : ($domM === null ? 'bg-gray-100 text-gray-400' : 'bg-amber-100 text-amber-700') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <p class="text-sm font-semibold text-gray-900">Domain name</p>
                    </div>
                    <p class="text-lg font-bold {{ $domM === null ? 'text-gray-400' : ($domM < 0 ? 'text-red-600' : ($domM < 30 ? 'text-amber-600' : 'text-emerald-700')) }}">
                        @if ($domM === null)
                            Checking…
                        @elseif ($domM < 0)
                            Registration expired
                        @elseif ($domM < 30)
                            Renew soon
                        @else
                            Active
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        @if ($site->domain_expires_at)
                            Renews {{ $site->domain_expires_at->format('M j, Y') }}
                        @else
                            We check this daily for you
                        @endif
                    </p>
                </div>
            </div>

            {{-- Week at a glance — simple, not technical --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-6 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Your week at a glance</h2>
                <p class="text-sm text-gray-500 mt-1">Green means your site was up that day. We will email you if anything changes.</p>

                <div class="mt-5 grid grid-cols-7 gap-2 sm:gap-3">
                    @foreach ($uptimeDayGroups as $day)
                        @php
                            $dayStatus = ! $day['has_data'] ? 'none' : (($day['pct'] ?? 0) >= 99 ? 'good' : (($day['pct'] ?? 0) >= 90 ? 'warn' : 'bad'));
                        @endphp
                        <div class="text-center">
                            <div class="mx-auto h-12 sm:h-14 w-full max-w-[3rem] rounded-xl flex items-center justify-center
                                {{ $dayStatus === 'good' ? 'bg-emerald-100' : ($dayStatus === 'warn' ? 'bg-amber-100' : ($dayStatus === 'bad' ? 'bg-red-100' : 'bg-gray-100')) }}">
                                @if ($dayStatus === 'good')
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif ($dayStatus === 'bad')
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @elseif ($dayStatus === 'warn')
                                    <span class="text-amber-600 font-bold text-sm">!</span>
                                @else
                                    <span class="text-gray-300 text-lg">·</span>
                                @endif
                            </div>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-2 font-medium">{{ $day['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($allClear && ! $hasIncidents)
                    <p class="mt-5 text-sm text-emerald-800 bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-center">
                        Everything looks good. No issues to worry about right now.
                    </p>
                @endif
            </div>

            {{-- Issues — only prominent when something happened --}}
            @if ($hasIncidents)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mb-6 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-900">Recent issues</h2>
                        <p class="text-sm text-gray-500 mt-0.5">What happened and when — we handled the monitoring, you stay informed.</p>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach ($uptimeIncidents->take(5) as $event)
                            @php $isDown = str_contains(strtolower($event->title), 'offline') || str_contains(strtolower($event->title), 'down'); @endphp
                            <li class="px-5 py-4 text-sm flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isDown ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-600' }}">
                                    @if ($isDown)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $isDown ? 'Site was unreachable' : 'Site came back online' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $event->created_at->diffForHumans() }} · {{ \App\Support\ClientTimezone::formatWithAbbr($portalClient, $event->created_at, 'M j, g:i A') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="mb-6 rounded-2xl border border-gray-100 bg-gray-50/80 px-5 py-4 text-center">
                    <p class="text-sm text-gray-600">No outages recorded. Your visitors have been able to reach your site.</p>
                </div>
            @endif

            {{-- Preferences — tucked away, not the main story --}}
            <details class="group bg-white rounded-2xl border border-gray-200 shadow-sm mb-2">
                <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 text-sm font-semibold text-gray-700 hover:text-gray-900 select-none">
                    <span>Monitoring preferences</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-5 pb-5 pt-0 border-t border-gray-100">
                    <p class="text-xs text-gray-500 mt-4 mb-4">Optional — defaults work well for most businesses. Times shown in {{ $clientTimezoneLabel ?: 'your timezone' }} · <a href="{{ route('portal.billing') }}?tab=profile" class="text-brand hover:underline">change</a></p>
                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">How often we check</label>
                            <select wire:model="monitorInterval" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white min-w-[8rem] focus:ring-2 focus:ring-brand/20 focus:border-brand">
                                @foreach ($allowedIntervals as $mins)
                                    <option value="{{ $mins }}">{{ \App\Support\MonitorSettings::intervalLabel($mins) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Check from region</label>
                            <select wire:model="monitorRegion" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white min-w-[8rem] focus:ring-2 focus:ring-brand/20 focus:border-brand">
                                @foreach ($allowedRegions as $region)
                                    <option value="{{ $region }}">{{ \App\Support\MonitorSettings::regionLabel($region) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" wire:click="saveMonitorSettings"
                            class="text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2 rounded-lg transition-colors"
                            wire:loading.attr="disabled">
                            Save
                        </button>
                        @if ($monitorSettingsSaved)
                            <span class="text-sm text-emerald-600 font-medium">Saved</span>
                        @endif
                        @if (! $site->monitoring_paused)
                            <button type="button" wire:click="toggleMonitoringPause" wire:confirm="Pause monitoring? You will not receive down alerts until you resume."
                                class="text-sm text-gray-500 hover:text-amber-700 ml-auto">
                                Pause monitoring
                            </button>
                        @endif
                    </div>
                </div>
            </details>
        @endif
    @endif

    {{-- Activity --}}
    @if ($tab === 'activity')
        <p class="text-sm text-gray-500 mb-4">Audit log for this site — your actions, team updates, backups, reports, and alerts.</p>
        <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm">
            <ul class="divide-y divide-gray-100">
                @forelse ($recentEvents as $event)
                    <li class="px-5 py-4 text-sm flex items-start gap-3">
                        @if ($event->type === 'client_action')
                            <span class="mt-0.5 flex-shrink-0 text-[10px] font-semibold uppercase tracking-wide text-violet-700 bg-violet-50 border border-violet-100 px-2 py-0.5 rounded">You</span>
                        @else
                            <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 bg-gray-300"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-800">{{ $event->title }}</p>
                            @if ($event->message)
                                <p class="text-gray-600 mt-0.5">{{ $event->message }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">{{ \App\Support\ClientTimezone::formatWithAbbr($portalClient, $event->created_at) }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-gray-500">No activity yet for this site.</li>
                @endforelse
            </ul>
        </div>
    @endif

    {{-- Backups --}}
    @if ($tab === 'backups')
        @if ($site->hasPaidSubscription())
            <p class="text-sm text-gray-500 mb-4">
                Backups run on your {{ $site->plan?->name ?? 'plan' }} schedule.
                @if ($latestBackup?->completed_at)
                    Last completed {{ $latestBackup->completed_at->diffForHumans() }}.
                @else
                    Your first backup will appear here after the next scheduled run.
                @endif
            </p>
        @endif
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden mb-4">
            @if ($backups->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-gray-500">
                    @if ($site->hasPaidSubscription() && $site->hasAgentConnected())
                        No backup copies yet. They're created automatically — check back after your next scheduled run.
                    @elseif (! $site->hasPaidSubscription())
                        Backups start after you complete checkout on the Plan tab.
                    @else
                        Connect the plugin first so we can back up your site.
                    @endif
                </p>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Size</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($backups as $backup)
                            <tr>
                                <td class="px-5 py-3 text-gray-700">{{ $backup->completed_at?->format('M j, Y H:i') ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $backup->size_bytes ? round($backup->size_bytes / 1048576) . ' MB' : '—' }}</td>
                                <td class="px-5 py-3"><span class="text-xs font-medium text-emerald-700">Verified</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <p class="text-sm text-gray-500">Need a restore? <a href="{{ route('portal.tickets') }}" class="text-brand font-medium hover:underline">Contact support</a> and we'll handle it.</p>
    @endif

    {{-- Reports --}}
    @if ($tab === 'reports')
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden">
            @if ($reports->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-gray-500">Monthly reports will appear here.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($reports as $report)
                        <li class="px-5 py-4 flex items-center justify-between gap-4 text-sm">
                            <div>
                                <p class="font-medium text-gray-800">{{ ucfirst($report->type ?? 'Monthly') }} report</p>
                                <p class="text-xs text-gray-400">{{ $report->period }} · {{ ucfirst($report->status) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Connection --}}
    @if ($tab === 'connection')
        <livewire:portal.connection-guide :site-id="$site->id" :compact="true" :key="'conn-tab-'.$site->id.'-'.($site->hasAgentConnected() ? 'on' : 'off')" />

        <div class="mt-4 bg-white rounded-[10px] border border-gray-200 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Hosting login details</h2>
                        <p class="text-sm text-gray-500 mt-1">Optional — share cPanel, SSH, or FTP access so our team can help when needed.</p>
                    </div>
                    <button wire:click="openCredentials" class="text-sm font-semibold text-brand border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-50">
                        {{ $site->hosting_credentials ? 'Update' : 'Add' }} details
                    </button>
                </div>
                @if ($credentialsSaved)
                    <p class="mt-3 text-sm text-emerald-700">Details saved securely.</p>
                @endif
            </div>

        @if ($showCredentialsModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40" wire:click="$set('showCredentialsModal', false)"></div>
                    <div class="relative bg-white rounded-[10px] shadow-xl w-full max-w-lg p-6 z-10 max-h-[90vh] overflow-y-auto">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Hosting login details</h2>
                        <form wire:submit.prevent="saveCredentials" class="space-y-4 text-sm">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Hosting provider</label>
                                <input type="text" wire:model="credHostingProvider" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="SiteGround, WP Engine, etc." />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Panel URL</label>
                                    <input type="url" wire:model="credCpanelUrl" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Username</label>
                                    <input type="text" wire:model="credCpanelUser" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Password</label>
                                    <input type="password" wire:model="credCpanelPassword" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Notes</label>
                                <textarea wire:model="credNotes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 resize-none"></textarea>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-brand text-white font-semibold py-2.5 rounded-lg">Save</button>
                                <button type="button" wire:click="$set('showCredentialsModal', false)" class="px-4 py-2.5 border border-gray-300 rounded-lg">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Plan --}}
    @if ($tab === 'plan')
        <div class="max-w-2xl">
            @if (! $site->hasPaidSubscription())
                <p class="text-sm text-gray-600 mb-4">Pick a plan for this site. You'll pay on the next screen{{ $stripeTestMode ? ' (Stripe test mode — use test card 4242 4242 4242 4242)' : '' }}.</p>

                <div class="grid gap-3 sm:grid-cols-3 mb-6">
                    @foreach ($plans as $plan)
                        <button type="button" wire:click="selectPlan('{{ $plan->slug }}')"
                            class="text-left p-4 rounded-[10px] border-2 transition-colors
                                {{ $selectedPlanSlug === $plan->slug ? 'border-brand bg-brand-light' : 'border-gray-200 hover:border-emerald-300 bg-white' }}">
                            @if ($plan->isRecommended())
                                <span class="text-[10px] font-bold uppercase text-emerald-700">Popular</span>
                            @endif
                            <p class="font-semibold text-gray-900">{{ $plan->name }}</p>
                            <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($plan->price_monthly, 0) }}<span class="text-xs font-normal text-gray-500">/mo</span></p>
                            <ul class="mt-3 space-y-1">
                                @foreach (\App\Support\PlanCatalog::bullets($plan) as $bullet)
                                    <li class="text-xs text-gray-600 leading-snug">{{ $bullet }}</li>
                                @endforeach
                            </ul>
                        </button>
                    @endforeach
                </div>

                <button wire:click="resumeCheckout" wire:loading.attr="disabled"
                    class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg">
                    Continue to payment →
                </button>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <button wire:click="removeSite" wire:confirm="Remove this site? This cannot be undone."
                        class="text-sm text-gray-500 hover:text-red-600">
                        Remove this site
                    </button>
                </div>
            @elseif ($site->plan)
                <div class="space-y-6 max-w-3xl">
                    @if (session('success'))
                        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                    @endif

                    <div class="bg-white rounded-[10px] border-2 border-brand p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand mb-1">Your current plan</p>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $site->plan->name }}</h2>
                        <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($site->plan->price_monthly, 0) }}<span class="text-sm font-normal text-gray-500">/month</span></p>
                        <p class="text-sm text-gray-500 mt-2">{{ \App\Support\PlanCatalog::tagline($site->plan) }}</p>

                        <ul class="mt-4 space-y-3">
                            @foreach (\App\Support\PlanCatalog::included($site->plan) as $feature)
                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>{{ $feature['name'] }}</strong>@if ($feature['desc'])<span class="text-gray-500"> — {{ $feature['desc'] }}</span>@endif</span>
                                </li>
                            @endforeach
                        </ul>

                        @if (\App\Support\PlanCatalog::notIncluded($site->plan))
                            <div class="mt-5 pt-4 border-t border-gray-100">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Not included on this plan</p>
                                <ul class="space-y-2">
                                    @foreach (\App\Support\PlanCatalog::notIncluded($site->plan) as $item)
                                        <li class="text-sm text-gray-500">– <strong class="font-medium text-gray-600">{{ $item['name'] }}</strong>@if ($item['desc']) — {{ $item['desc'] }}@endif</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($site->subscription)
                            <p class="text-sm text-gray-500 mt-4 pt-4 border-t border-gray-100">
                                Status: <strong>{{ $site->subscription->billingStatusLabel() }}</strong>
                                @if ($site->subscription->nextBillingDate())
                                    · Next billing {{ $site->subscription->nextBillingDate()->format('M j, Y') }}
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($plans->contains(fn ($p) => \App\Support\PlanCatalog::isUpgrade($site->plan, $p)))
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Upgrade this site</h3>
                            <p class="text-sm text-gray-500 mb-4">Your card on file is charged the prorated difference today. Receipt appears under Account → Billing.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($plans as $upgradePlan)
                                    @if (\App\Support\PlanCatalog::isUpgrade($site->plan, $upgradePlan))
                                        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                                            <p class="font-bold text-gray-900">{{ $upgradePlan->name }}</p>
                                            <p class="text-xl font-bold text-brand mt-0.5">${{ number_format($upgradePlan->price_monthly, 0) }}<span class="text-xs font-normal text-gray-500">/mo</span></p>
                                            <p class="text-xs text-gray-500 mt-2">{{ \App\Support\PlanCatalog::bestFor($upgradePlan) }}</p>
                                            <ul class="mt-4 space-y-2">
                                                @foreach (\App\Support\PlanCatalog::upgradeGains($site->plan, $upgradePlan) as $gain)
                                                    <li class="text-sm text-gray-700 flex gap-2">
                                                        <span class="text-emerald-500 font-bold shrink-0">+</span>
                                                        <span>{{ $gain }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <button type="button"
                                                wire:click="openPlanChangeModal('{{ $upgradePlan->slug }}')"
                                                wire:loading.attr="disabled"
                                                class="mt-5 w-full text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2.5 rounded-lg">
                                                Upgrade to {{ $upgradePlan->name }}
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($plans->contains(fn ($p) => \App\Support\PlanCatalog::isDowngrade($site->plan, $p)))
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-1">Switch to a lower plan</h3>
                            <p class="text-sm text-gray-500 mb-4">Changes apply immediately. Unused time is credited toward your next bill — no charge today.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($plans as $lowerPlan)
                                    @if (\App\Support\PlanCatalog::isDowngrade($site->plan, $lowerPlan))
                                        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                                            <p class="font-bold text-gray-900">{{ $lowerPlan->name }}</p>
                                            <p class="text-xl font-bold text-gray-700 mt-0.5">${{ number_format($lowerPlan->price_monthly, 0) }}<span class="text-xs font-normal text-gray-500">/mo</span></p>
                                            <button type="button"
                                                wire:click="openPlanChangeModal('{{ $lowerPlan->slug }}')"
                                                wire:loading.attr="disabled"
                                                class="mt-5 w-full text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2.5 rounded-lg">
                                                Switch to {{ $lowerPlan->name }}
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-gray-400">
                        Full comparison in <a href="{{ route('portal.billing', ['tab' => 'plan']) }}" class="text-brand hover:underline">Account → Plan</a>.
                        Payment method? <a href="{{ route('portal.billing', ['tab' => 'billing']) }}" class="text-brand hover:underline">Billing</a>.
                    </p>
                </div>
            @else
                <p class="text-sm text-gray-500">No plan on file. <a href="{{ route('portal.tickets') }}" class="text-brand hover:underline">Contact support</a>.</p>
            @endif
        </div>
    @endif

    <x-portal.plan-change-modal :show="$showPlanChangeModal" :modal="$planChangeModal" />
</div>
