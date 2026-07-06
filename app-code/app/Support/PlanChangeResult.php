<?php

namespace App\Support;

use App\Models\Subscription;
use Carbon\Carbon;

readonly class PlanChangeResult
{
    public function __construct(
        public Subscription $subscription,
        public bool $isUpgrade,
        public ?int $chargedCents = null,
        public ?Carbon $nextBillingAt = null,
        public ?string $stripeInvoiceId = null,
    ) {}

    public function successMessage(string $planName): string
    {
        $parts = ["Switched to {$planName}."];

        if ($this->chargedCents !== null && $this->chargedCents > 0) {
            $parts[] = '$' . number_format($this->chargedCents / 100, 2) . ' charged to your card today (prorated).';
        } elseif (! $this->isUpgrade) {
            $parts[] = 'Unused time on your previous plan is credited toward your next bill.';
        }

        if ($this->nextBillingAt) {
            $parts[] = 'Next billing: ' . $this->nextBillingAt->format('M j, Y') . '.';
        }

        $parts[] = 'Receipt is in Billing & Invoices.';

        return implode(' ', $parts);
    }
}
