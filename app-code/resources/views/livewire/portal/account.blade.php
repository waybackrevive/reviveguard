<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Account Settings</h1>

    {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 mb-6 border-b border-gray-200">
        @foreach (['profile' => 'Profile & Password', 'plan' => 'Plan', 'billing' => 'Billing & Invoices'] as $tab => $label)
            <button
                wire:click="$set('activeTab', '{{ $tab }}')"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px
                    {{ $activeTab === $tab
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── Profile & Password tab ───────────────────────────────────────── --}}
    @if ($activeTab === 'profile')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Your Details</h2>

            @if ($profileSaved)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    Changes saved.
                </div>
            @endif

            <form wire:submit.prevent="saveProfile" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" wire:model="name"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" wire:model="email"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" wire:model="phone" placeholder="Optional"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-400 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Save Changes
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Change Password</h2>

            @if ($passwordSaved)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    Password updated successfully.
                </div>
            @endif

            <form wire:submit.prevent="changePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                    <input type="password" wire:model="currentPassword"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('currentPassword') border-red-400 @enderror">
                    @error('currentPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input type="password" wire:model="newPassword" minlength="8"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('newPassword') border-red-400 @enderror">
                    @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                    <input type="password" wire:model="confirmPassword" minlength="8"
                           class="w-full max-w-sm px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit"
                        class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold rounded-lg transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Update Password
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Plan tab ─────────────────────────────────────────────────────── --}}
    @if ($activeTab === 'plan')
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Your Plan</h2>

        @if ($plan)
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-lg font-semibold text-gray-900">{{ $plan->name }}</p>
                    @if ($plan->price_monthly)
                        <p class="text-sm text-gray-500 mt-0.5">
                            ${{ number_format($plan->price_monthly, 0) }} / month
                        </p>
                    @endif
                    @if ($sub?->whop_status)
                        <span class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded-full text-xs font-semibold
                            {{ $sub->whop_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sub->whop_status === 'active' ? 'bg-green-500' : 'bg-yellow-500' }}"></span>
                            {{ ucfirst($sub->whop_status) }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($sub?->whop_valid_until)
                <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-500">
                    <p>Next billing: <strong class="text-gray-800">{{ $sub->whop_valid_until->format('F j, Y') }}</strong></p>
                </div>
            @endif

            <div class="mt-5 pt-5 border-t border-gray-100">
                <p class="text-xs text-gray-400">
                    To change or cancel your plan, visit your
                    <a href="https://whop.com" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Whop membership dashboard</a>.
                </p>
            </div>
        @else
            <p class="text-sm text-gray-500">No active plan found. Contact support if you believe this is an error.</p>
        @endif
    </div>
    @endif

    {{-- ── Billing & Invoices tab ───────────────────────────────────────── --}}
    @if ($activeTab === 'billing')
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Invoices</h2>

        @if ($invoices->isEmpty())
            <p class="text-sm text-gray-500">No invoices yet. Your first invoice will appear here after your next billing cycle.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs text-gray-500 uppercase tracking-wide">
                            <th class="pb-2 pr-4">Invoice #</th>
                            <th class="pb-2 pr-4">Period</th>
                            <th class="pb-2 pr-4">Amount</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-gray-800 text-xs">{{ $invoice->invoice_number }}</td>
                                <td class="py-3 pr-4 text-gray-600">
                                    {{ $invoice->period_start->format('M j') }} – {{ $invoice->period_end->format('M j, Y') }}
                                </td>
                                <td class="py-3 pr-4 font-medium text-gray-800">{{ $invoice->formatted_total }}</td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : ($invoice->status === 'void' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-700') }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    @if ($invoice->pdf_url)
                                        <a href="{{ $invoice->pdf_url }}" target="_blank" rel="noopener"
                                           class="text-blue-600 hover:underline text-xs">
                                            Download
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endif
</div>

