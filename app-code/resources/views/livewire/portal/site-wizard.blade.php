<div>
    {{-- Step indicator --}}
    <div class="flex items-center mb-8 overflow-x-auto">
        @foreach ([1 => 'Site URL', 2 => 'Connection', 3 => 'Plan', 4 => 'Payment'] as $num => $label)
            <div class="flex items-center gap-2 {{ $num > 1 ? 'flex-1' : '' }}">
                @if ($num > 1)
                    <div class="flex-1 h-px {{ $step >= $num ? 'bg-emerald-400' : 'bg-gray-200' }} mx-2 min-w-[12px]"></div>
                @endif
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $step === $num ? 'bg-brand text-white ring-2 ring-emerald-200' : ($step > $num ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400') }}">
                        @if ($step > $num)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    <span class="text-xs font-medium hidden sm:inline {{ $step === $num ? 'text-brand' : ($step > $num ? 'text-emerald-600' : 'text-gray-400') }}">{{ $label }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Step 1: URL --}}
    @if ($step === 1)
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Which site are we protecting?</h3>
        <p class="text-sm text-gray-500 mb-5">Enter the website URL. You can add a label if you manage sites for clients.</p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Website URL <span class="text-red-500">*</span></label>
                <input type="url" wire:model="siteUrl" placeholder="https://www.example.com"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 @error('siteUrl') border-red-400 @enderror" />
                @error('siteUrl') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client or site label <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" wire:model="clientLabel" placeholder="e.g. Joe's Bakery"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500" />
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button wire:click="goToConnection" wire:loading.attr="disabled"
                class="px-5 py-2.5 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg">
                Continue →
            </button>
            <button wire:click="cancel" type="button" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
        </div>
    @endif

    {{-- Step 2: Connection --}}
    @if ($step === 2)
        <livewire:portal.connection-guide :site-id="$siteId" :connection-token="$connectionToken" :key="'conn-'.$siteId" />

        <div class="flex gap-3 mt-6">
            <button wire:click="goToPlan" class="px-5 py-2.5 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg">
                Continue to plan →
            </button>
            <button wire:click="goBackTo(1)" type="button" class="px-4 py-2.5 text-sm text-gray-600">← Back</button>
        </div>
        <p class="text-xs text-gray-400 mt-3">You can finish payment while we wait for the connection.</p>
    @endif

    {{-- Step 3: Plan --}}
    @if ($step === 3)
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Choose your coverage</h3>
        <p class="text-sm text-gray-500 mb-5">Per site, per month. Our team handles everything.</p>

        <div class="grid gap-3 sm:grid-cols-3 mb-4">
            @foreach ($plans as $plan)
                <button type="button" wire:click="selectPlan('{{ $plan['slug'] }}')"
                    class="text-left p-4 rounded-[10px] border-2 transition-colors
                        {{ $selectedPlan === $plan['slug'] ? 'border-brand bg-brand-light' : 'border-gray-200 hover:border-emerald-300' }}">
                    <p class="font-semibold text-gray-900">{{ $plan['name'] }}</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($plan['price_monthly'], 0) }}<span class="text-sm font-normal text-gray-500">/mo</span></p>
                </button>
            @endforeach
        </div>
        @error('selectedPlan') <p class="text-red-600 text-xs mb-3">{{ $message }}</p> @enderror

        <div class="flex gap-3">
            <button wire:click="goToCheckout" class="px-5 py-2.5 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg">Review & pay →</button>
            <button wire:click="goBackTo(2)" type="button" class="px-4 py-2.5 text-sm text-gray-600">← Back</button>
        </div>
    @endif

    {{-- Step 4: Payment summary --}}
    @if ($step === 4)
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Complete your order</h3>
        <p class="text-sm text-gray-500 mb-5">You'll be redirected to secure checkout.</p>

        @if ($stripeTestMode)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Test mode is on. Use card <span class="font-mono">4242 4242 4242 4242</span> — any future expiry and CVC.
            </div>
        @endif

        <div class="bg-gray-50 border border-gray-200 rounded-[10px] p-5 mb-6 text-sm">
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600">Site</span>
                <span class="font-medium text-gray-900">{{ $domain }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="text-gray-600">Plan</span>
                <span class="font-medium text-gray-900">{{ $selectedPlanData['name'] ?? '—' }}</span>
            </div>
            <div class="flex justify-between py-3 text-base font-bold">
                <span>Monthly total</span>
                <span>${{ number_format($selectedPlanData['price_monthly'] ?? 0, 0) }}</span>
            </div>
        </div>

        <div class="flex gap-3">
            <button wire:click="proceedToCheckout" wire:loading.attr="disabled"
                class="px-5 py-2.5 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg">
                <span wire:loading.remove wire:target="proceedToCheckout">Go to secure checkout →</span>
                <span wire:loading wire:target="proceedToCheckout">Redirecting…</span>
            </button>
            <button wire:click="goBackTo(3)" type="button" class="px-4 py-2.5 text-sm text-gray-600">← Back</button>
        </div>
        <p class="text-xs text-gray-400 mt-3">You haven't been charged yet. Checkout is handled securely by Stripe.</p>
    @endif
</div>
