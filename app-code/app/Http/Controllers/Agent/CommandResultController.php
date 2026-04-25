<?php

namespace App\Http\Controllers\Agent;

use App\Enums\CommandStatus;
use App\Http\Controllers\Controller;
use App\Models\SiteCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandResultController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'command_id' => ['required', 'uuid'],
            'status'     => ['required', 'in:success,failed'],
            'result'     => ['nullable', 'array'],
            'error'      => ['nullable', 'string', 'max:2000'],
        ]);

        $command = SiteCommand::where('id', $validated['command_id'])
            ->where('site_id', $site->id)
            ->first();

        if (! $command) {
            return response()->json(['error' => 'Command not found'], 404);
        }

        $command->update([
            'status'        => $validated['status'] === 'success'
                ? CommandStatus::SUCCESS
                : CommandStatus::FAILED,
            'result'        => $validated['result'] ?? null,
            'error_message' => $validated['error'] ?? null,
            'completed_at'  => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
