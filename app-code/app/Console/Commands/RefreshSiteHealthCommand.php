<?php

namespace App\Console\Commands;

use App\Jobs\RefreshSiteHealthJob;
use App\Models\Site;
use Illuminate\Console\Command;

class RefreshSiteHealthCommand extends Command
{
    protected $signature = 'sites:refresh-health {site? : Site UUID to refresh (optional — all protected sites if omitted)}';

    protected $description = 'Run SSL, domain, and uptime checks for protected sites';

    public function handle(): int
    {
        $siteId = $this->argument('site');

        $query = Site::protected()->whereNotNull('url');

        if ($siteId) {
            $query->where('id', $siteId);
        }

        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->warn('No protected sites with a URL found.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            RefreshSiteHealthJob::dispatchSync($site->id);
            $this->line("Refreshed: {$site->displayName()}");
        }

        $this->info('Health refresh complete.');

        return self::SUCCESS;
    }
}
