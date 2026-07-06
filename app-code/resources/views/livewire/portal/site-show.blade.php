<div>
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
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

    {{-- Sub-nav tabs --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200 overflow-x-auto">
        @foreach ([
            'overview' => 'Overview',
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
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Uptime (30d)</p>
                <p class="text-2xl font-bold text-gray-900">{{ $site->uptime_30d !== null ? number_format($site->uptime_30d, 1) . '%' : '—' }}</p>
            </div>
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">SSL</p>
                @php $ssl = $site->sslExpiresInDays(); @endphp
                <p class="text-2xl font-bold {{ $ssl !== null && $ssl < 30 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $ssl !== null ? ($ssl < 0 ? 'Expired' : $ssl . ' days') : '—' }}
                </p>
            </div>
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Domain</p>
                @php $dom = $site->domainExpiresInDays(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $dom !== null ? ($dom < 0 ? 'Expired' : $dom . ' days') : '—' }}</p>
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
                @forelse ($recentEvents as $event)
                    <li class="px-5 py-3 text-sm">
                        <p class="font-medium text-gray-800">{{ $event->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $event->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-500">Activity will appear once your site is connected.</li>
                @endforelse
            </ul>
        </div>
    @endif

    {{-- Activity --}}
    @if ($tab === 'activity')
        <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm">
            <ul class="divide-y divide-gray-100">
                @forelse ($recentEvents as $event)
                    <li class="px-5 py-4 text-sm">
                        <p class="font-medium text-gray-800">{{ $event->title }}</p>
                        @if ($event->message)
                            <p class="text-gray-600 mt-0.5">{{ $event->message }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">{{ $event->created_at->format('M j, Y g:i A') }}</p>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-gray-500">No activity yet for this site.</li>
                @endforelse
            </ul>
        </div>
    @endif

    {{-- Backups --}}
    @if ($tab === 'backups')
        <div class="bg-white rounded-[10px] border border-gray-200 overflow-hidden mb-4">
            @if ($backups->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-gray-500">Backup copies will appear here once your site is connected.</p>
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
        <livewire:portal.connection-guide :site-id="$site->id" :compact="true" :key="'conn-tab-'.$site->id" />

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
        <div class="bg-white rounded-[10px] border border-gray-200 p-6 shadow-sm max-w-lg">
            @if ($site->plan)
                <h2 class="text-lg font-semibold text-gray-900">{{ $site->plan->name }}</h2>
                <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($site->plan->price_monthly, 0) }}<span class="text-sm font-normal text-gray-500">/month per site</span></p>

                @if ($site->subscription)
                    <p class="text-sm text-gray-500 mt-4">
                        Status: <strong>{{ $site->subscription->billingStatusLabel() }}</strong>
                        @if ($site->subscription->nextBillingDate())
                            · Next billing {{ $site->subscription->nextBillingDate()->format('M j, Y') }}
                        @endif
                    </p>
                @endif

                @if ($ps === 'checkout')
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-sm text-amber-800 mb-3">Complete payment to activate protection for this site.</p>
                        <button wire:click="resumeCheckout" wire:loading.attr="disabled"
                            class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg">
                            Complete payment →
                        </button>
                    </div>
                @else
                    <p class="text-xs text-gray-400 mt-6">Change plan or payment method in <a href="{{ route('portal.billing') }}" class="text-brand hover:underline">Billing</a>.</p>
                @endif
            @else
                <p class="text-sm text-gray-500">No plan selected yet.</p>
                @if ($ps === 'checkout')
                    <button wire:click="resumeCheckout" class="mt-4 bg-brand text-white text-sm font-semibold px-4 py-2.5 rounded-lg">Choose plan & pay →</button>
                @endif
            @endif
        </div>
    @endif
</div>
