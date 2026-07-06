<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('monitoring_paused')->default(false)->after('monitor_region');
            $table->timestamp('monitoring_paused_at')->nullable()->after('monitoring_paused');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['monitoring_paused', 'monitoring_paused_at']);
        });
    }
};
