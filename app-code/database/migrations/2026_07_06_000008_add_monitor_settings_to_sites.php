<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->unsignedSmallInteger('monitor_interval_minutes')->default(5)->after('uptime_kuma_monitor_id');
            $table->string('monitor_region', 32)->default('us-east')->after('monitor_interval_minutes');
            $table->timestamp('last_uptime_probe_at')->nullable()->after('monitor_region');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['monitor_interval_minutes', 'monitor_region', 'last_uptime_probe_at']);
        });
    }
};
