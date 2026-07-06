<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('stripe_hosted_invoice_url', 1000)->nullable()->after('stripe_invoice_id');
            $table->string('reference_key', 120)->nullable()->unique()->after('stripe_hosted_invoice_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['stripe_hosted_invoice_url', 'reference_key']);
        });
    }
};
