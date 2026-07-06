<div>
    {{-- ── Step indicator ──────────────────────────────────────────────────── --}}
    <div class="flex items-center mb-8">
        @foreach ([1 => 'Domain name', 2 => 'Package options', 3 => 'Order'] as $num => $label)
            <div class="flex items-center gap-2 {{ $num > 1 ? 'flex-1' : '' }}">
                @if ($num > 1)
                    <div class="flex-1 h-px {{ $step >= $num ? 'bg-emerald-400' : 'bg-gray-200' }} mx-2"></div>
                @endif
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $step === $num ? 'bg-emerald-600 text-white ring-2 ring-emerald-200' : ($step > $num ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400') }}">
                        @if ($step > $num)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-xs font-medium {{ $step === $num ? 'text-emerald-700' : ($step > $num ? 'text-emerald-600' : 'text-gray-400') }}">
                        {{ $label }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Step 1: Domain name + access method                                   --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($step === 1)
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Add Website</h3>
        <p class="text-sm text-gray-500 mb-5">Tell us about your site and how we can access it.</p>

        <div class="space-y-4">
            {{-- Client label (optional — for agencies managing client sites) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client or site label <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" wire:model="clientLabel" placeholder="e.g. Joe's Bakery"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                <p class="text-xs text-gray-400 mt-1">Helps you identify this site if you manage many clients.</p>
            </div>

            {{-- Company name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company name</label>
                <input
                    type="text"
                    wire:model="companyName"
                    placeholder="e.g. John's Bakery"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('companyName') border-red-400 @enderror"
                >
                @error('companyName')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Domain name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Domain name <span class="text-red-500">*</span></label>
                <input
                    type="url"
                    wire:model="siteUrl"
                    placeholder="https://www.yourwebsite.com"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('siteUrl') border-red-400 @enderror"
                >
                @error('siteUrl')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- WP access --}}
            <div class="pt-1">
                <p class="text-sm font-medium text-gray-700 mb-1">Create login details</p>
                <p class="text-xs text-gray-500 mb-3">We need access to your site to start protecting it.</p>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="selectAccessMethod('authorize')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border-2 transition-colors
                            {{ $accessMethod === 'authorize' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-300 text-gray-700 hover:border-emerald-400' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        WP Authorize us
                    </button>
                    <span class="self-center text-xs text-gray-400">— or —</span>
                    <button
                        type="button"
                        wire:click="selectAccessMethod('manual')"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border-2 transition-colors
                            {{ $accessMethod === 'manual' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-300 text-gray-700 hover:border-emerald-400' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Add credentials manually
                    </button>
                </div>

                {{-- Authorize info --}}
                @if ($accessMethod === 'authorize')
                    <div class="mt-3 bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-xs text-emerald-800">
                        After selecting your package, we'll send you a secure plugin to install. It takes 2 minutes.
                    </div>
                @endif

                {{-- Manual credential form --}}
                @if ($accessMethod === 'manual')
                    <div class="mt-3 space-y-3 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WordPress admin URL <span class="text-red-500">*</span></label>
                            <input
                                type="url"
                                wire:model="wpAdminUrl"
                                placeholder="https://yoursite.com/wp-admin"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('wpAdminUrl') border-red-400 @enderror"
                            >
                            @error('wpAdminUrl')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Application password <span class="text-red-500">*</span></label>
                            <input
                                type="password"
                                wire:model="wpAppPassword"
                                placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 @error('wpAppPassword') border-red-400 @enderror"
                            >
                            @error('wpAppPassword')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-400 mt-1">Generate in WordPress Admin → Users → Profile → Application Passwords</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <button wire:click="cancel" class="text-sm text-gray-400 hover:text-gray-600">Cancel</button>
            <button
                wire:click="goToStep2"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
            >
                <span wire:loading.remove wire:target="goToStep2">Go to package options →</span>
                <span wire:loading wire:target="goToStep2">Checking...</span>
            </button>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Step 2: Package options + add-ons                                     --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($step === 2)
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Choose your maintenance package</h3>
        <p class="text-sm text-gray-500 mb-5">Pick the plan that fits your business. You can upgrade anytime.</p>

        {{-- Plan cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            @foreach ($plans as $plan)
                @php
                    $isSelected = $selectedPlan === $plan['slug'];
                    $labels = ['monitor' => 'Basic monitoring only', 'guard' => 'Most popular', 'shield' => 'Priority support'];
                    $badge  = $labels[$plan['slug']] ?? '';
                @endphp
                <button
                    type="button"
                    wire:click="selectPlan('{{ $plan['slug'] }}')"
                    class="relative text-left border-2 rounded-xl p-4 transition-all
                        {{ $isSelected ? 'border-emerald-500 bg-emerald-50 shadow-sm' : 'border-gray-200 bg-white hover:border-emerald-300' }}"
                >
                    @if ($plan['slug'] === 'guard')
                        <span class="absolute -top-2.5 left-3 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                            Most popular
                        </span>
                    @endif

                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-gray-900">{{ $plan['name'] }}</span>
                        @if ($isSelected)
                            <span class="w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        @endif
                    </div>
                    <p class="text-xl font-extrabold text-gray-900">${{ number_format($plan['price_monthly'], 0) }}<span class="text-sm font-normal text-gray-400">/mo</span></p>
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($plan['slug'] === 'monitor') Monthly backups · Monitoring only
                        @elseif ($plan['slug'] === 'guard') Weekly backups · Updates included
                        @elseif ($plan['slug'] === 'shield') Daily backups · Priority support
                        @endif
                    </p>
                    <div class="mt-2">
                        <span class="text-xs font-semibold {{ $isSelected ? 'text-emerald-700' : 'text-gray-500' }}">
                            {{ $isSelected ? '✓ Selected' : 'Select' }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Plan comparison toggle --}}
        <button
            type="button"
            wire:click="toggleComparison"
            class="text-sm text-emerald-700 hover:underline mb-4 flex items-center gap-1"
        >
            <svg class="w-4 h-4 transition-transform {{ $showComparison ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            {{ $showComparison ? 'Hide' : 'Show' }} full plan comparison
        </button>

        {{-- Plan comparison table --}}
        @if ($showComparison)
            <div class="mb-4 rounded-xl border border-gray-200 overflow-hidden text-sm">
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium">Feature</th>
                            <th class="text-center px-3 py-2 font-medium">Monitor</th>
                            <th class="text-center px-3 py-2 font-medium bg-emerald-50 text-emerald-700">Guard</th>
                            <th class="text-center px-3 py-2 font-medium">Shield</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr><td class="px-4 py-2">Uptime monitoring</td><td class="text-center px-3 py-2">✓</td><td class="text-center px-3 py-2 bg-emerald-50">✓</td><td class="text-center px-3 py-2">✓</td></tr>
                        <tr><td class="px-4 py-2">SSL & domain monitoring</td><td class="text-center px-3 py-2">✓</td><td class="text-center px-3 py-2 bg-emerald-50">✓</td><td class="text-center px-3 py-2">✓</td></tr>
                        <tr><td class="px-4 py-2">WordPress updates</td><td class="text-center px-3 py-2 text-gray-300">✗</td><td class="text-center px-3 py-2 bg-emerald-50">✓</td><td class="text-center px-3 py-2">✓</td></tr>
                        <tr><td class="px-4 py-2">Backup frequency</td><td class="text-center px-3 py-2">Monthly</td><td class="text-center px-3 py-2 bg-emerald-50">Weekly</td><td class="text-center px-3 py-2">Daily</td></tr>
                        <tr><td class="px-4 py-2">Backup retention</td><td class="text-center px-3 py-2">30 days</td><td class="text-center px-3 py-2 bg-emerald-50">90 days</td><td class="text-center px-3 py-2">180 days</td></tr>
                        <tr><td class="px-4 py-2">Support tickets/month</td><td class="text-center px-3 py-2 text-gray-300">None</td><td class="text-center px-3 py-2 bg-emerald-50">1</td><td class="text-center px-3 py-2">Unlimited</td></tr>
                        <tr><td class="px-4 py-2">Monthly report</td><td class="text-center px-3 py-2">✓</td><td class="text-center px-3 py-2 bg-emerald-50">✓</td><td class="text-center px-3 py-2">✓</td></tr>
                        <tr><td class="px-4 py-2">Priority support</td><td class="text-center px-3 py-2 text-gray-300">✗</td><td class="text-center px-3 py-2 bg-emerald-50 text-gray-300">✗</td><td class="text-center px-3 py-2">✓</td></tr>
                        <tr class="font-bold bg-gray-50"><td class="px-4 py-2">Price</td><td class="text-center px-3 py-2">$19/mo</td><td class="text-center px-3 py-2 bg-emerald-50 text-emerald-700">$49/mo</td><td class="text-center px-3 py-2">$99/mo</td></tr>
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Add-ons --}}
        <div class="mb-5">
            <p class="text-sm font-semibold text-gray-700 mb-2">Add-ons:</p>
            <div class="space-y-2">
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" wire:model="addonExtraStorage" class="w-4 h-4 rounded text-emerald-600 border-gray-300 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">Extra backup storage (10GB) <span class="text-gray-400">+$5/mo</span></span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" wire:model="addonSpeedAudit" class="w-4 h-4 rounded text-emerald-600 border-gray-300 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">Speed optimization audit <span class="text-gray-400">$49 one-time</span></span>
                </label>
            </div>
        </div>

        {{-- Summary box --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">Summary</span>
                <button type="button" wire:click="goBackTo(1)" class="text-xs text-emerald-700 hover:underline flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Adjust domain
                </button>
            </div>
            <div class="space-y-1 text-sm text-gray-600">
                <div class="flex justify-between">
                    <span>Domain:</span>
                    <span class="font-medium text-gray-900 truncate ml-2">{{ $domain ?: '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Package:</span>
                    <span class="font-medium text-gray-900">{{ $selectedPlanData['name'] ?? '—' }} — ${{ number_format($selectedPlanData['price_monthly'] ?? 0, 0) }}/mo</span>
                </div>
                <div class="flex justify-between">
                    <span>Add-ons:</span>
                    <span class="font-medium text-gray-900">
                        @if ($addonExtraStorage && $addonSpeedAudit) Extra storage, Speed audit
                        @elseif ($addonExtraStorage) Extra storage (+$5/mo)
                        @elseif ($addonSpeedAudit) Speed audit ($49 once)
                        @else None
                        @endif
                    </span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-200 font-bold text-gray-900">
                    <span>Total:</span>
                    <span>${{ number_format($total, 0) }}/mo{{ $addonSpeedAudit ? ' + $49 once' : '' }}</span>
                </div>
            </div>
            <button
                wire:click="goToStep3"
                wire:loading.attr="disabled"
                class="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors"
            >
                <span wire:loading.remove wire:target="goToStep3">Proceed to order →</span>
                <span wire:loading wire:target="goToStep3">Loading...</span>
            </button>
        </div>

        <div class="flex items-center justify-between">
            <button wire:click="goBackTo(1)" class="text-sm text-gray-400 hover:text-gray-600">← Back to domain</button>
            <button
                wire:click="goToStep3"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
            >
                <span wire:loading.remove wire:target="goToStep3">Proceed to order →</span>
                <span wire:loading wire:target="goToStep3">Loading...</span>
            </button>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Step 3: Order summary → Whop checkout                                 --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($step === 3)
        <h3 class="text-lg font-semibold text-gray-900 mb-5">Order Summary</h3>

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-5">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Site:</span>
                    <span class="font-semibold text-gray-900">{{ $domain ?: $siteUrl }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Plan:</span>
                    <span class="font-semibold text-gray-900">{{ $selectedPlanData['name'] ?? '—' }} — ${{ number_format($selectedPlanData['price_monthly'] ?? 0, 0) }}/month</span>
                </div>
                @if ($addonExtraStorage)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Extra storage:</span>
                        <span class="font-semibold text-gray-900">+$5/mo</span>
                    </div>
                @endif
                @if ($addonSpeedAudit)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Speed audit:</span>
                        <span class="font-semibold text-gray-900">$49 one-time</span>
                    </div>
                @endif
                <div class="flex justify-between pt-2 border-t border-gray-200 font-bold text-gray-900 text-base">
                    <span>Total:</span>
                    <span>${{ number_format($total, 0) }}/month{{ $addonSpeedAudit ? ' + $49 once' : '' }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-3">Billed monthly. Cancel anytime.</p>
        </div>

        <button
            wire:click="proceedToCheckout"
            wire:loading.attr="disabled"
            class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors text-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            <span wire:loading.remove wire:target="proceedToCheckout">Proceed to secure checkout</span>
            <span wire:loading wire:target="proceedToCheckout">Redirecting to Whop...</span>
        </button>

        @error('selectedPlan')
            <div class="mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        @error('checkout')
            <div class="mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="mt-4 flex items-start gap-2 text-xs text-gray-500 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
            <svg class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>You haven't been charged yet. Checkout is handled securely by Whop.</span>
        </div>

        <div class="mt-4 text-center">
            <button wire:click="goBackTo(2)" class="text-sm text-gray-400 hover:text-gray-600">← Adjust plan</button>
        </div>
    @endif
</div>
