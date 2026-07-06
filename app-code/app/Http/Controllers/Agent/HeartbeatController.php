<?php

namespace App\Http\Controllers\Agent;

use App\Enums\CommandStatus;
use App\Http\Controllers\Controller;
use App\Models\SiteCommand;
use App\Services\AgentHeartbeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request, AgentHeartbeatService $heartbeat): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'agent_version' => ['nullable', 'string', 'max:20'],
            'wp_version'    => ['nullable', 'string', 'max:20'],
            'php_version'   => ['nullable', 'string', 'max:20'],
            'disk_usage_mb' => ['nullable', 'numeric', 'min:0'],
            'debug_mode'    => ['nullable', 'boolean'],
            'plugin_count'  => ['nullable', 'integer', 'min:0'],
            'theme_name'    => ['nullable', 'string', 'max:255'],
            'site_url'      => ['nullable', 'string', 'max:500'],
        ]);

        $site = $heartbeat->record($site, $validated);

        $pending = SiteCommand::where('site_id', $site->id)
            ->where('status', CommandStatus::PENDING)
            ->orderBy('created_at')
            ->first();

        if ($pending) {
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
