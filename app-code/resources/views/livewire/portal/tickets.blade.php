<div>
    <div class="mb-6">
        <h1 class="portal-title text-3xl font-extrabold">Support</h1>
        <p class="portal-muted mt-2 text-sm">Open a tracked request when you need technical help, investigation, or backup restore assistance.</p>
    </div>

    @if (optional($plan)->slug === 'monitor')
        <div class="rounded-3xl p-6 mb-6 text-sm portal-badge-warning" style="display:block;">
            Support tickets are available on Guard and Shield plans.
            <a href="{{ route('portal.account') }}" class="portal-link font-semibold">View your plan →</a>
        </div>
    @else

    @if (optional($plan)->slug === 'guard')
        @php $usedCount = $tickets->where('created_at', '>=', now()->startOfMonth())->count(); @endphp
        <p class="portal-muted text-sm mb-4">
            {{ $usedCount }} of 1 support ticket{{ $usedCount === 1 ? '' : 's' }} used this month.
        </p>
    @endif

    @if ($submitted)
        <div class="rounded-3xl p-4 mb-6 flex items-start justify-between gap-4" style="background: var(--portal-success-soft); border: 1px solid var(--portal-border);">
            <p class="text-sm" style="color: var(--portal-success-text);">Ticket submitted. We'll respond within 24 hours.</p>
            <button wire:click="dismissSuccess" class="text-xs font-semibold" style="color: var(--portal-success-text);">Dismiss</button>
        </div>
    @endif

    @if (session('ticket_error'))
        <div class="rounded-3xl p-4 mb-6" style="background: var(--portal-danger-soft); border: 1px solid var(--portal-border);">
            <p class="text-sm" style="color: var(--portal-danger-text);">{{ session('ticket_error') }}</p>
        </div>
    @endif

    <div class="portal-panel-strong rounded-3xl p-6 mb-8">
        <h2 class="portal-title text-base font-extrabold mb-4">Need help with your website?</h2>

        <form wire:submit.prevent="submitTicket" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold portal-title mb-1">Subject</label>
                <input type="text" wire:model="subject" placeholder="Briefly describe your issue"
                       class="portal-input w-full px-3 py-2.5 rounded-xl text-sm @error('subject') border-red-400 @enderror">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($sites->count() > 1)
                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Site</label>
                    <select wire:model="siteId"
                            class="portal-select w-full px-3 py-2.5 rounded-xl text-sm">
                        <option value="">Select a site</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-sm font-semibold portal-title mb-1">Message</label>
                <textarea wire:model="message" rows="4" placeholder="Please describe the issue in detail"
                          class="portal-textarea w-full px-3 py-2.5 rounded-xl text-sm @error('message') border-red-400 @enderror"></textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="portal-btn-primary px-6 py-2.5 text-sm font-semibold rounded-xl transition-colors"
                    wire:loading.attr="disabled" wire:loading.class="opacity-60">
                <span wire:loading.remove>Submit Ticket</span>
                <span wire:loading>Submitting…</span>
            </button>
        </form>
    </div>

    @endif

    @if ($tickets->isNotEmpty())
        <h2 class="portal-title text-base font-extrabold mb-3">Your Tickets</h2>
        <div class="portal-panel-strong rounded-3xl overflow-hidden">
            @foreach ($tickets as $ticket)
                <div wire:click="showTicket('{{ $ticket->id }}')"
                     class="portal-table-row portal-table-row-hover border-b last:border-b-0 flex items-center justify-between px-6 py-4 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $ticket->isOpen() ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        <div class="min-w-0">
                            <p class="portal-title text-sm font-bold truncate">{{ $ticket->subject }}</p>
                            <p class="portal-muted text-xs">Submitted {{ $ticket->created_at->format('M j') }}</p>
                        </div>
                    </div>
                    <span class="ml-4 flex-shrink-0 text-xs font-semibold {{ $ticket->isOpen() ? 'text-emerald-600' : 'portal-soft' }}">
                        {{ $ticket->isOpen() ? 'Open' : 'Resolved' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($selectedTicket)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
             wire:click.self="closeModal">
            <div class="portal-modal-panel rounded-3xl max-w-md w-full p-6">
                <h3 class="portal-title text-base font-extrabold mb-1">{{ $selectedTicket->subject }}</h3>
                <p class="portal-soft text-xs mb-4">Submitted {{ $selectedTicket->created_at->format('M j, Y') }}</p>

                <div class="portal-panel-soft rounded-2xl p-4 text-sm portal-muted mb-4">
                    {{ $selectedTicket->message }}
                </div>

                @if ($selectedTicket->admin_reply)
                    <div class="border-l-4 border-emerald-500 pl-4 mb-4">
                        <p class="portal-link text-xs font-semibold mb-1">Team response</p>
                        <p class="portal-muted text-sm">{{ $selectedTicket->admin_reply }}</p>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="portal-badge {{ $selectedTicket->isOpen() ? 'portal-badge-success' : 'portal-badge-neutral' }}">
                        {{ ucfirst($selectedTicket->status) }}
                    </span>
                    <button wire:click="closeModal"
                            class="portal-btn-secondary px-4 py-2 text-sm font-semibold rounded-xl transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
