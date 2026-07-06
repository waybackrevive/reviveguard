<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('addon_slug', 64);
            $table->string('addon_name', 255);
            $table->string('price_label', 32);
            $table->unsignedInteger('quantity')->default(1);
            $table->text('client_notes')->nullable();
            $table->string('status', 32)->default('pending');
            // pending | in_progress | completed | cancelled
            $table->text('team_update')->nullable();
            $table->timestampTz('team_updated_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['client_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_orders');
    }
};
