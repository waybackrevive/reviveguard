<?php

namespace App\Support;

use App\Models\Plan;

final class SupportTier
{
    public static function forPlan(?Plan $plan): array
    {
        $slug = $plan?->slug ?? 'monitor';

        return match ($slug) {
            'shield' => [
                'email'        => true,
                'email_limit'  => null,
                'phone'        => true,
                'reply_sla'    => 'Priority — same business day',
                'headline'     => 'Email & phone support with priority response.',
            ],
            'guard' => [
                'email'        => true,
                'email_limit'  => null,
                'phone'        => true,
                'reply_sla'    => 'Within 24 hours on business days',
                'headline'     => 'Unlimited email support plus phone assistance.',
            ],
            default => [
                'email'        => true,
                'email_limit'  => null,
                'phone'        => false,
                'reply_sla'    => 'Within 24 hours on business days',
                'headline'     => 'Unlimited email support — we reply within 24 hours.',
            ],
        };
    }

    public static function canSubmitTicket(?Plan $plan): bool
    {
        return self::forPlan($plan)['email'];
    }

    public static function ticketLimitReached(?Plan $plan, int $usedThisMonth): bool
    {
        $limit = $plan?->support_tickets_per_month ?? -1;

        if ($limit < 0) {
            return false;
        }

        return $usedThisMonth >= $limit;
    }
}
