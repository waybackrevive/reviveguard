<div>
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Account Settings</h1>

    <div class="space-y-6">

        {{-- ── Profile ─────────────────────────────────────────────────────── --}}
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

        {{-- ── Change password ──────────────────────────────────────────────── --}}
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

        {{-- ── Plan info ────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Your Plan</h2>

            @if ($plan)
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $plan->name }}
                            @if ($plan->price_monthly)
                                — ${{ number_format($plan->price_monthly, 0) }}/month
                            @endif
                        </p>
                        @if ($sub && $sub->whop_valid_until)
                            <p class="text-xs text-gray-500 mt-0.5">
                                Next billing: {{ $sub->whop_valid_until->format('M j, Y') }}
                            </p>
                        @endif
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500">No active plan found.</p>
            @endif
        </div>

    </div>
</div>
