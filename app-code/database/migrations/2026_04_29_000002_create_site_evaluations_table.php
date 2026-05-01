<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * site_evaluations — tracks Path B (new client) evaluation lifecycle.
 *
 * Status flow:
 *   pending → reviewing → proposed → converted | declined | expired
 *
 * Monthly cap enforced at application level before accepting new evaluations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Prospect info (from public form submission)
            $table->string('prospect_name', 255);
            $table->string('prospect_email', 255);
            $table->string('site_url', 500);
            $table->string('site_type', 50)->default('wordpress');
            // 'wordpress' | 'html' | 'other'
            $table->text('concern')->nullable(); // "What's your biggest concern?"

            // Lifecycle status
            $table->string('status', 30)->default('pending');
            // 'pending' | 'reviewing' | 'proposed' | 'converted' | 'declined' | 'expired'

            // Internal review fields (filled by admin)
            $table->text('admin_notes')->nullable();
            $table->foreignUuid('recommended_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();

            // Proposal (set when admin sends proposal)
            $table->string('proposal_token_hash', 64)->nullable()->unique();
            $table->timestampTz('proposal_sent_at')->nullable();
            $table->timestampTz('proposal_expires_at')->nullable(); // +72h from sent

            // Outcome
            $table->foreignUuid('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestampTz('converted_at')->nullable();
            $table->timestampTz('declined_at')->nullable();
            $table->timestampTz('expired_at')->nullable();

            // Follow-up tracking
            $table->timestampTz('followup_sent_at')->nullable();  // 7-day follow-up
            $table->boolean('waitlisted')->default(false);

            // Month slot for cap enforcement: format 'YYYY-MM'
            $table->string('month_slot', 7)->nullable(); // set when status → 'proposed'

            // Submission metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('referrer_url', 500)->nullable();

            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_evaluations_tenant  ON site_evaluations(tenant_id)');
        DB::statement('CREATE INDEX idx_evaluations_email   ON site_evaluations(prospect_email)');
        DB::statement('CREATE INDEX idx_evaluations_status  ON site_evaluations(status)');
        DB::statement('CREATE INDEX idx_evaluations_month   ON site_evaluations(month_slot)');
        DB::statement('CREATE INDEX idx_evaluations_token   ON site_evaluations(proposal_token_hash)');
    }

    public function down(): void
    {
        Schema::dropIfExists('site_evaluations');
    }
};
