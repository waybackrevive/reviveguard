<?php

namespace App\Jobs;

use App\Services\ContentHoursService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Reset Shield content-edit minutes at the start of each month. */
final class ResetShieldContentHours implements ShouldQueue
{
    use Queueable;

    public function handle(ContentHoursService $hours): void
    {
        $count = $hours->resetAllShieldClients();

        Log::info('ResetShieldContentHours: reset clients', ['count' => $count]);
    }
}
