<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Add-ons</h1>
        <p class="text-sm text-gray-500 mt-1">Extend your plan with one-time services and extra capacity.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($addons as $addon)
        <div class="bg-white rounded-[10px] border border-gray-200 p-5 shadow-sm flex flex-col">
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900">{{ $addon['name'] }}</h3>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed">{{ $addon['description'] }}</p>
            </div>
            <div class="mt-5 flex items-center justify-between gap-3 pt-4 border-t border-gray-100">
                <span class="text-lg font-bold text-gray-900">{{ $addon['price_label'] }}</span>
                <button wire:click="requestAddon('{{ $addon['slug'] }}')"
                    class="text-sm font-semibold text-white bg-brand hover:bg-brand-dark px-4 py-2 rounded-lg transition-colors">
                    Request
                </button>
            </div>
        </div>
    @endforeach
    </div>

    <p class="mt-6 text-sm text-gray-500">
    Add-ons are billed separately. We'll confirm scope and timing before any charge.
    Questions? <a href="{{ route('portal.tickets') }}" class="text-brand font-medium hover:underline">Contact support</a>.
    </p>
</div>
