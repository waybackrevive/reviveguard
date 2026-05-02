<div>
    {{-- Flash error --}}
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">My Websites</h1>
        @if (! $showWizard)
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
                @php
                    $statusVal = $site->status->value;
                    $isPending  = $statusVal === 'pending';
                @endphp
                <div class="bg-white rounded-2xl border {{ $isPending ? 'border-amber-200' : 'border-gray-200' }} p-5 flex flex-col">

                    {{-- Pending banner --}}
                    @if ($isPending)
                        <div class="mb-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                            <strong>Awaiting payment.</strong> Complete checkout to activate monitoring.
                        </div>
                    @endif

                    <div class="flex items-start justify-between mb-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 text-sm truncate">{{ $site->name }}</p>
                            <p class="text-xs text-gray-400 truncate mt-0.5">{{ $site->url }}</p>
                            @if ($site->plan)
                                <p class="text-xs text-emerald-700 mt-0.5 font-medium">{{ $site->plan->name }} — ${{ number_format($site->plan->price_monthly, 0) }}/mo</p>
                            @endif
                        </div>
                        <span class="ml-2 flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                            @if ($statusVal === 'down')     bg-red-100 text-red-700
                            @elseif ($statusVal === 'warning') bg-amber-100 text-amber-700
                            @elseif ($statusVal === 'active')  bg-green-100 text-green-700
                            @else                              bg-gray-100 text-gray-600
                            @endif
                        ">
                            <span class="w-1.5 h-1.5 rounded-full
                                @if ($statusVal === 'down')     bg-red-500
                                @elseif ($statusVal === 'warning') bg-amber-500
                                @elseif ($statusVal === 'active')  bg-green-500
                                @else                              bg-gray-400
                                @endif
                            "></span>
                            {{ $site->status->label() }}
                        </span>
                    </div>

                    <div class="text-xs text-gray-500 space-y-1 flex-1">
                        @if (! $isPending)
                            @if ($site->last_seen_at)
                                <p>Last heartbeat: {{ $site->last_seen_at->diffForHumans() }}</p>
                            @else
                                <p class="text-yellow-600">Waiting for first heartbeat...</p>
                            @endif
                            @if ($site->ssl_expires_at)
                                <p>SSL expires: {{ $site->ssl_expires_at->format('M j, Y') }}</p>
                            @endif
                            @if ($site->domain_expires_at)
                                <p>Domain expires: {{ $site->domain_expires_at->format('M j, Y') }}</p>
                            @endif
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        @if ($isPending)
                            {{-- Pending: complete checkout or delete --}}
                            <div class="flex items-center gap-3">
                                <button
                                    wire:click="resumeCheckout('{{ $site->id }}')"
                                    wire:loading.attr="disabled"
                                    class="flex-1 inline-flex justify-center items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                    Complete checkout
                                </button>
                                <button
                                    wire:click="deletePendingSite('{{ $site->id }}')"
                                    wire:confirm="Delete this pending site? This cannot be undone."
                                    class="text-xs text-red-500 hover:text-red-700 underline"
                                >
                                    Delete
                                </button>
                            </div>
                        @else
                            {{-- Active site: view dashboard + events/reports + credentials --}}
                            <div class="flex flex-wrap gap-2 items-center justify-between">
                                <div class="flex gap-2">
                                    <a
                                        href="{{ route('portal.dashboard', ['site_id' => $site->id]) }}"
                                        class="inline-flex items-center gap-1 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg transition-colors"
                                    >
                                        View dashboard
                                    </a>
                                    <a href="{{ route('portal.events') }}" class="text-xs text-gray-500 hover:text-emerald-700 hover:underline self-center">Events</a>
                                    <a href="{{ route('portal.reports') }}" class="text-xs text-gray-500 hover:text-emerald-700 hover:underline self-center">Reports</a>
                                </div>
                                <button
                                    wire:click="openCredentials('{{ $site->id }}')"
                                    title="Share access credentials"
                                    class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-emerald-700 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Access
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Hosting Credentials Modal ─────────────────────────────────────────────── --}}
    @if ($showCredentialsModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-full items-center justify-center p-4">
                {{-- Backdrop --}}
                <div
                    class="fixed inset-0 bg-black/40"
                    wire:click="closeCredentials"
                ></div>

                {{-- Panel --}}
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 z-10">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Access Credentials</h2>
                            <p class="text-xs text-gray-500 mt-1">Stored securely and encrypted. Only accessible to your maintenance team.</p>
                        </div>
                        <button wire:click="closeCredentials" class="text-gray-400 hover:text-gray-600 ml-4 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if ($credentialsSaved)
                        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 mb-4">
                            ✓ Credentials saved securely.
                        </div>
                    @endif

                    <form wire:submit.prevent="saveCredentials" class="space-y-5">

                        {{-- Hosting provider --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Hosting Provider</label>
                            <input type="text" wire:model="credHostingProvider" placeholder="e.g. SiteGround, Bluehost, WP Engine" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                        </div>

                        {{-- cPanel --}}
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 space-y-3">
                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Control Panel (cPanel / Plesk)</p>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Panel URL</label>
                                <input type="url" wire:model="credCpanelUrl" placeholder="https://yourdomain.com:2083" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Username</label>
                                    <input type="text" wire:model="credCpanelUser" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Password</label>
                                    <input type="password" wire:model="credCpanelPassword" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                            </div>
                        </div>

                        {{-- SSH --}}
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 space-y-3">
                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">SSH Access (optional)</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Host / IP</label>
                                    <input type="text" wire:model="credSshHost" placeholder="123.456.789.0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Username</label>
                                    <input type="text" wire:model="credSshUser" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Password</label>
                                <input type="password" wire:model="credSshPassword" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                            </div>
                        </div>

                        {{-- FTP --}}
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 space-y-3">
                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">FTP Access (optional)</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Host / IP</label>
                                    <input type="text" wire:model="credFtpHost" placeholder="ftp.yourdomain.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Username</label>
                                    <input type="text" wire:model="credFtpUser" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Password</label>
                                <input type="password" wire:model="credFtpPassword" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Additional Notes</label>
                            <textarea wire:model="credNotes" rows="2" placeholder="Any other access details, special instructions, etc." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none resize-none"></textarea>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button
                                type="submit"
                                class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors"
                            >
                                Save credentials
                            </button>
                            <button
                                type="button"
                                wire:click="closeCredentials"
                                class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

