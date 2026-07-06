<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_test_price_id', 100)->nullable()->after('stripe_price_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('stripe_test_id', 100)->nullable()->unique()->after('stripe_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('stripe_test_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('stripe_test_price_id');
        });
    }
};
