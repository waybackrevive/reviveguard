<?php
defined('ABSPATH') || exit;

/**
 * Collects site metadata for the heartbeat payload.
 * Uses native WordPress functions only — no shell commands.
 */
final class ReviveGuard_SiteInfo
{
    /**
     * Build the heartbeat payload.
     *
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        return [
            'wp_version'     => $this->getWpVersion(),
            'php_version'    => PHP_VERSION,
            'theme_name'     => $this->getActiveTheme(),
            'plugin_count'   => $this->getPluginCount(),
            'disk_usage_mb'  => $this->getDiskUsageMb(),
            'debug_mode'     => defined('WP_DEBUG') && WP_DEBUG,
            'site_url'       => get_site_url(),
            'agent_version'  => REVIVEGUARD_VERSION,
        ];
    }

    private function getWpVersion(): string
    {
        global $wp_version;
        return isset($wp_version) ? (string) $wp_version : '';
    }

    private function getActiveTheme(): string
    {
        $theme = wp_get_theme();
        return $theme->get('Name') . ' ' . $theme->get('Version');
    }

    private function getPluginCount(): int
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return count(get_plugins());
    }

    /**
     * Returns disk usage of the WordPress root in MB.
     * Caps at 500 MB scan to avoid timeout on large sites.
     */
    private function getDiskUsageMb(): float
    {
        $bytes = $this->dirSize(ABSPATH, 0, 536870912); // 512 MB cap
        return round($bytes / (1024 * 1024), 2);
    }

    /**
     * Recursively calculates directory size with a byte cap.
     */
    private function dirSize(string $dir, int $accumulated, int $cap): int
    {
        if ($accumulated >= $cap) {
            return $accumulated;
        }

        if (! is_dir($dir)) {
            return $accumulated;
        }

        $handle = @opendir($dir);
        if ($handle === false) {
            return $accumulated;
        }

        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                $accumulated += (int) filesize($path);
            } elseif (is_dir($path) && ! is_link($path)) {
                $accumulated = $this->dirSize($path, $accumulated, $cap);
            }
            if ($accumulated >= $cap) {
                break;
            }
        }

        closedir($handle);
        return $accumulated;
    }
}
