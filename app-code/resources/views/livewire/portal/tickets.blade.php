<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Support</h1>

    {{-- ── Monitor plan restriction ────────────────────────────────────────── --}}
    @if (optional($plan)->slug === 'monitor')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6 text-sm text-amber-800">
            Support tickets are available on Guard and Shield plans.
            <a href="{{ route('portal.account') }}" class="font-semibold hover:underline">View your plan →</a>
        </div>
    @else

    {{-- ── Guard plan counter ──────────────────────────────────────────────── --}}
    @if (optional($plan)->slug === 'guard')
        @php $usedCount = $tickets->where('created_at', '>=', now()->startOfMonth())->count(); @endphp
        <p class="text-sm text-gray-500 mb-4">
            {{ $usedCount }} of 1 support ticket{{ $usedCount === 1 ? '' : 's' }} used this month.
        </p>
    @endif

    {{-- ── Success message ─────────────────────────────────────────────────── --}}
    @if ($submitted)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-6 flex items-start justify-between gap-4">
            <p class="text-sm text-green-700">Ticket submitted. We'll respond within 24 hours.</p>
            <button wire:click="dismissSuccess" class="text-green-500 hover:text-green-700 text-xs">Dismiss</button>
        </div>
    @endif

    @if (session('ticket_error'))
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
            <p class="text-sm text-red-700">{{ session('ticket_error') }}</p>
        </div>
    @endif

    {{-- ── Submit form ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Need help with your website?</h2>

        <form wire:submit.prevent="submitTicket" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" wire:model="subject" placeholder="Briefly describe your issue"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('subject') border-red-400 @enderror">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($sites->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <select wire:model="siteId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a site</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea wire:model="message" rows="4" placeholder="Please describe the issue in detail"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('message') border-red-400 @enderror"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors"
                    wire:loading.attr="disabled" wire:loading.class="opacity-60">
                <span wire:loading.remove>Submit Ticket</span>
                <span wire:loading>Submitting…</span>
            </button>
        </form>
    </div>

    @endif {{-- end monitor restriction --}}

    {{-- ── Ticket list ─────────────────────────────────────────────────────── --}}
    @if ($tickets->isNotEmpty())
        <h2 class="text-base font-semibold text-gray-900 mb-3">Your Tickets</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($tickets as $ticket)
                <div wire:click="showTicket('{{ $ticket->id }}')"
                     class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $ticket->isOpen() ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $ticket->subject }}</p>
                            <p class="text-xs text-gray-400">Submitted {{ $ticket->created_at->format('M j') }}</p>
                        </div>
                    </div>
                    <span class="ml-4 flex-shrink-0 text-xs font-medium {{ $ticket->isOpen() ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $ticket->isOpen() ? 'Open' : 'Resolved' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Ticket detail modal ─────────────────────────────────────────────── --}}
    @if ($selectedTicket)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
             wire:click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $selectedTicket->subject }}</h3>
                <p class="text-xs text-gray-400 mb-4">Submitted {{ $selectedTicket->created_at->format('M j, Y') }}</p>

                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 mb-4">
                    {{ $selectedTicket->message }}
                </div>

                @if ($selectedTicket->admin_reply)
                    <div class="border-l-4 border-blue-400 pl-4 mb-4">
                        <p class="text-xs font-medium text-blue-600 mb-1">Team response</p>
                        <p class="text-sm text-gray-700">{{ $selectedTicket->admin_reply }}</p>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-xs px-2 py-1 rounded-full {{ $selectedTicket->isOpen() ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ ucfirst($selectedTicket->status) }}
                    </span>
                    <button wire:click="closeModal"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-sm font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
