<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">My Websites</h1>
        @if (! $showWizard && $hasSubscription)
            <button
                wire:click="openWizard"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add website
            </button>
        @endif
    </div>

    {{-- No active subscription — show plan CTA --}}
    @if (! $hasSubscription)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100">
                <svg class="h-7 w-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">No active plan</h2>
            <p class="text-sm text-gray-600 mb-6">You need an active ReviveGuard plan to add and monitor websites.<br>Choose a plan to get started — takes less than 2 minutes.</p>
            <a href="{{ route('portal.account') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                View plans &amp; billing
            </a>
        </div>

    @else

    @if ($showWizard)
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6 mb-6">
            <livewire:portal.site-wizard :key="'wizard-' . now()->timestamp" />
        </div>
    @endif

    @if ($sites->isEmpty() && ! $showWizard)
        <div class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                </svg>
            </div>
            <p class="text-gray-500 text-sm mb-4">No websites added yet.</p>
            <button
                wire:click="openWizard"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
            >
                Add your first website
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($sites as $site)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 text-sm truncate">{{ $site->label ?? $site->url }}</p>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $site->url }}</p>
                        </div>
                        <span class="ml-2 flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                            @if (isset($site->status) && $site->status->value === 'DOWN')
                                bg-red-100 text-red-700
                            @elseif ($site->status?->value === 'UP')
                                bg-green-100 text-green-700
                            @else
                                bg-gray-100 text-gray-600
                            @endif
                        ">
                            <span class="w-1.5 h-1.5 rounded-full
                                @if (isset($site->status) && $site->status->value === 'DOWN') bg-red-500
                                @elseif ($site->status?->value === 'UP') bg-green-500
                                @else bg-gray-400
                                @endif
                            "></span>
                            {{ $site->status->value ?? 'Pending' }}
                        </span>
                    </div>

                    <div class="text-xs text-gray-500 space-y-1">
                        @if ($site->last_heartbeat_at)
                            <p>Last heartbeat: {{ $site->last_heartbeat_at->diffForHumans() }}</p>
                        @else
                            <p class="text-yellow-600">Waiting for first heartbeat...</p>
                        @endif

                        @if ($site->ssl_expires_at)
                            <p>SSL expires: {{ $site->ssl_expires_at->format('M j, Y') }}</p>
                        @endif

                        @if ($site->domain_expires_at)
                            <p>Domain expires: {{ $site->domain_expires_at->format('M j, Y') }}</p>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex gap-2">
                        <a
                            href="{{ route('portal.events') }}"
                            class="text-xs text-emerald-700 hover:underline"
                        >View events</a>
                        <span class="text-gray-200">|</span>
                        <a
                            href="{{ route('portal.reports') }}"
                            class="text-xs text-emerald-700 hover:underline"
                        >Reports</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @endif {{-- hasSubscription --}}
</div>
