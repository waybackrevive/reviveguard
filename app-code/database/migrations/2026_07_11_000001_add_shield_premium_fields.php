<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->foreignUuid('account_manager_id')
                ->nullable()
                ->after('timezone')
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedSmallInteger('content_minutes_remaining')
                ->nullable()
                ->after('account_manager_id');

            $table->timestampTz('content_minutes_reset_at')
                ->nullable()
                ->after('content_minutes_remaining');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('type', 50)
                ->default('general')
                ->after('priority');

            $table->timestampTz('sla_due_at')
                ->nullable()
                ->after('type');

            $table->unsignedSmallInteger('minutes_billed')
                ->nullable()
                ->after('sla_due_at');

            $table->index(['status', 'sla_due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['status', 'sla_due_at']);
            $table->dropColumn(['type', 'sla_due_at', 'minutes_billed']);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_manager_id');
            $table->dropColumn(['content_minutes_remaining', 'content_minutes_reset_at']);
        });
    }
};
