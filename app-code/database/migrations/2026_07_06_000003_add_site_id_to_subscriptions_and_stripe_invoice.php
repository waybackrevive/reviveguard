<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignUuid('site_id')->nullable()->after('plan_id')->constrained('sites')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('stripe_invoice_id', 100)->nullable()->unique()->after('whop_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('stripe_invoice_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });
    }
};
