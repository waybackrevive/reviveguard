<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_login_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_login_tokens');
    }
};
