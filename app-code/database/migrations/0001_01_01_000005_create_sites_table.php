<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            // Identity
            $table->string('name', 255);
            $table->string('url', 500);
            $table->string('type', 50)->default('wordpress'); // wordpress | html | other

            // Agent token (stored as SHA-256 hash)
            $table->string('agent_token', 64)->unique()->nullable();
            $table->string('agent_token_last4', 4)->nullable();
            $table->string('agent_version', 50)->nullable();
            $table->timestampTz('agent_installed_at')->nullable();

            // Status
            $table->string('status', 50)->default('pending');
            // Values: pending | active | down | warning | suspended
            $table->timestampTz('last_seen_at')->nullable();

            // Uptime Kuma
            $table->integer('uptime_kuma_monitor_id')->nullable();
            $table->decimal('uptime_30d', 5, 2)->nullable();
            $table->decimal('uptime_7d', 5, 2)->nullable();

            // SSL
            $table->date('ssl_expires_at')->nullable();
            $table->string('ssl_issuer', 255)->nullable();
            $table->boolean('ssl_valid')->nullable();

            // Domain
            $table->date('domain_expires_at')->nullable();
            $table->string('registrar', 255)->nullable();

            // WordPress / CMS metadata
            $table->string('wp_version', 50)->nullable();
            $table->string('php_version', 50)->nullable();
            $table->integer('plugin_count')->nullable();
            $table->string('theme_name', 255)->nullable();
            $table->integer('disk_usage_mb')->nullable();
            $table->boolean('debug_mode')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
