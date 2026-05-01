<div>
    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-6">
        @foreach ([1 => 'Site URL', 2 => 'Install Plugin', 3 => 'Done'] as $num => $label)
            <div class="flex items-center gap-1.5">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold
                    {{ $step === $num ? 'bg-emerald-600 text-white' : ($step > $num ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                    @if ($step > $num)
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-xs {{ $step === $num ? 'text-emerald-700 font-medium' : 'text-gray-400' }}">{{ $label }}</span>
            </div>
            @if ($num < 3)
                <div class="flex-1 h-px bg-gray-200 mx-1"></div>
            @endif
        @endforeach
    </div>

    {{-- ── Step 1: Enter URL ────────────────────────────────────────────── --}}
    @if ($step === 1)
        <h3 class="text-base font-semibold text-gray-900 mb-1">Add your website</h3>
        <p class="text-sm text-gray-500 mb-4">Enter the full URL of the WordPress site you want to monitor.</p>

        @error('siteUrl')
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site URL <span class="text-red-500">*</span></label>
                <input
                    type="url"
                    wire:model="siteUrl"
                    placeholder="https://example.com"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Label <span class="text-gray-400 font-normal">(optional)</span></label>
                <input
                    type="text"
                    wire:model="siteLabel"
                    placeholder="My Business Site"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                >
            </div>
        </div>

        <div class="flex gap-3 mt-5">
            <button
                wire:click="submitStep1"
                wire:loading.attr="disabled"
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors"
            >
                <span wire:loading.remove wire:target="submitStep1">Continue</span>
                <span wire:loading wire:target="submitStep1">Saving...</span>
            </button>
            <button wire:click="cancel" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    @endif

    {{-- ── Step 2: Install plugin ───────────────────────────────────────── --}}
    @if ($step === 2)
        <h3 class="text-base font-semibold text-gray-900 mb-1">Install the ReviveGuard plugin</h3>
        <p class="text-sm text-gray-500 mb-4">
            Download and install the plugin on <strong>{{ $pendingSite?->url }}</strong>, then paste your unique agent key below.
        </p>

        <ol class="space-y-3 mb-5 text-sm text-gray-700">
            <li class="flex gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex-shrink-0 flex items-center justify-center text-xs font-semibold mt-0.5">1</span>
                <span>
                    <a href="{{ $pluginDownloadUrl }}" class="text-emerald-700 hover:underline font-medium" target="_blank">
                        Download reviveguard-agent.zip
                    </a>
                    &nbsp;&rarr; install via WordPress Admin &rarr; Plugins &rarr; Add New &rarr; Upload Plugin
                </span>
            </li>
            <li class="flex gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex-shrink-0 flex items-center justify-center text-xs font-semibold mt-0.5">2</span>
                <span>Go to <strong>Settings &rarr; ReviveGuard Agent</strong> in your WordPress admin.</span>
            </li>
            <li class="flex gap-2">
                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex-shrink-0 flex items-center justify-center text-xs font-semibold mt-0.5">3</span>
                <span>
                    Enter your agent key shown below and the API endpoint:
                    <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">{{ config('app.url') }}/api/v1</code>
                </span>
            </li>
        </ol>

        @if ($agentKey)
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 mb-5">
                <p class="text-xs text-gray-500 mb-1 font-medium">Your agent key:</p>
                <code class="text-sm font-mono text-gray-900 break-all">{{ $agentKey }}</code>
            </div>
        @endif

        @error('agentKeyInput')
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm agent key from plugin settings</label>
            <input
                type="text"
                wire:model="agentKeyInput"
                placeholder="Paste the agent key from your plugin settings..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"
            >
        </div>

        <div class="flex gap-3">
            <button
                wire:click="submitStep2"
                wire:loading.attr="disabled"
                class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors"
            >
                <span wire:loading.remove wire:target="submitStep2">Verify &amp; Continue</span>
                <span wire:loading wire:target="submitStep2">Verifying...</span>
            </button>
            <button wire:click="cancel" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    @endif

    {{-- ── Step 3: Confirmation ─────────────────────────────────────────── --}}
    @if ($step === 3)
        <div class="text-center py-4">
            @if ($heartbeatReceived)
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">All set!</h3>
                <p class="text-sm text-gray-500">{{ $statusMessage }}</p>
            @else
                <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">Waiting for heartbeat</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $statusMessage }}</p>
                <button
                    wire:click="checkHeartbeat"
                    wire:poll.10000ms="checkHeartbeat"
                    class="text-sm text-emerald-700 hover:underline"
                >
                    Check now
                </button>
            @endif
        </div>
    @endif
</div>

