@props([
    'show' => false,
    'modal' => [],
])

@if ($show && ($modal['from_name'] ?? null) && ($modal['to_name'] ?? null))
<div class="fixed inset-0 z-[60] overflow-y-auto" aria-modal="true" role="dialog" aria-labelledby="plan-change-title">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-[1px]" wire:click="closePlanChangeModal"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg z-10 overflow-hidden border border-gray-200">
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 {{ ($modal['is_upgrade'] ?? false) ? 'bg-gradient-to-r from-emerald-50 to-white' : 'bg-gradient-to-r from-slate-50 to-white' }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide {{ ($modal['is_upgrade'] ?? false) ? 'text-emerald-700' : 'text-gray-500' }}">
                            {{ ($modal['is_upgrade'] ?? false) ? 'Plan upgrade' : 'Plan change' }}
                        </p>
                        <h2 id="plan-change-title" class="text-xl font-bold text-gray-900 mt-1">{{ $modal['title'] }}</h2>
                        @if (! empty($modal['site_name']))
                            <p class="text-sm text-gray-500 mt-0.5">{{ $modal['site_name'] }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closePlanChangeModal" class="p-1.5 rounded-lg text-gray-400 hover:bg-white/80 hover:text-gray-600" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div class="px-6 py-5 space-y-5">
                <div class="flex items-center justify-center gap-3 text-sm">
                    <div class="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center">
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Current</p>
                        <p class="font-bold text-gray-900 mt-1">{{ $modal['from_name'] }}</p>
                        <p class="text-gray-600">${{ $modal['from_price'] }}/mo</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <div class="flex-1 rounded-xl border-2 {{ ($modal['is_upgrade'] ?? false) ? 'border-emerald-300 bg-emerald-50' : 'border-gray-300 bg-white' }} px-4 py-3 text-center">
                        <p class="text-xs {{ ($modal['is_upgrade'] ?? false) ? 'text-emerald-700' : 'text-gray-500' }} uppercase tracking-wide font-semibold">New plan</p>
                        <p class="font-bold text-gray-900 mt-1">{{ $modal['to_name'] }}</p>
                        <p class="{{ ($modal['is_upgrade'] ?? false) ? 'text-emerald-700' : 'text-gray-600' }} font-semibold">${{ $modal['to_price'] }}/mo</p>
                    </div>
                </div>

                <div class="rounded-xl border {{ ($modal['is_upgrade'] ?? false) ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/60' }} px-4 py-3 text-sm">
                    <p class="font-semibold {{ ($modal['is_upgrade'] ?? false) ? 'text-emerald-900' : 'text-amber-900' }}">
                        {{ ($modal['is_upgrade'] ?? false) ? 'What happens when you confirm' : 'Billing on this change' }}
                    </p>
                    <p class="mt-1 {{ ($modal['is_upgrade'] ?? false) ? 'text-emerald-800' : 'text-amber-800' }}">{{ $modal['billing_note'] }}</p>
                </div>

                @if (! empty($modal['gains']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">You will get</p>
                        <ul class="space-y-2">
                            @foreach ($modal['gains'] as $gain)
                                <li class="flex gap-2 text-sm text-gray-700">
                                    <span class="text-emerald-500 font-bold shrink-0">+</span>
                                    <span>{{ $gain }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($modal['warning']))
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <p class="font-semibold text-gray-800">Please note</p>
                        <p class="mt-1">{{ $modal['warning'] }}</p>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row gap-3">
                <button type="button" wire:click="closePlanChangeModal"
                    class="sm:flex-1 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="confirmPlanChange" wire:loading.attr="disabled"
                    class="sm:flex-1 px-4 py-2.5 rounded-lg text-sm font-semibold text-white {{ ($modal['is_upgrade'] ?? false) ? 'bg-brand hover:bg-brand-dark' : 'bg-gray-800 hover:bg-gray-900' }}">
                    <span wire:loading.remove wire:target="confirmPlanChange">{{ $modal['confirm_label'] }}</span>
                    <span wire:loading wire:target="confirmPlanChange">Processing…</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
