<div>
    <h1 class="portal-title text-3xl font-extrabold mb-6">Account Settings</h1>

    <div class="space-y-6">

        <div class="portal-panel-strong rounded-3xl p-6">
            <h2 class="portal-title text-base font-extrabold mb-4">Your Details</h2>

            @if ($profileSaved)
                <div class="mb-4 p-3 rounded-xl text-sm" style="background: var(--portal-success-soft); border: 1px solid var(--portal-border); color: var(--portal-success-text);">
                    Changes saved.
                </div>
            @endif

            <form wire:submit.prevent="saveProfile" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Name</label>
                    <input type="text" wire:model="name"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Email</label>
                    <input type="email" wire:model="email"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Phone</label>
                    <input type="text" wire:model="phone" placeholder="Optional"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm @error('phone') border-red-400 @enderror">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="portal-btn-primary px-5 py-2.5 text-sm font-semibold rounded-xl transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Save Changes
                </button>
            </form>
        </div>

        <div class="portal-panel-strong rounded-3xl p-6">
            <h2 class="portal-title text-base font-extrabold mb-4">Change Password</h2>

            @if ($passwordSaved)
                <div class="mb-4 p-3 rounded-xl text-sm" style="background: var(--portal-success-soft); border: 1px solid var(--portal-border); color: var(--portal-success-text);">
                    Password updated successfully.
                </div>
            @endif

            <form wire:submit.prevent="changePassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Current password</label>
                    <input type="password" wire:model="currentPassword"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm @error('currentPassword') border-red-400 @enderror">
                    @error('currentPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">New password</label>
                    <input type="password" wire:model="newPassword" minlength="8"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm @error('newPassword') border-red-400 @enderror">
                    @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold portal-title mb-1">Confirm new password</label>
                    <input type="password" wire:model="confirmPassword" minlength="8"
                           class="portal-input w-full max-w-sm px-3 py-2.5 rounded-xl text-sm">
                </div>

                <button type="submit"
                        class="portal-btn-secondary px-5 py-2.5 text-sm font-semibold rounded-xl transition-colors"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    Update Password
                </button>
            </form>
        </div>

        <div class="portal-panel-strong rounded-3xl p-6">
            <h2 class="portal-title text-base font-extrabold mb-4">Your Plan</h2>

            @if ($plan)
                <div class="flex items-center gap-4">
                    <div>
                        <p class="portal-title text-sm font-bold">
                            {{ $plan->name }}
                            @if ($plan->price_monthly)
                                — ${{ number_format($plan->price_monthly, 0) }}/month
                            @endif
                        </p>
                        @if ($sub && $sub->whop_valid_until)
                            <p class="portal-muted text-xs mt-0.5">
                                Next billing: {{ $sub->whop_valid_until->format('M j, Y') }}
                            </p>
                        @endif
                    </div>
                </div>
            @else
                <p class="portal-muted text-sm">No active plan found.</p>
            @endif
        </div>

    </div>
</div>
