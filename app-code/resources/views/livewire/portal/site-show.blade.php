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
        @php
            $tabs = [
                'overview' => 'Overview',
                'monitoring' => 'Monitoring',
                'activity' => 'Activity',
                'backups' => 'Backups',
            ];
            if ($showSecurityTab ?? false) {
                $tabs['security'] = 'Security & links';
            }
            $tabs += [
                'reports' => 'Reports',
                'connection' => 'Connection',
                'plan' => 'Plan',
            ];
        @endphp
        @foreach ($tabs as $key => $label)
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
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if ($event->type === 'client_action' && ! empty($event->metadata['action']))
                                {{ \App\Services\ClientActivityService::actionLabel((string) $event->metadata['action']) }} ·
                            @elseif ($event->type !== 'client_action')
                                {{ $event->typeLabel() }} ·
                            @endif
                            {{ $event->created_at->diffForHumans() }}
                        </p>
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
            @if ($site->monitoring_paused)
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-amber-900">
                        <strong>Monitoring paused.</strong> Uptime alerts are off
                        @if ($site->monitoring_paused_at)
                            since {{ \App\Support\ClientTimezone::formatWithAbbr($portalClient, $site->monitoring_paused_at, 'M j, Y') }}.
                        @endif
                    </div>
                    <button wire:click="toggleMonitoringPause" class="text-sm font-semibold text-amber-900 bg-white border border-amber-300 px-4 py-2 rounded-lg hover:bg-amber-100">
                        Resume
                    </button>
                </div>
            @endif

            <div class="mb-5 rounded-xl border border-gray-200 bg-white px-4 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <div class="flex items-center gap-2 text-gray-700">
                    <span class="font-medium text-gray-900">Monitor</span>
                    <select wire:model="monitorInterval" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                        @foreach ($allowedIntervals as $mins)
                            <option value="{{ $mins }}">{{ \App\Support\MonitorSettings::intervalLabel($mins) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 text-gray-700">
                    <span class="font-medium text-gray-900">Region</span>
                    <select wire:model="monitorRegion" class="text-sm border border-gray-300 rounded-lg px-2 py-1.5 bg-white">
                        @foreach ($allowedRegions as $region)
                            <option value="{{ $region }}">{{ \App\Support\MonitorSettings::regionLabel($region) }}</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="saveMonitorSettings" class="text-sm font-semibold text-brand hover:underline">
                    Save
                </button>
                <span class="text-xs text-gray-400 hidden sm:inline">{{ \App\Support\MonitorSettings::planIntervalHint($site) }} SSL &amp; domain checked daily.</span>
                @if (! $site->monitoring_paused)
                    <button wire:click="toggleMonitoringPause" wire:confirm="Pause uptime monitoring and down alerts for this site?"
                        class="text-xs text-gray-500 hover:text-amber-700 sm:ml-auto">
                        Pause monitoring
                    </button>
                @endif
                @if ($clientTimezoneLabel)
                    <p class="text-xs text-gray-400 w-full">Times in {{ $clientTimezoneLabel }} · <a href="{{ route('portal.billing') }}?tab=profile" class="text-brand hover:underline">change</a></p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Currently up for</p>
                    <p class="text-lg font-bold {{ ($lastProbe && ! $lastProbe->is_up) || $site->status === \App\Enums\SiteStatus::DOWN ? 'text-red-600' : 'text-emerald-700' }}">
                        @if ($site->monitoring_paused)
                            Paused
                        @elseif ($lastProbe && ! $lastProbe->is_up)
                            Down
                        @elseif ($lastProbe && $lastProbe->is_up)
                            {{ $lastProbe->checked_at->diffForHumans() }}
                        @else
                            Awaiting first check
                        @endif
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Every {{ \App\Support\MonitorSettings::intervalLabel((int) $site->monitor_interval_minutes) }} · Agent {{ $site->last_seen_at?->diffForHumans() ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Uptime (30d)</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $site->uptime_30d !== null ? number_format((float) $site->uptime_30d, 2) . '%' : '—' }}</p>
                </div>
                <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">SSL expires</p>
                    @php $sslM = $site->sslExpiresInDays(); @endphp
                    <p class="text-lg font-bold {{ $sslM !== null && $sslM <= 30 ? 'text-amber-600' : 'text-gray-900' }}">
                        {{ $site->ssl_expires_at?->format('M j, Y') ?? ($site->metricSyncing('ssl') ? 'Syncing…' : '—') }}
                    </p>
                    @if ($sslM !== null)
                        <p class="text-xs text-gray-400 mt-1">{{ $sslM < 0 ? 'Expired' : "in {$sslM} days" }}</p>
                    @endif
                </div>
                <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Domain expires</p>
                    @php $domM = $site->domainExpiresInDays(); @endphp
                    <p class="text-lg font-bold {{ $domM !== null && $domM <= 60 ? 'text-amber-600' : 'text-gray-900' }}">
                        {{ $site->domain_expires_at?->format('M j, Y') ?? ($site->metricSyncing('domain') ? 'Syncing…' : '—') }}
                    </p>
                    @if ($domM !== null)
                        <p class="text-xs text-gray-400 mt-1">{{ $domM < 0 ? 'Expired' : "in {$domM} days" }}</p>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-[10px] border border-gray-200 p-5 mb-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Uptime rate — last 7 days</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Daily checks at your {{ \App\Support\MonitorSettings::intervalLabel((int) $site->monitor_interval_minutes) }} schedule</p>
                    </div>
                    @if ($periodUptimePct !== null)
                        <span class="inline-flex items-center text-sm font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full">
                            {{ number_format($periodUptimePct, 2) }}%
                        </span>
                    @endif
                </div>
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-2 pt-3 pb-2">
                <div class="flex items-end gap-1.5 h-28 w-full">
                    @foreach ($uptimeDailyBars as $day)
                        <div class="flex-1 flex flex-col justify-end h-full min-w-0">
                            <div class="flex-1 flex items-end w-full min-h-[2rem]">
                                @if ($day['has_data'])
                                    <div
                                        class="w-full rounded-t-sm {{ $day['color'] }}"
                                        style="height: {{ max(18, min(100, (int) round((float) ($day['pct'] ?? 0)))) }}%"
                                        title="{{ number_format((float) $day['pct'], 1) }}% uptime"
                                    ></div>
                                @else
                                    <div class="w-full h-1 rounded-full bg-gray-200" title="No checks this day"></div>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-500 mt-2 text-center truncate w-full">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                </div>
                @if ($uptimeProbes->isEmpty())
                    <p class="text-xs text-gray-500 mt-4 text-center">Collecting uptime data — chart fills in after the first checks.</p>
                @endif
            </div>

            <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Incident timeline</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Downtime detected by our uptime monitors — not plugin heartbeats.</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($uptimeIncidents as $event)
                        <li class="px-5 py-3 text-sm flex items-start gap-3">
                            <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 {{ str_contains(strtolower($event->title), 'offline') || str_contains(strtolower($event->title), 'down') ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                            <div>
                                <p class="font-medium text-gray-800">{{ $event->title }}</p>
                                @if ($event->message)
                                    <p class="text-gray-600 text-xs mt-0.5">{{ $event->message }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">{{ \App\Support\ClientTimezone::formatWithAbbr($portalClient, $event->created_at, 'M j, Y g:i A') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-gray-500">No downtime incidents recorded. We'll alert you immediately if anything changes.</li>
                    @endforelse
                </ul>
            </div>
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
                            @if ($event->type === 'client_action' && ! empty($event->metadata['action']))
                                <p class="text-xs text-violet-600 mt-0.5">{{ \App\Services\ClientActivityService::actionLabel((string) $event->metadata['action']) }}</p>
                            @elseif ($event->type !== 'client_action')
                                <p class="text-xs text-gray-500 mt-0.5">{{ $event->typeLabel() }}</p>
                            @endif
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
                {{ \App\Support\PlanFeatures::for($site->plan)->portalRetentionCopy() }}
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

    {{-- Security & links (Guard/Shield) --}}
    @if ($tab === 'security')
        @if (! $site->hasPaidSubscription())
            <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Security scans and link audits are included on Guard and Shield plans after checkout.
                <button wire:click="setTab('plan')" class="ml-2 font-semibold underline hover:no-underline">View plans →</button>
            </div>
        @else
            <p class="text-sm text-gray-500 mb-4">
                @if ($planFeatures->canMalwareScan())
                    Malware scans run weekly.
                @endif
                @if ($planFeatures->canMalwareScan() && $planFeatures->canBrokenLinkAudit())
                    Broken link audits run monthly.
                @elseif ($planFeatures->canBrokenLinkAudit())
                    Broken link audits run monthly.
                @endif
                Detection and reporting only — open a ticket if you'd like us to fix anything.
            </p>

            <div class="grid gap-4 md:grid-cols-2 mb-6">
                @if ($planFeatures->canMalwareScan())
                    <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Malware scan</p>
                            @if ($lastMalwareScan)
                                @php
                                    $msSeverity = $lastMalwareScan->severity instanceof \App\Enums\EventSeverity
                                        ? $lastMalwareScan->severity->value
                                        : (string) $lastMalwareScan->severity;
                                @endphp
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                    {{ in_array($msSeverity, ['critical', 'warning'], true) ? 'bg-amber-100 text-amber-800' : ($msSeverity === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $lastMalwareScan->type === 'malware_scan_alert' ? 'Issues found' : ($lastMalwareScan->type === 'malware_scan_failed' ? 'Scan failed' : 'Clean') }}
                                </span>
                            @endif
                        </div>
                        @if ($lastMalwareScan)
                            <p class="text-sm font-semibold text-gray-900">{{ $lastMalwareScan->title }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $lastMalwareScan->message }}</p>
                            <p class="text-xs text-gray-400 mt-3">Last run {{ $lastMalwareScan->created_at->diffForHumans() }}</p>
                        @else
                            <p class="text-sm text-gray-600">No scan results yet. Your first weekly scan will appear here.</p>
                        @endif
                    </div>
                @endif

                @if ($planFeatures->canBrokenLinkAudit())
                    <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Broken links</p>
                            @if ($lastLinkAudit)
                                @php
                                    $brokenCount = (int) ($lastLinkAudit->metadata['broken_count'] ?? 0);
                                @endphp
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $brokenCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $brokenCount > 0 ? $brokenCount . ' broken' : 'All clear' }}
                                </span>
                            @endif
                        </div>
                        @if ($lastLinkAudit)
                            <p class="text-sm font-semibold text-gray-900">{{ $lastLinkAudit->title }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $lastLinkAudit->message }}</p>
                            @if (! empty($lastLinkAudit->metadata['total_checked']))
                                <p class="text-xs text-gray-500 mt-2">{{ $lastLinkAudit->metadata['total_checked'] }} links checked</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">Last run {{ $lastLinkAudit->created_at->diffForHumans() }}</p>
                        @else
                            <p class="text-sm text-gray-600">No audit results yet. Your first monthly audit will appear here.</p>
                        @endif
                    </div>
                @endif
            </div>

            @if (($lastMalwareScan && $lastMalwareScan->type === 'malware_scan_alert') || ($lastLinkAudit && (int) ($lastLinkAudit->metadata['broken_count'] ?? 0) > 0))
                <div class="rounded-[10px] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    Issues were detected on your site.
                    <a href="{{ route('portal.tickets') }}" class="ml-1 font-semibold text-amber-800 underline hover:no-underline">Open a support ticket</a>
                    and we'll help review or fix them.
                </div>
            @endif
        @endif
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
