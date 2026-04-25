<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            // Whop fields (replaces Stripe/Cashier fields)
            $table->string('whop_membership_id', 100)->nullable()->unique();
            $table->string('whop_plan_id', 100)->nullable();
            $table->string('whop_status', 50)->default('pending');
            // Values: 'pending', 'active', 'past_due', 'cancelled', 'paused'
            $table->timestampTz('whop_valid_until')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
