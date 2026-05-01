<div class="text-sm text-gray-600 space-y-1">
    <p class="mb-2">The plain token was sent in the invite email. To send a new link, use the <strong>Resend</strong> action.</p>
    <p class="text-xs text-gray-400">Invite ID: {{ $invite->id }}</p>
    <p class="text-xs text-gray-400">Status: {{ $invite->status_label }}</p>
    <p class="text-xs text-gray-400">Expires: {{ $invite->expires_at?->format('M j, Y g:i A') ?? 'N/A' }} UTC</p>
</div>
