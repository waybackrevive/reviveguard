<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('workspace_name', 150)->nullable()->after('company_name');
            $table->string('account_type', 30)->nullable()->after('workspace_name');
            $table->string('sites_managed_range', 30)->nullable()->after('account_type');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('client_label', 150)->nullable()->after('name');
        });

        // Existing portal users skip welcome wizard (already using the product).
        if (Schema::hasTable('clients')) {
            \Illuminate\Support\Facades\DB::table('clients')
                ->whereNull('onboarding_completed_at')
                ->where(function ($q) {
                    $q->whereNotNull('last_login_at')
                        ->orWhereExists(function ($sub) {
                            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('sites')
                                ->whereColumn('sites.client_id', 'clients.id');
                        });
                })
                ->update([
                    'onboarding_completed_at' => now(),
                    'workspace_name'          => \Illuminate\Support\Facades\DB::raw('COALESCE(workspace_name, company_name, name)'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('client_label');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['workspace_name', 'account_type', 'sites_managed_range']);
        });
    }
};
