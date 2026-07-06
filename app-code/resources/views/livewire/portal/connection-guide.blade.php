<div class="{{ $compact ? '' : 'bg-white rounded-[10px] border border-gray-200 p-6 shadow-sm' }}">
    @if ($isConnected)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            <strong>Connected.</strong>
            @if ($site?->last_seen_at)
                Last seen {{ $site->last_seen_at->diffForHumans() }}.
            @endif
        </div>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4" @if($site) wire:poll.10s @endif>
            <strong>Waiting for connection.</strong> Install the plugin on your WordPress site — we'll detect it automatically.
        </div>
    @endif

    <h2 class="text-base font-semibold text-gray-900 mb-4">Connect your site</h2>

    <ol class="space-y-5 text-sm text-gray-700">
        <li class="flex gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">1</span>
            <div>
                <p class="font-medium text-gray-900">Install the ReviveGuard plugin</p>
                <p class="mt-1 text-gray-500">In WordPress: Plugins → Add New → Upload, or ask us to install it for you.</p>
                @if ($pluginUrl)
                    <a href="{{ $pluginUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 mt-2 text-brand font-semibold hover:underline">
                        Download plugin (.zip) →
                    </a>
                @endif
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">2</span>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900">Paste your connection code</p>
                <p class="mt-1 text-gray-500">In WordPress: ReviveGuard → Settings → Connection code.</p>
                @if ($connectionToken)
                    <div class="mt-3 flex gap-2" x-data="{ copied: false }">
                        <input type="text" readonly value="{{ $connectionToken }}"
                               class="flex-1 font-mono text-xs border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 truncate"
                               onclick="this.select()" />
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $connectionToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="shrink-0 px-3 py-2 text-xs font-semibold border border-gray-300 rounded-lg hover:bg-gray-50">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied!</span>
                        </button>
                    </div>
                    <p class="text-xs text-amber-700 mt-2">Save this code — it won't be shown again.</p>
                @elseif ($site?->agent_token_last4)
                    <p class="mt-2 text-xs text-gray-500">Your code ends in <span class="font-mono font-semibold">····{{ $site->agent_token_last4 }}</span>. Contact <a href="{{ route('portal.tickets') }}" class="text-brand hover:underline">support</a> for a new code.</p>
                @endif
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">3</span>
            <div>
                <p class="font-medium text-gray-900">Set the platform URL</p>
                <p class="mt-1 text-gray-500">Use this address in the plugin settings:</p>
                <code class="mt-2 inline-block text-xs bg-gray-100 border border-gray-200 rounded px-2 py-1 font-mono">{{ $apiUrl }}</code>
            </div>
        </li>
    </ol>

    <details class="mt-6 text-sm">
        <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">Common questions</summary>
        <div class="mt-3 space-y-2 text-gray-600 pl-1">
            <p><strong>How long does connection take?</strong> Usually under 2 minutes after the plugin is active.</p>
            <p><strong>Is my site data safe?</strong> The connection only sends health signals — not your content or customer data.</p>
            <p><strong>Need help?</strong> <a href="{{ route('portal.tickets') }}" class="text-brand hover:underline">Open a support ticket</a> and we'll connect it for you.</p>
        </div>
    </details>
</div>
