<?php

namespace App\Livewire\Portal;

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Backups extends Component
{
    public function render(): \Illuminate\View\View
    {
        $client = Auth::guard('client')->user();
        $site   = Site::where('client_id', $client->id)->first();

        $backups = $site
            ? Backup::where('site_id', $site->id)
                ->where('status', BackupStatus::SUCCESS)
                ->orderByDesc('completed_at')
                ->limit(10)
                ->get()
            : collect();

        // Plan-based retention copy from the subscription
        $plan = optional($client->activeSubscription)->plan;

        $retentionCopy = match (optional($plan)->slug) {
            'guard'  => 'Your site is backed up weekly. Files are kept for 90 days.',
            'shield' => 'Your site is backed up daily. Files are kept for 180 days.',
            default  => 'Your site is backed up monthly. Files are kept for 30 days.',
        };

        return view('livewire.portal.backups', [
            'backups'       => $backups,
            'retentionCopy' => $retentionCopy,
        ])->layout('portal.layouts.app');
    }
}
