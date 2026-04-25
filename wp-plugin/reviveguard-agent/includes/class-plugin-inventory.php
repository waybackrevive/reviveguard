<?php
defined('ABSPATH') || exit;

/**
 * Collects the installed plugin list and detects changes via checksum.
 * Only sends to the API when the list has actually changed — avoids noise.
 */
final class ReviveGuard_PluginInventory
{
    private const CHECKSUM_OPTION = 'reviveguard_plugin_checksum';

    /**
     * Collect all installed plugins with metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collect(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Force fresh update check data
        wp_update_plugins();
        $updateData = get_site_transient('update_plugins');

        $allPlugins     = get_plugins();
        $activePlugins  = get_option('active_plugins', []);
        $result         = [];

        foreach ($allPlugins as $pluginFile => $pluginData) {
            $result[] = [
                'name'             => (string) ($pluginData['Name']    ?? ''),
                'slug'             => dirname($pluginFile),
                'version'          => (string) ($pluginData['Version'] ?? ''),
                'active'           => in_array($pluginFile, (array) $activePlugins, true),
                'update_available' => isset($updateData->response[$pluginFile]),
                'update_version'   => $updateData->response[$pluginFile]->new_version ?? null,
            ];
        }

        return $result;
    }

    /**
     * Returns true if the plugin list has changed since the last checksum was saved.
     */
    public function hasChanged(): bool
    {
        $plugins         = $this->collect();
        $currentChecksum = $this->buildChecksum($plugins);
        $savedChecksum   = (string) get_option(self::CHECKSUM_OPTION, '');

        return $currentChecksum !== $savedChecksum;
    }

    /**
     * Persist the checksum of the given plugin list.
     *
     * @param array<int, array<string, mixed>> $plugins
     */
    public function saveChecksum(array $plugins): void
    {
        update_option(self::CHECKSUM_OPTION, $this->buildChecksum($plugins));
    }

    /**
     * @param array<int, array<string, mixed>> $plugins
     */
    private function buildChecksum(array $plugins): string
    {
        // Sort by name so checksum is stable regardless of plugin load order
        usort($plugins, static function (array $a, array $b): int {
            return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
        });

        return md5((string) wp_json_encode($plugins));
    }
}
