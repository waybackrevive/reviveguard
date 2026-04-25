<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('type', 100);
            // e.g. site_down, ssl_expiry_warning, welcome, monthly_report_ready
            $table->string('channel', 50)->default('email'); // email only
            $table->string('recipient', 255);    // email address
            $table->string('subject', 500)->nullable();
            $table->string('status', 50)->default('sent');
            // Values: sent | failed
            $table->string('error_message', 500)->nullable();
            $table->string('resend_message_id', 255)->nullable();
            $table->timestampsTz();

            $table->index(['client_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
