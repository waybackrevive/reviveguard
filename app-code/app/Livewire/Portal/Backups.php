<?php

namespace App\Livewire\Portal;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\Site;
use App\Support\PlanFeatures;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Backups extends Component
{
    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();

        if (!$client) {
            return view('livewire.portal.backups', [
                'backups' => collect(),
                'retentionCopy' => 'Please sign in to view backups.',
            ])->layout('portal.layouts.app');
        }

        if (!Schema::hasTable('sites') || !Schema::hasTable('backups')) {
            return view('livewire.portal.backups', [
                'backups' => collect(),
                'retentionCopy' => 'Backups are being initialized. Please check again shortly or contact support.',
            ])->layout('portal.layouts.app');
        }

        try {
            $site = Site::where('client_id', $client->id)->first();

            $backups = $site
                ? Backup::where('site_id', $site->id)
                    ->where('status', BackupStatus::SUCCESS)
                    ->orderByDesc('completed_at')
                    ->limit(10)
                    ->get()
                : collect();
        } catch (QueryException $e) {
            Log::warning('Portal backups query failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            $backups = collect();
        }

        // Plan-based retention copy — use best paid plan across sites (not legacy single subscription)
        $plan = $client->bestSupportPlan();

        $retentionCopy = $plan
            ? PlanFeatures::for($plan)->portalRetentionCopy()
            : 'Your backup schedule depends on your plan.';

        return view('livewire.portal.backups', [
            'backups'       => $backups,
            'retentionCopy' => $retentionCopy,
        ])->layout('portal.layouts.app');
    }
}
