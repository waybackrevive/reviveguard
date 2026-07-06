<?php

namespace App\Http\Controllers\Agent;

use App\Enums\CommandStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'agent_version' => ['nullable', 'string', 'max:20'],
            'wp_version'    => ['nullable', 'string', 'max:20'],
            'php_version'   => ['nullable', 'string', 'max:20'],
            'disk_usage_mb' => ['nullable', 'numeric', 'min:0'],
            'debug_mode'    => ['nullable'],
            'plugin_count'  => ['nullable', 'integer', 'min:0'],
            'theme_name'    => ['nullable', 'string', 'max:255'],
            'site_url'      => ['nullable', 'url', 'max:500'],
        ]);

        // Dispatch async job to process status changes and emit events
        ProcessHeartbeat::dispatchSync($site->id, $validated);

        // Fetch pending command (already loaded via HasOne relation)
        $pending = $site->pendingCommand;

        if ($pending) {
            // Mark as sent so it isn't double-dispatched
            $pending->update([
                'status'  => CommandStatus::SENT,
                'sent_at' => now(),
            ]);

            return response()->json([
                'status'     => 'ok',
                'command'    => $pending->type->value,
                'params'     => $pending->params ?? [],
                'command_id' => $pending->id,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
