<?php
defined('ABSPATH') || exit;

/**
 * Restores a site from a Backblaze B2 backup archive (rollback_restore command).
 */
final class ReviveGuard_RestoreHandler
{
    /**
     * @param  array<string, mixed> $params  Must include b2_path; optional b2_bucket
     * @return array<string, mixed>
     */
    public function run(string $commandId, array $params): array
    {
        $b2Path = (string) ($params['b2_path'] ?? '');
        if ($b2Path === '') {
            return ['status' => 'failed', 'error' => 'Missing b2_path for restore'];
        }

        $timestamp  = gmdate('Y-m-d_H-i-s');
        $archive    = trailingslashit(sys_get_temp_dir()) . 'reviveguard-restore-' . $timestamp . '.tar.gz';
        $extractDir = trailingslashit(sys_get_temp_dir()) . 'reviveguard-restore-' . $timestamp . '/';

        try {
            $this->downloadFromB2($b2Path, $archive);

            if (! file_exists($archive)) {
                throw new RuntimeException('Downloaded archive not found');
            }

            wp_mkdir_p($extractDir);
            $this->extractArchive($archive, $extractDir);

            $this->enableMaintenanceMode();

            $dbFile = $extractDir . 'database.sql';
            if (file_exists($dbFile)) {
                $this->importDatabase($dbFile);
            }

            $this->restoreFiles($extractDir);

            $this->disableMaintenanceMode();

            update_option('reviveguard_last_backup', time());

            return [
                'status'  => 'success',
                'message' => 'Site restored from backup taken before the failed update.',
                'b2_path' => $b2Path,
            ];
        } catch (Throwable $e) {
            $this->disableMaintenanceMode();
            ReviveGuard_DebugLogger::error('Restore failed: ' . $e->getMessage());

            return ['status' => 'failed', 'error' => $e->getMessage()];
        } finally {
            if (file_exists($archive)) {
                @unlink($archive);
            }
            $this->rrmdir($extractDir);
        }
    }

    private function downloadFromB2(string $b2Path, string $localFile): void
    {
        $keyId      = (string) get_option('reviveguard_b2_key_id', '');
        $appKey     = (string) get_option('reviveguard_b2_app_key', '');
        $bucketName = (string) get_option('reviveguard_b2_bucket_name', '');

        if ($keyId === '' || $appKey === '' || $bucketName === '') {
            throw new RuntimeException('B2 credentials not configured in plugin settings');
        }

        $authResponse = wp_remote_get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account', [
            'headers'   => ['Authorization' => 'Basic ' . base64_encode("{$keyId}:{$appKey}")],
            'timeout'   => 30,
            'sslverify' => true,
        ]);

        if (is_wp_error($authResponse)) {
            throw new RuntimeException('B2 authorization failed: ' . $authResponse->get_error_message());
        }

        $auth = json_decode(wp_remote_retrieve_body($authResponse), true);
        if (empty($auth['downloadUrl']) || empty($auth['authorizationToken'])) {
            throw new RuntimeException('B2 authorization response malformed');
        }

        $downloadUrl = rtrim((string) $auth['downloadUrl'], '/')
            . '/file/' . rawurlencode($bucketName) . '/' . ltrim($b2Path, '/');

        $fileResponse = wp_remote_get($downloadUrl, [
            'headers'   => ['Authorization' => (string) $auth['authorizationToken']],
            'timeout'   => 600,
            'sslverify' => true,
        ]);

        if (is_wp_error($fileResponse)) {
            throw new RuntimeException('B2 download failed: ' . $fileResponse->get_error_message());
        }

        if (wp_remote_retrieve_response_code($fileResponse) !== 200) {
            throw new RuntimeException('B2 download returned HTTP ' . wp_remote_retrieve_response_code($fileResponse));
        }

        $body = wp_remote_retrieve_body($fileResponse);
        if ($body === '' || file_put_contents($localFile, $body) === false) {
            throw new RuntimeException('Failed to write downloaded backup to disk');
        }
    }

    private function extractArchive(string $archive, string $extractDir): void
    {
        $archiveEsc   = escapeshellarg($archive);
        $extractDirEsc = escapeshellarg($extractDir);
        $exitCode     = 0;

        system("tar -xzf {$archiveEsc} -C {$extractDirEsc} 2>&1", $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException("tar extract failed with exit code {$exitCode}");
        }
    }

    private function importDatabase(string $dbFile): void
    {
        if ($this->isWpCliAvailable()) {
            $escaped = escapeshellarg($dbFile);
            $exitCode = 0;
            system("wp db import {$escaped} --allow-root 2>&1", $exitCode);

            if ($exitCode === 0) {
                return;
            }
            ReviveGuard_DebugLogger::warning('WP-CLI db import failed — trying PHP import');
        }

        global $wpdb;
        $sql = file_get_contents($dbFile);
        if ($sql === false || $sql === '') {
            throw new RuntimeException('Could not read database.sql from backup');
        }

        $wpdb->query('SET FOREIGN_KEY_CHECKS=0'); // phpcs:ignore
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            if ($statement === '' || strpos($statement, '--') === 0) {
                continue;
            }
            $wpdb->query($statement); // phpcs:ignore
        }
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1'); // phpcs:ignore
    }

    private function restoreFiles(string $extractDir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $wpRoot = trailingslashit(ABSPATH);

        foreach ($iterator as $item) {
            $relative = str_replace($extractDir, '', $item->getPathname());

            if ($relative === 'database.sql' || $relative === 'wp-config.php') {
                continue;
            }

            $target = $wpRoot . $relative;

            if ($item->isDir()) {
                wp_mkdir_p($target);
            } else {
                wp_mkdir_p(dirname($target));
                copy($item->getPathname(), $target);
            }
        }
    }

    private function enableMaintenanceMode(): void
    {
        if (! file_exists(ABSPATH . '.maintenance')) {
            touch(ABSPATH . '.maintenance');
        }
    }

    private function disableMaintenanceMode(): void
    {
        $file = ABSPATH . '.maintenance';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private function isWpCliAvailable(): bool
    {
        $output   = [];
        $exitCode = 0;
        exec('which wp 2>/dev/null', $output, $exitCode);

        return $exitCode === 0 && ! empty($output);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $objects = scandir($dir);
        if ($objects === false) {
            return;
        }
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path) && ! is_link($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
