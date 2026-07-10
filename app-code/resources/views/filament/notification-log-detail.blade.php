<div class="space-y-3 text-sm">
    <div>
        <p class="text-xs font-semibold text-gray-500 uppercase">Recipient</p>
        <p>{{ $record->recipient }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold text-gray-500 uppercase">Type</p>
        <p>{{ $record->type }} · {{ $record->channel }}</p>
    </div>
    <div>
        <p class="text-xs font-semibold text-gray-500 uppercase">Status</p>
        <p class="{{ $record->status === 'failed' ? 'text-danger-600' : 'text-success-600' }}">{{ ucfirst($record->status) }}</p>
    </div>
    @if ($record->error_message)
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase">Error</p>
            <p class="text-danger-600">{{ $record->error_message }}</p>
        </div>
    @endif
    @if ($record->resend_message_id)
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase">Resend message ID</p>
            <p class="font-mono text-xs">{{ $record->resend_message_id }}</p>
        </div>
    @endif
</div>
