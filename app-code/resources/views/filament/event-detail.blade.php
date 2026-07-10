<div class="space-y-4 text-sm">
    <div>
        <p class="font-medium text-gray-500 dark:text-gray-400">Message</p>
        <p class="mt-1 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $event->message ?: 'No additional details.' }}</p>
    </div>
    <div>
        <p class="font-medium text-gray-500 dark:text-gray-400">Type / Severity</p>
        <p class="mt-1 text-gray-900 dark:text-white">{{ $event->type }} · {{ $event->severity instanceof \App\Enums\EventSeverity ? $event->severity->value : $event->severity }}</p>
    </div>
    @if (! empty($event->metadata))
        <div>
            <p class="font-medium text-gray-500 dark:text-gray-400">Metadata</p>
            <pre class="mt-1 overflow-x-auto rounded bg-gray-50 p-3 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
