<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('type', 100);
            // Examples: site_down, site_recovered, ssl_expiry_warning,
            //           domain_expiry_warning, backup_complete, backup_failed,
            //           update_complete, heartbeat_missed, uptime_kuma_alert
            $table->string('severity', 20)->default('info');
            // Values: success | info | warning | critical
            $table->string('title', 500);
            $table->text('message')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->boolean('resolved')->default(false);
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index(['site_id', 'created_at']);
            $table->index(['tenant_id', 'severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
