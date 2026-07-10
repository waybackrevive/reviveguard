<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-2">Support</h1>
    <p class="text-sm text-gray-500 mb-6">{{ $supportTier['headline'] }}</p>

    @if ($isShield ?? false)
        <div class="bg-gradient-to-br from-violet-50 to-white border border-violet-200 rounded-2xl p-5 mb-6">
            <p class="text-xs font-semibold text-violet-700 uppercase tracking-wider mb-3">Shield premium</p>
            <div class="grid gap-4 sm:grid-cols-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Account manager</p>
                    @if ($accountManager ?? null)
                        <p class="font-medium text-gray-900">{{ $accountManager->name }}</p>
                        <a href="mailto:{{ $accountManager->email }}" class="text-xs text-brand hover:underline">{{ $accountManager->email }}</a>
                    @else
                        <p class="text-gray-600">Assigned shortly after onboarding</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Content edit hours</p>
                    <p class="font-medium text-gray-900">{{ $contentHours ?? 0 }} min left this month</p>
                    <p class="text-xs text-gray-500">120 min included · billed when tickets close</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Emergency restore</p>
                    <p class="font-medium text-gray-900">4-hour SLA</p>
                    <p class="text-xs text-gray-500">Use emergency restore ticket type below</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl p-5 mb-6 text-sm">
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Email</p>
                <p class="text-gray-900 font-medium">{{ $supportTier['email'] ? 'Unlimited' : 'Not included' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Response time</p>
                <p class="text-gray-900 font-medium">{{ $supportTier['reply_sla'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Phone</p>
                <p class="text-gray-900 font-medium">
                    @if ($supportTier['phone'])
                        Included — we call you back on priority issues
                    @else
                        Email only on Monitor
                    @endif
                </p>
            </div>
        </div>
        @if (optional($plan)->slug === 'shield')
            <p class="mt-4 text-xs text-violet-800 bg-violet-50 border border-violet-100 rounded-lg px-3 py-2">
                Shield clients receive priority routing — emergency restores are tracked against a 4-hour SLA.
            </p>
        @endif
    </div>

    @if ($supportTier['email'])
        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Need help with your website?</h2>

            <form wire:submit.prevent="submitTicket" class="space-y-4">
                @if ($isShield ?? false)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Request type</label>
                        <select wire:model="ticketType"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="general">General support</option>
                            <option value="content_edit">Content edit (uses monthly hours)</option>
                            <option value="emergency_restore">Emergency restore (4h SLA)</option>
                        </select>
                    </div>
                @endif

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
                    <span wire:loading.remove>Submit ticket</span>
                    <span wire:loading>Submitting…</span>
                </button>
            </form>
        </div>
    @endif

    @if ($tickets->isNotEmpty())
        <h2 class="text-base font-semibold text-gray-900 mb-3">Your tickets</h2>
        <div class="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach ($tickets as $ticket)
                <div wire:click="showTicket('{{ $ticket->id }}')"
                     class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $ticket->isOpen() ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $ticket->subject }}</p>
                            <p class="text-xs text-gray-400">Submitted {{ $ticket->created_at->format('M j') }}</p>
                            @if ($ticket->sla_due_at && $ticket->isOpen())
                                @php $slaLabel = $slaService->slaLabel($ticket); @endphp
                                @if ($slaLabel)
                                    <p class="text-xs mt-1 {{ $slaService->isBreached($ticket) ? 'text-red-600 font-semibold' : 'text-amber-600' }}">{{ $slaLabel }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                    <span class="ml-4 flex-shrink-0 text-xs font-medium {{ $ticket->isOpen() ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $ticket->isOpen() ? 'Open' : 'Resolved' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($selectedTicket)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
             wire:click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $selectedTicket->subject }}</h3>
                <p class="text-xs text-gray-400 mb-4">Submitted {{ $selectedTicket->created_at->format('M j, Y') }}</p>

                @if ($selectedTicket->sla_due_at && $selectedTicket->isOpen())
                    <p class="text-xs mb-4 px-3 py-2 rounded-lg {{ $slaService->isBreached($selectedTicket) ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-800' }}">
                        {{ $slaService->slaLabel($selectedTicket) }}
                    </p>
                @endif

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
