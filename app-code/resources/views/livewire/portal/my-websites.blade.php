<div>
    @if (session('error'))
        <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Sites</h1>
            @if ($sites->isNotEmpty())
                <p class="text-sm text-gray-500 mt-1">
                    {{ $summary['total'] }} {{ Str::plural('site', $summary['total']) }}
                    · {{ $summary['protected'] }} protected
                    · {{ $summary['setup'] }} need setup
                    @if ($summary['issues'] > 0)
                        · {{ $summary['issues'] }} need attention
                    @endif
                </p>
            @endif
        </div>
        <button wire:click="openWizard"
                class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-4 py-2.5 rounded-[10px] transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add site
        </button>
    </div>

    @if ($sites->isEmpty())
        <div class="bg-white rounded-[10px] border border-gray-200 p-12 text-center mt-6">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">No sites yet</h2>
            <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">Add your first website and our team will handle monitoring, backups, and protection.</p>
            <button wire:click="openWizard"
                class="inline-flex items-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-5 py-2.5 rounded-[10px] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add your first site
            </button>
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3 mb-4 mt-4">
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search sites…"
                    class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-[10px] bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm border border-gray-200 rounded-[10px] px-3 py-2 bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-gray-600">
                <option value="">All status</option>
                <option value="protected">Protected</option>
                <option value="setup">Setup needed</option>
                <option value="issue">We're on it</option>
                <option value="warning">Needs attention</option>
                <option value="checkout">Complete payment</option>
            </select>
        </div>

        <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/80 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="hidden md:table-cell px-4 py-3">Uptime</th>
                            <th class="hidden lg:table-cell px-4 py-3">SSL</th>
                            <th class="hidden lg:table-cell px-4 py-3">Backup</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sites as $site)
                            @php
                                $ps      = $site->portalStatusKey();
                                $sslDays = $site->sslExpiresInDays();
                            @endphp
                            <tr class="group hover:bg-gray-50/60 cursor-pointer transition-colors"
                                onclick="window.location='{{ route('portal.sites.show', $site) }}'">
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-gray-900 truncate max-w-[220px]">{{ $site->displayName() }}</p>
                                    <p class="text-xs text-gray-400 truncate max-w-[220px]">{{ parse_url($site->url, PHP_URL_HOST) ?: $site->url }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap
                                        {{ $ps === 'issue' ? 'bg-red-100 text-red-700' : ($ps === 'warning' ? 'bg-amber-100 text-amber-700' : ($ps === 'protected' ? 'bg-emerald-100 text-emerald-800' : ($ps === 'checkout' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600'))) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $ps === 'issue' ? 'bg-red-500' : ($ps === 'warning' ? 'bg-amber-500' : ($ps === 'protected' ? 'bg-emerald-500' : 'bg-gray-400')) }}"></span>
                                        {{ $site->portalStatusLabel() }}
                                    </span>
                                </td>
                                <td class="hidden md:table-cell px-4 py-3.5 text-gray-700">
                                    {{ $site->uptime_30d !== null ? number_format((float) $site->uptime_30d, 1) . '%' : '—' }}
                                </td>
                                <td class="hidden lg:table-cell px-4 py-3.5 {{ $sslDays !== null && $sslDays <= 30 ? 'text-amber-600 font-medium' : 'text-gray-700' }}">
                                    @if ($sslDays !== null)
                                        {{ $sslDays < 0 ? 'Expired' : $sslDays . ' days' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell px-4 py-3.5 text-gray-700">
                                    {{ '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-gray-700">
                                    {{ $site->plan?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-right" onclick="event.stopPropagation()">
                                    @if ($ps === 'checkout')
                                        <button wire:click="resumeCheckout('{{ $site->id }}')" wire:loading.attr="disabled"
                                            class="text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 px-2.5 py-1.5 rounded-lg transition-colors">
                                            Pay →
                                        </button>
                                    @else
                                        <span class="text-gray-300 group-hover:text-brand">→</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
