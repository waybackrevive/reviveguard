<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds new onboarding-model fields to clients table.
 *
 * path:
 *   'alumni'     — WaybackRevive restored client, onboarded via admin invite
 *   'evaluation' — new client, went through evaluation flow
 *   'legacy'     — clients created before invite system (old Whop auto-create path)
 *
 * source: human-readable acquisition source for reporting
 * onboarding_completed_at: set when client completes the portal wizard
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('path', 30)->default('legacy')->after('is_active');
            // 'alumni' | 'evaluation' | 'legacy'

            $table->string('source', 100)->nullable()->after('path');
            // 'waybackrevive_restored' | 'inbound' | 'referral' | null

            $table->timestampTz('onboarding_completed_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['path', 'source', 'onboarding_completed_at']);
        });
    }
};
