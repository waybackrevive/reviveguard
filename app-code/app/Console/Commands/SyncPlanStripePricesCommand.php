<?php

namespace App\Console\Commands;

use App\Support\PlanStripePriceSync;
use Illuminate\Console\Command;

class SyncPlanStripePricesCommand extends Command
{
    protected $signature = 'plans:sync-stripe-prices';

    protected $description = 'Sync Stripe price IDs from config into the plans table (safe after config:cache)';

    public function handle(): int
    {
        $updated = PlanStripePriceSync::syncFromConfig();

        if ($updated === 0) {
            $this->info('Plan Stripe prices are already in sync (or config values are missing).');

            return self::SUCCESS;
        }

        $this->info("Updated Stripe price IDs on {$updated} plan(s).");

        return self::SUCCESS;
    }
}
