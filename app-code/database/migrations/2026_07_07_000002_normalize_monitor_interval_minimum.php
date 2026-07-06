<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $siteIds = DB::table('sites')
            ->join('plans', 'sites.plan_id', '=', 'plans.id')
            ->where('plans.slug', 'monitor')
            ->where('sites.monitor_interval_minutes', 5)
            ->pluck('sites.id');

        if ($siteIds->isNotEmpty()) {
            DB::table('sites')->whereIn('id', $siteIds)->update(['monitor_interval_minutes' => 10]);
        }
    }

    public function down(): void
    {
        // No rollback — 5 min is no longer valid on Monitor.
    }
};
