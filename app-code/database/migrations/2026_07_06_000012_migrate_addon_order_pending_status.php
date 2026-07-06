<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('addon_orders')) {
            return;
        }

        DB::table('addon_orders')
            ->where('status', 'pending')
            ->whereNull('paid_at')
            ->update(['status' => 'awaiting_payment']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('addon_orders')) {
            return;
        }

        DB::table('addon_orders')
            ->where('status', 'awaiting_payment')
            ->whereNull('paid_at')
            ->update(['status' => 'pending']);
    }
};
