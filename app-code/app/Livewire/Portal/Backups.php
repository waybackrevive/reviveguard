<?php

namespace App\Livewire\Portal;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\Site;
use App\Support\PlanFeatures;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Backups extends Component
{
    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        if (! $client) {
            return view('livewire.portal.backups', $this->emptyState('Please sign in to view backups.'))
                ->layout('portal.layouts.app');
        }

        $siteIds = Site::query()
            ->where('client_id', $client->id)
            ->pluck('id');

        $backups = Backup::query()
            ->whereIn('site_id', $siteIds)
            ->with('site')
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $successCount = $backups->where('status', BackupStatus::SUCCESS)->count();
        $failedCount  = $backups->where('status', BackupStatus::FAILED)->count();
        $latestOk     = $backups->first(fn (Backup $b) => $b->status === BackupStatus::SUCCESS);

        $sites = Site::query()
            ->where('client_id', $client->id)
            ->with(['plan', 'latestBackup'])
            ->orderBy('name')
            ->get()
            ->map(function (Site $site) {
                $features = PlanFeatures::forSite($site);

                return [
                    'site'           => $site,
                    'retention'      => $features->portalRetentionCopy(),
                    'frequency'      => $features->backupFrequencyLabel(),
                    'latest'         => $site->latestBackup,
                    'ready'          => $site->latestBackup?->status === BackupStatus::SUCCESS
                        && $site->latestBackup->completed_at
                        && $site->latestBackup->completed_at->gte(now()->subDays($features->restoreReadinessMaxAgeDays())),
                ];
            });

        $plan = $client->bestSupportPlan();
        $retentionCopy = $plan
            ? PlanFeatures::for($plan)->portalRetentionCopy()
            : 'Your backup schedule depends on your plan.';

        return view('livewire.portal.backups', [
            'backups'       => $backups,
            'sites'         => $sites,
            'retentionCopy' => $retentionCopy,
            'successCount'  => $successCount,
            'failedCount'   => $failedCount,
            'latestOk'      => $latestOk,
        ])->layout('portal.layouts.app');
    }

    /** @return array<string, mixed> */
    private function emptyState(string $message): array
    {
        return [
            'backups'       => collect(),
            'sites'         => collect(),
            'retentionCopy' => $message,
            'successCount'  => 0,
            'failedCount'   => 0,
            'latestOk'      => null,
        ];
    }
}
