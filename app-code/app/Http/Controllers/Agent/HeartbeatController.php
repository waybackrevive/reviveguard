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
            'disk_usage_mb' => ['nullable', 'numeric', 'min:0'],  // plugin sends float
            'debug_mode'    => ['nullable', 'boolean'],
            'plugin_count'  => ['nullable', 'integer', 'min:0'],
            'theme_name'    => ['nullable', 'string', 'max:255'],
            'active_theme'  => ['nullable', 'string', 'max:255'],  // plugin field name
        ]);

        // Normalise: plugin sends active_theme, DB column is theme_name
        if (empty($validated['theme_name']) && ! empty($validated['active_theme'])) {
            $validated['theme_name'] = $validated['active_theme'];
        }
        unset($validated['active_theme']);

        // DB column is integer — cast before dispatch
        if (isset($validated['disk_usage_mb'])) {
            $validated['disk_usage_mb'] = (int) $validated['disk_usage_mb'];
        }

        // Dispatch async job to process status changes and emit events
        ProcessHeartbeat::dispatch($site->id, $validated);

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
