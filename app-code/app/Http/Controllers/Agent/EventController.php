<?php

namespace App\Http\Controllers\Agent;

use App\Enums\EventSeverity;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'type'     => ['required', 'string', 'max:100'],
            'severity' => ['required', 'in:success,info,warning,critical'],
            'title'    => ['required', 'string', 'max:500'],
            'message'  => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        Event::create([
            'tenant_id' => $site->tenant_id,
            'site_id'   => $site->id,
            'type'      => $validated['type'],
            'severity'  => EventSeverity::from($validated['severity']),
            'title'     => $validated['title'],
            'message'   => $validated['message'] ?? null,
            'metadata'  => $validated['metadata'] ?? [],
        ]);

        return response()->json(['status' => 'ok']);
    }
}
