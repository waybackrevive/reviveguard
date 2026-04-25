<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('portal_password', 255)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('timezone', 50)->default('America/New_York');
            // Whop membership reference
            $table->string('whop_member_id', 100)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
