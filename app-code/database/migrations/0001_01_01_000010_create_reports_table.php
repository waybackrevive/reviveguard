<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('type', 50)->default('monthly'); // monthly | manual
            $table->string('period', 20); // e.g. '2025-01'
            $table->string('status', 50)->default('pending');
            // Values: pending | generating | ready | failed
            $table->string('b2_file_key', 1000)->nullable(); // B2 path for PDF
            $table->string('b2_bucket', 255)->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestampTz('email_sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['client_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
