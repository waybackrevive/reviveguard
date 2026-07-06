<div>
    @if (session('success'))
        <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add-ons</h1>
        <p class="text-sm text-gray-500 mt-1">Order extra services — submit details, pay securely, and track progress here.</p>
    </div>

    <div class="mb-8 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-sm text-gray-700">
        <p class="font-semibold text-gray-900 mb-1">How it works</p>
        <ol class="list-decimal list-inside space-y-1 text-gray-600">
            <li>Fill in your request details and site.</li>
            <li>Pay securely via Stripe (one-time charge shown before checkout).</li>
            <li>Our team starts work and posts updates on this page.</li>
        </ol>
    </div>

    @if ($orders->isNotEmpty())
        <div class="bg-white rounded-[10px] border border-gray-200 shadow-sm mb-8 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Your orders</h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach ($orders as $order)
                    <li class="px-5 py-4 text-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $order->addon_name }}</p>
                                <p class="text-gray-500 text-xs mt-0.5">
                                    {{ $order->formattedAmount() ?? $order->price_label }}
                                    @if ($order->quantity > 1) · Qty {{ $order->quantity }} @endif
                                    @if ($order->site) · {{ $order->site->displayName() }} @endif
                                    · {{ $order->created_at->format('M j, Y') }}
                                </p>
                                @if ($order->team_update)
                                    <p class="mt-2 text-gray-700 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-xs">
                                        <strong>Team update:</strong> {{ $order->team_update }}
                                        @if ($order->team_updated_at)
                                            <span class="text-gray-400">· {{ $order->team_updated_at->diffForHumans() }}</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $order->statusColor() }}">
                                    {{ $order->statusLabel() }}
                                </span>
                                @if ($order->isAwaitingPayment())
                                    <button wire:click="payOrder('{{ $order->id }}')"
                                        class="text-xs font-semibold text-white bg-brand hover:bg-brand-dark px-3 py-1.5 rounded-lg">
                                        Pay now
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($addons as $addon)
            <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm flex flex-col">
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900">{{ $addon['name'] }}</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $addon['description'] }}</p>
                </div>
                <div class="mt-5 flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
                    <span class="text-lg font-bold text-gray-900">{{ $addon['price_label'] }}</span>
                    <button wire:click="openOrder('{{ $addon['slug'] }}')"
                        class="text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2 rounded-lg transition-colors">
                        Order
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if ($showOrderModal && $selectedAddon)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" wire:click="closeOrder"></div>
                <div class="relative bg-white rounded-[10px] shadow-xl w-full max-w-lg p-6 z-10">
                    <h2 class="text-lg font-semibold text-gray-900">Order: {{ $selectedAddon['name'] }}</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $selectedAddon['price_label'] }}
                        @if ($selectedAddon['billing'] ?? '' === 'monthly')
                            · First month charged at checkout
                        @else
                            · Secure one-time payment after you submit
                        @endif
                    </p>

                    <form wire:submit.prevent="placeOrder" class="mt-5 space-y-4 text-sm">
                        @if ($selectedAddon['requires_site'] ?? false)
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Site</label>
                                <select wire:model="orderSiteId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->displayName() }}</option>
                                    @endforeach
                                </select>
                                @error('orderSiteId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($selectedAddon['show_quantity'] ?? false)
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $selectedAddon['quantity_label'] ?? 'Quantity' }}</label>
                                <input type="number" wire:model="orderQuantity" min="1" max="99"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                                @error('orderQuantity') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $selectedAddon['notes_label'] ?? 'Details for our team' }}</label>
                            <textarea wire:model="orderNotes" rows="4" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 resize-none"
                                placeholder="Tell us what you need…"></textarea>
                            @error('orderNotes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="flex-1 bg-brand text-white font-semibold py-2.5 rounded-lg">Continue to payment</button>
                            <button type="button" wire:click="closeOrder" class="px-4 py-2.5 border border-gray-300 rounded-lg">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
