<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\PluginSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\Site $site */
        $site = $request->attributes->get('site');

        $validated = $request->validate([
            'plugins'           => ['required', 'array'],
            'plugins.*.name'    => ['required', 'string', 'max:255'],
            'plugins.*.version' => ['nullable', 'string', 'max:30'],
            'plugins.*.active'  => ['required', 'boolean'],
            'plugins.*.update_available' => ['nullable', 'boolean'],
        ]);

        $plugins = $validated['plugins'];

        $total            = count($plugins);
        $active           = collect($plugins)->where('active', true)->count();
        $inactive         = $total - $active;
        $updatesAvailable = collect($plugins)->where('update_available', true)->count();

        PluginSnapshot::create([
            'tenant_id'        => $site->tenant_id,
            'site_id'          => $site->id,
            'plugins'          => $plugins,
            'total'            => $total,
            'active'           => $active,
            'inactive'         => $inactive,
            'updates_available' => $updatesAvailable,
        ]);

        // Keep only the last 5 snapshots per site to avoid unbounded growth
        PluginSnapshot::where('site_id', $site->id)
            ->orderByDesc('created_at')
            ->skip(5)
            ->take(PHP_INT_MAX)
            ->delete();

        return response()->json(['status' => 'ok', 'received' => $total]);
    }
}
