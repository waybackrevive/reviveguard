<div class="min-h-screen lg:grid lg:grid-cols-2">
    {{-- Brand panel --}}
    <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-brand-light via-white to-emerald-50 px-12 py-14 border-r border-gray-100">
        <div>
            <div class="flex items-center gap-2 text-2xl font-bold tracking-tight">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand text-white text-sm font-bold">RG</span>
                <span>Revive<span class="text-brand">Guard</span></span>
            </div>
        </div>
        <div class="max-w-md">
            <p class="text-sm font-semibold text-brand uppercase tracking-wider mb-3">Customize your account</p>
            <h1 class="text-4xl font-bold text-gray-900 leading-tight mb-4">Your sites.<br>Our care.<br>One calm dashboard.</h1>
            <p class="text-gray-600 text-lg leading-relaxed">From the team that has restored 500+ websites — expert-managed protection you can actually see and trust.</p>
        </div>
        <p class="text-xs text-gray-400">Operated by WaybackRevive LLC</p>
    </div>

    {{-- Form panel --}}
    <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16 bg-white">
        <div class="lg:hidden mb-8">
            <div class="flex items-center gap-2 text-xl font-bold">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white text-xs font-bold">RG</span>
                Revive<span class="text-brand">Guard</span>
            </div>
        </div>

        <div class="w-full max-w-lg mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome — let's set up your workspace</h2>
            <p class="text-sm text-gray-500 mb-8">This takes about 30 seconds. Then you'll connect your first site.</p>

            <form wire:submit="complete" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Your first name</label>
                    <input type="text" wire:model="firstName"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none" />
                    @error('firstName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Workspace name</label>
                    <input type="text" wire:model="workspaceName" placeholder="e.g. Smith Dental or My Agency"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none" />
                    @error('workspaceName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">What describes you best?</label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach (['solo' => ['Solo business', 'I manage my own website'], 'freelance' => ['Freelancer', 'I manage sites for clients'], 'agency' => ['Agency', 'My team manages client sites']] as $value => [$title, $desc])
                            <button type="button" wire:click="$set('accountType', '{{ $value }}')"
                                class="text-left rounded-xl border-2 p-3 transition {{ $accountType === $value ? 'border-brand bg-brand-light/40' : 'border-gray-200 hover:border-gray-300' }}">
                                <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                                <p class="text-xs text-gray-500 mt-1 leading-snug">{{ $desc }}</p>
                            </button>
                        @endforeach
                    </div>
                    @error('accountType') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">How many websites do you manage?</label>
                    <select wire:model="sitesManagedRange"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none bg-white">
                        <option value="1">Just 1 site</option>
                        <option value="1-5">1–5 sites</option>
                        <option value="1-20">1–20 sites</option>
                        <option value="21-50">21–50 sites</option>
                        <option value="50+">50+ sites</option>
                    </select>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand hover:bg-brand-dark text-white text-sm font-semibold px-6 py-3 transition-colors">
                        <span wire:loading.remove wire:target="complete">Connect your first site →</span>
                        <span wire:loading wire:target="complete">Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
