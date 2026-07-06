<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $prices = [
            'monitor' => 49.00,
            'guard'   => 99.00,
            'shield'  => 179.00,
        ];

        foreach ($prices as $slug => $price) {
            Plan::where('slug', $slug)->update(['price_monthly' => $price]);
        }
    }

    public function down(): void
    {
        // Legacy prices before marketing alignment — do not restore in production.
    }
};
