<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * client_invites — signed invite tokens.
 *
 * The ONLY mechanism for onboarding a client. No public sign-up exists.
 *
 * Admin creates invite → system generates random_bytes(32) plain token →
 * stores SHA-256 hash only → emails client the plain token in a URL →
 * client clicks → token hashed → matched → account activated.
 *
 * path values:
 *   'alumni'     — WaybackRevive restored client (proactive outreach)
 *   'evaluation' — new client approved after evaluation review
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_invites', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Prospect details (pre-seeded from WaybackRevive or evaluation)
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('site_url', 500)->nullable(); // pre-fill wizard step 1

            // Path — set by admin, NEVER by the user
            $table->string('path', 30)->default('alumni');
            // 'alumni' | 'evaluation'

            // Link to evaluation (Path B only) — no FK constraint, site_evaluations may not exist yet
            $table->uuid('evaluation_id')->nullable();

            // Created client (set after invite is accepted)
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();

            // Token security: store only SHA-256 hash, never the plain token
            $table->string('token_hash', 64)->unique();
            $table->timestampTz('expires_at');          // default: NOW() + 72h (set in service)
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('email_sent_at')->nullable();

            // Admin
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampsTz();
        });

        // Indexes
        DB::statement('CREATE INDEX idx_invites_tenant   ON client_invites(tenant_id)');
        DB::statement('CREATE INDEX idx_invites_email    ON client_invites(email)');
        DB::statement('CREATE INDEX idx_invites_token    ON client_invites(token_hash)');
        DB::statement('CREATE INDEX idx_invites_path     ON client_invites(path)');
        DB::statement('CREATE INDEX idx_invites_eval     ON client_invites(evaluation_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('client_invites');
    }
};
