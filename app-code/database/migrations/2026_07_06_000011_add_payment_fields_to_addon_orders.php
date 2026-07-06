<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addon_orders', function (Blueprint $table) {
            $table->unsignedInteger('amount_cents')->nullable()->after('price_label');
            $table->string('stripe_checkout_session_id', 255)->nullable()->after('amount_cents');
            $table->timestampTz('paid_at')->nullable()->after('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('addon_orders', function (Blueprint $table) {
            $table->dropColumn(['amount_cents', 'stripe_checkout_session_id', 'paid_at']);
        });
    }
};
