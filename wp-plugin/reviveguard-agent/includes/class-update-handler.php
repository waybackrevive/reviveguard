<?php
defined('ABSPATH') || exit;

/**
 * Runs WordPress core, plugin, and theme updates.
 *
 * Strategy:
 *   1. Verify a backup was completed in the last 24 hours — defer if not.
 *   2. Try WP-CLI if available (fastest, most reliable).
 *   3. Fall back to WordPress native upgrader API.
 */
final class ReviveGuard_UpdateHandler
{
    private const BACKUP_REQUIRED_SECONDS = 86400; // 24 hours

    /**
     * @return array<string, mixed>
     */
    public function run(string $commandId): array
    {
        $lastBackup = (int) get_option('reviveguard_last_backup', 0);
        if ($lastBackup === 0 || (time() - $lastBackup) > self::BACKUP_REQUIRED_SECONDS) {
            ReviveGuard_DebugLogger::info('Updates deferred — no backup in last 24 hours');
            return [
                'status' => 'deferred',
                'reason' => 'No backup found in the last 24 hours. Run a backup first, then retry updates.',
            ];
        }

        $preBackupPath = (string) get_option('reviveguard_pre_update_backup', '');
        if ($preBackupPath !== '') {
            ReviveGuard_DebugLogger::info('Pre-update backup on file: ' . $preBackupPath);
        }

        if ($this->isWpCliAvailable()) {
            return $this->updateViaWpCli();
        }

        return $this->updateViaWordPressApi();
    }

    /**
     * @return array<string, mixed>
     */
    private function updateViaWpCli(): array
    {
        $results = ['method' => 'wp_cli'];
        $failed  = false;

        $coreOutput   = [];
        $coreExitCode = 0;
        exec('wp core update --allow-root 2>&1', $coreOutput, $coreExitCode);
        $results['core'] = [
            'exit_code' => $coreExitCode,
            'output'    => implode("\n", $coreOutput),
        ];
        if ($coreExitCode !== 0) {
            $failed = true;
        }

        $pluginOutput   = [];
        $pluginExitCode = 0;
        exec('wp plugin update --all --allow-root --format=json 2>&1', $pluginOutput, $pluginExitCode);
        $pluginJson         = json_decode(implode('', $pluginOutput), true);
        $results['plugins'] = is_array($pluginJson) ? $pluginJson : ['raw' => implode("\n", $pluginOutput)];
        if ($pluginExitCode !== 0) {
            $failed = true;
        }

        $themeOutput   = [];
        $themeExitCode = 0;
        exec('wp theme update --all --allow-root 2>&1', $themeOutput, $themeExitCode);
        $results['themes'] = [
            'exit_code' => $themeExitCode,
            'output'    => implode("\n", $themeOutput),
        ];
        if ($themeExitCode !== 0) {
            $failed = true;
        }

        if ($failed) {
            $results['status'] = 'failed';
            $results['error']  = 'One or more WP-CLI update commands returned a non-zero exit code.';

            return $results;
        }

        $results['status'] = 'success';

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function updateViaWordPressApi(): array
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-includes/update.php';

        $results = ['method' => 'wordpress_api', 'plugins' => [], 'themes' => []];
        $failed  = false;

        wp_update_plugins();
        wp_update_themes();
        wp_version_check();

        $pluginUpdates = get_site_transient('update_plugins');
        if (! empty($pluginUpdates->response) && is_array($pluginUpdates->response)) {
            foreach ($pluginUpdates->response as $pluginFile => $updateInfo) {
                $skin     = new Automatic_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $result   = $upgrader->upgrade((string) $pluginFile);
                $results['plugins'][(string) $pluginFile] = $result ? 'updated' : 'failed';
                if (! $result) {
                    $failed = true;
                }
            }
        }

        $themeUpdates = get_site_transient('update_themes');
        if (! empty($themeUpdates->response) && is_array($themeUpdates->response)) {
            foreach (array_keys($themeUpdates->response) as $themeSlug) {
                $skin     = new Automatic_Upgrader_Skin();
                $upgrader = new Theme_Upgrader($skin);
                $result   = $upgrader->upgrade((string) $themeSlug);
                $results['themes'][(string) $themeSlug] = $result ? 'updated' : 'failed';
                if (! $result) {
                    $failed = true;
                }
            }
        }

        if ($failed) {
            $results['status'] = 'failed';
            $results['error']  = 'One or more plugin or theme updates failed.';

            return $results;
        }

        $results['status'] = 'success';

        return $results;
    }

    private function isWpCliAvailable(): bool
    {
        $output   = [];
        $exitCode = 0;
        exec('which wp 2>/dev/null', $output, $exitCode);

        return $exitCode === 0 && ! empty($output);
    }
}
