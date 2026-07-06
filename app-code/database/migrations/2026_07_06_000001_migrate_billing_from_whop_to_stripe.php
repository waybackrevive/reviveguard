<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_price_id', 100)->nullable()->after('price_monthly');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('stripe_id', 100)->nullable()->unique()->after('email');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_subscription_id', 100)->nullable()->unique()->after('plan_id');
            $table->string('stripe_status', 50)->nullable()->after('stripe_subscription_id');
            $table->timestampTz('stripe_current_period_end')->nullable()->after('stripe_status');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['stripe_subscription_id', 'stripe_status', 'stripe_current_period_end']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('stripe_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('stripe_price_id');
        });
    }
};
