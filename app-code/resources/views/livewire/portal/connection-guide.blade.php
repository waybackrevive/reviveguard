<div class="{{ $compact ? '' : 'bg-white rounded-[10px] border border-gray-200 p-6 shadow-sm' }}">
    @if ($isConnected)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-5">
            <strong>Plugin connected.</strong>
            @if ($site?->last_seen_at)
                Last heartbeat {{ $site->last_seen_at->diffForHumans() }}.
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 mb-5 text-sm">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Plugin version</p>
                <p class="font-medium text-gray-900 mt-1">{{ $site?->agent_version ?? 'Unknown' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">WordPress</p>
                <p class="font-medium text-gray-900 mt-1">{{ $site?->wp_version ? 'WP ' . $site->wp_version : '—' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-5">
            <a href="{{ $pluginUrl }}" download="reviveguard-agent.zip"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-sm font-semibold text-gray-700 rounded-lg hover:bg-gray-50">
                Download latest plugin
            </a>
            @if ($canOpenWpAdmin ?? false)
                <span class="text-xs text-gray-500 self-center">Use <strong>WP Admin</strong> in the header for one-click login.</span>
            @endif
        </div>

        <details class="text-sm border border-gray-200 rounded-lg">
            <summary class="cursor-pointer font-medium text-gray-700 px-4 py-3 hover:bg-gray-50 rounded-lg">Reinstall or move hosts?</summary>
            <div class="px-4 pb-4 pt-1 text-gray-600 space-y-3 border-t border-gray-100">
                <p>If you lost your connection code, regenerate it from the Plan tab, then paste the new code in WordPress → ReviveGuard → Settings.</p>
                <p>Platform URL: <code class="text-xs bg-gray-100 px-2 py-0.5 rounded font-mono">{{ $apiUrl }}</code></p>
            </div>
        </details>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4" @if($site) wire:poll.10s @endif>
            <strong>Waiting for connection.</strong> Follow the steps below — we detect the plugin automatically (usually under 2 minutes).
        </div>

        <h2 class="text-base font-semibold text-gray-900 mb-1">Connect your WordPress site</h2>
        <p class="text-sm text-gray-500 mb-5">No support ticket needed. Do these three steps in order.</p>

        <ol class="space-y-5 text-sm text-gray-700">
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">1</span>
                <div>
                    <p class="font-medium text-gray-900">Download &amp; install the plugin</p>
                    <p class="mt-1 text-gray-500">Download the zip, then in WordPress go to <strong>Plugins → Add New → Upload Plugin</strong> and activate it.</p>
                    <a href="{{ $pluginUrl }}" download="reviveguard-agent.zip"
                       class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand-dark transition-colors">
                        Download ReviveGuard plugin (.zip)
                    </a>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">2</span>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900">Paste your connection code</p>
                    <p class="mt-1 text-gray-500">In WordPress: <strong>ReviveGuard → Settings</strong> → paste the code below → Save.</p>
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
                        <p class="text-xs text-amber-700 mt-2">Save this code now — it won't be shown again.</p>
                    @elseif ($site?->agent_token_last4)
                        <p class="mt-2 text-xs text-gray-500">Your code ends in <span class="font-mono font-semibold">····{{ $site->agent_token_last4 }}</span>. Use <strong>Regenerate code</strong> on the Plan tab if you lost it.</p>
                    @endif
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white text-xs font-bold">3</span>
                <div>
                    <p class="font-medium text-gray-900">Set the platform URL</p>
                    <p class="mt-1 text-gray-500">In the same plugin settings, set Platform URL to:</p>
                    <code class="mt-2 inline-block text-xs bg-gray-100 border border-gray-200 rounded px-2 py-1 font-mono select-all">{{ $apiUrl }}</code>
                </div>
            </li>
        </ol>

        @if ($site)
            <p class="mt-5 text-xs text-gray-500 border-t border-gray-100 pt-4">
                We check for the plugin every few seconds. Once connected, this page updates automatically.
            </p>
        @endif
    @endif

    <details class="mt-4 text-sm">
        <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">Common questions</summary>
        <div class="mt-3 space-y-2 text-gray-600 pl-1">
            <p><strong>Is the plugin on WordPress.org?</strong> Not yet — download it from the button above.</p>
            <p><strong>Can I pay before connecting?</strong> Yes. Connection and payment are separate steps.</p>
            <p><strong>Is my site data safe?</strong> The plugin only sends health signals — not your content or customer data.</p>
        </div>
    </details>
</div>
