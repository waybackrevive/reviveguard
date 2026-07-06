<div>
    @if (\App\Support\StripeConfig::isTestMode())
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
            <strong>Test mode.</strong> Payments use Stripe test cards — no real charges.
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('portal.sites') }}" class="text-sm text-gray-500 hover:text-brand inline-flex items-center gap-1 mb-2">← Back to sites</a>
        <h1 class="text-2xl font-bold text-gray-900">Add a site</h1>
        <p class="text-sm text-gray-500 mt-1">We'll walk you through connection and choosing your plan.</p>
    </div>

    <div class="max-w-2xl">
        <livewire:portal.site-wizard />
    </div>
</div>
