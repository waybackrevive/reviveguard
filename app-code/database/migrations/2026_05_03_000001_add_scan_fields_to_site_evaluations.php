<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds external scan + plugin report columns to site_evaluations.
 *
 * scan_status:    'pending' | 'running' | 'done' | 'failed'
 * scan_results:   JSON from ExternalScanService (SSL, WHOIS, HTTP, headers, CMS detect)
 * plugin_report:  JSON decoded from the WP health-check plugin report token
 * report_token_hash: SHA-256 of the raw token (used for idempotency check)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            // External scan
            $table->string('scan_status', 20)->default('pending')->after('referrer_url');
            $table->jsonb('scan_results')->nullable()->after('scan_status');
            $table->timestampTz('scan_ran_at')->nullable()->after('scan_results');

            // WP health-check plugin report
            $table->jsonb('plugin_report')->nullable()->after('scan_ran_at');
            $table->string('report_token_hash', 64)->nullable()->unique()->after('plugin_report');
            $table->timestampTz('plugin_report_at')->nullable()->after('report_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('site_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'scan_status', 'scan_results', 'scan_ran_at',
                'plugin_report', 'report_token_hash', 'plugin_report_at',
            ]);
        });
    }
};
