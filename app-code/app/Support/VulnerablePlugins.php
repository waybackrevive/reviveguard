<?php

namespace App\Support;

/**
 * Known vulnerable WordPress plugin slugs and maximum safe versions (v1 local list).
 *
 * @phpstan-type VulnEntry array{max_vulnerable: string, note: string}
 */
final class VulnerablePlugins
{
    /** @return array<string, VulnEntry> */
    public static function list(): array
    {
        return [
            'wp-file-manager'     => ['max_vulnerable' => '6.8', 'note' => 'Remote code execution risk in older versions'],
            'elementor'           => ['max_vulnerable' => '3.11.0', 'note' => 'Known XSS/CVE in older releases — update recommended'],
            'woocommerce'         => ['max_vulnerable' => '7.0.0', 'note' => 'Security patches in newer versions'],
            'contact-form-7'      => ['max_vulnerable' => '5.6', 'note' => 'Spam/reflection issues in older builds'],
            'all-in-one-wp-migration' => ['max_vulnerable' => '7.62', 'note' => 'Arbitrary file download risk in older versions'],
            'revslider'           => ['max_vulnerable' => '6.5.0', 'note' => 'Historical RCE vulnerabilities'],
            'wpforms-lite'        => ['max_vulnerable' => '1.7.8', 'note' => 'Update recommended for security fixes'],
        ];
    }

    /**
     * @return list<array{slug: string, version: string, note: string}>
     */
    public static function matchInstalled(string $slug, string $version): array
    {
        $list = self::list();

        if (! isset($list[$slug])) {
            return [];
        }

        $entry = $list[$slug];

        if (version_compare($version, $entry['max_vulnerable'], '<=')) {
            return [[
                'slug'    => $slug,
                'version' => $version,
                'note'    => $entry['note'],
            ]];
        }

        return [];
    }
}
