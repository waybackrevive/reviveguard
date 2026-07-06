<?php

namespace App\Support;

use App\Models\Client;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Portal display timezone — stored UTC in DB, shown in client preference.
 */
final class ClientTimezone
{
    public const DEFAULT = 'America/New_York';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'America/New_York'    => 'US Eastern — New York (ET)',
            'America/Chicago'     => 'US Central — Chicago (CT)',
            'America/Denver'      => 'US Mountain — Denver (MT)',
            'America/Los_Angeles' => 'US Pacific — Los Angeles (PT)',
            'America/Phoenix'     => 'US Arizona (MST — no DST)',
            'Europe/London'       => 'United Kingdom — London (GMT/BST)',
            'Europe/Paris'        => 'Central Europe — Paris (CET)',
            'Asia/Dubai'          => 'Gulf — Dubai (GST)',
            'Asia/Karachi'        => 'Pakistan — Karachi (PKT)',
            'Asia/Kolkata'        => 'India — Mumbai/Delhi (IST)',
            'Asia/Singapore'      => 'Singapore (SGT)',
            'Australia/Sydney'    => 'Australia — Sydney (AEST)',
            'UTC'                 => 'UTC (Coordinated Universal Time)',
        ];
    }

    public static function resolve(?Client $client): string
    {
        $tz = $client?->timezone;

        if ($tz && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        return self::DEFAULT;
    }

    public static function format(?Client $client, CarbonInterface $at, string $pattern = 'M j, Y g:i A'): string
    {
        return $at->copy()->timezone(self::resolve($client))->format($pattern);
    }

    public static function formatWithAbbr(?Client $client, CarbonInterface $at, string $pattern = 'M j, Y g:i A'): string
    {
        $local = $at->copy()->timezone(self::resolve($client));

        return $local->format($pattern) . ' ' . $local->format('T');
    }

    /** e.g. "US Eastern — New York (ET)" */
    public static function label(?Client $client): string
    {
        $tz      = self::resolve($client);
        $options = self::options();

        if (isset($options[$tz])) {
            return $options[$tz];
        }

        $abbr = now()->timezone($tz)->format('T');

        return str_replace('_', ' ', $tz) . " ({$abbr})";
    }

    public static function abbreviation(?Client $client): string
    {
        return now()->timezone(self::resolve($client))->format('T');
    }
}
