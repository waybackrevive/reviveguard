<?php
defined('ABSPATH') || exit;

/**
 * Creates a full-site backup (files + database) and uploads it to Backblaze B2.
 *
 * Upload strategy:
 *   1. Try rclone if available (fast, handles large files well)
 *   2. Fall back to direct B2 API (v2) via wp_remote_post
 *
 * B2 path prefix: reviveguard-backups/{site_id}/{timestamp}.tar.gz
 */
final class ReviveGuard_BackupHandler
{
    /**
     * @param  string               $commandId
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function run(string $commandId, array $params): array
    {
        $startTime = time();
        $timestamp = gmdate('Y-m-d_H-i-s');
        $tmpDir    = trailingslashit(sys_get_temp_dir()) . 'reviveguard-backup-' . $timestamp . '/';

        $client = new ReviveGuard_ApiClient();

        try {
            // Step 1: Create temp directory
            if (! wp_mkdir_p($tmpDir)) {
                throw new RuntimeException('Failed to create temp directory: ' . $tmpDir);
            }

            $this->reportEvent($client, 'info', 'Backup started');

            // Step 2: Export database to tmp dir
            $dbFile = $tmpDir . 'database.sql';
            $this->exportDatabase($dbFile);

            // Step 3: Create tar.gz archive including DB file and WP files
            $archiveFile = trailingslashit(sys_get_temp_dir()) . 'reviveguard-' . $timestamp . '.tar.gz';
            $this->createArchive($archiveFile, $tmpDir);

            // Step 4: Verify archive was created
            if (! file_exists($archiveFile)) {
                throw new RuntimeException('Archive file not found after creation attempt');
            }

            $checksum = (string) hash_file('sha256', $archiveFile);
            $fileSize = round((int) filesize($archiveFile) / (1024 * 1024), 2);

            // Step 5: Upload to B2
            $b2Path = $this->uploadToB2($archiveFile, $timestamp);

            $this->reportEvent($client, 'success', "Backup uploaded: {$b2Path} ({$fileSize} MB)");

            update_option('reviveguard_last_backup', time());

            $trigger = (string) ($params['trigger'] ?? '');
            if ($trigger === 'pre_update') {
                update_option('reviveguard_pre_update_backup', $b2Path);
            }

            return [
                'status'           => 'success',
                'file_size_mb'     => $fileSize,
                'checksum'         => 'sha256:' . $checksum,
                'b2_path'          => $b2Path,
                'duration_seconds' => time() - $startTime,
            ];

        } catch (Throwable $e) {
            ReviveGuard_DebugLogger::error('Backup failed: ' . $e->getMessage());
            $this->reportEvent($client, 'critical', 'Backup failed: ' . $e->getMessage());

            return ['status' => 'failed', 'error' => $e->getMessage()];

        } finally {
            // Always clean up temp files
            if (file_exists($archiveFile ?? '')) {
                @unlink($archiveFile);
            }
            $this->rrmdir($tmpDir);
        }
    }

    private function exportDatabase(string $outputFile): void
    {
        if ($this->isWpCliAvailable()) {
            $exitCode = 0;
            $escaped  = escapeshellarg($outputFile);
            system("wp db export {$escaped} --allow-root 2>&1", $exitCode);

            if ($exitCode === 0 && file_exists($outputFile)) {
                return;
            }
            ReviveGuard_DebugLogger::warning('WP-CLI db export failed — falling back to PHP export');
        }

        $this->phpDatabaseExport($outputFile);
    }

    private function phpDatabaseExport(string $outputFile): void
    {
        global $wpdb;

        $tables = $wpdb->get_col('SHOW TABLES');
        if (! is_array($tables)) {
            throw new RuntimeException('Could not retrieve table list from database');
        }

        $sql = "-- ReviveGuard PHP DB Export\n-- Generated: " . gmdate('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Validate table name (alphanumeric + underscore only)
            if (! preg_match('/^[a-zA-Z0-9_]+$/', (string) $table)) {
                continue;
            }

            /** @var array<int, string>|null $createTable */
            $createTable = $wpdb->get_row(
                $wpdb->prepare('SHOW CREATE TABLE `%1s`', $table), // phpcs:ignore
                ARRAY_N
            );

            if (! is_array($createTable) || ! isset($createTable[1])) {
                continue;
            }

            $sql .= "\n-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createTable[1] . ";\n\n";

            /** @var array<int, array<string, string|null>>|null $rows */
            $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A); // phpcs:ignore
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $values = array_map(
                    static function ($value): string {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . esc_sql($value) . "'";
                    },
                    array_values($row)
                );
                $sql .= 'INSERT INTO `' . $table . '` VALUES (' . implode(',', $values) . ");\n";
            }
        }

        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        if (file_put_contents($outputFile, $sql) === false) {
            throw new RuntimeException('Failed to write database export file');
        }
    }

    private function createArchive(string $archiveFile, string $tmpDir): void
    {
        $wpRoot     = ABSPATH;
        $archiveEsc = escapeshellarg($archiveFile);
        $tmpDirEsc  = escapeshellarg($tmpDir);
        $wpRootEsc  = escapeshellarg($wpRoot);

        // Excludes must come before the file list. --ignore-failed-read avoids exit 2
        // when WP files change mid-read (common on live sites).
        $excludeFlags = implode(' ', [
            '--exclude=./wp-content/cache',
            '--exclude=./wp-content/upgrade',
            '--exclude=./wp-content/uploads/backwpup*',
            '--exclude=./.git',
            '--exclude=./node_modules',
            '--exclude=*.log',
            '--ignore-failed-read',
        ]);

        $output   = [];
        $exitCode = 0;
        $cmd      = "tar -czf {$archiveEsc} {$excludeFlags} -C {$tmpDirEsc} database.sql -C {$wpRootEsc} . 2>&1";
        exec($cmd, $output, $exitCode);

        $stderr = trim(implode("\n", $output));
        $exists = file_exists($archiveFile) && filesize($archiveFile) > 0;

        // GNU tar exit 1 = some files differ; exit 2 = fatal. Accept 0/1 when archive exists.
        if ($exitCode > 1 || ! $exists) {
            $detail = $stderr !== '' ? $stderr : 'no tar output';
            throw new RuntimeException(
                "tar archive creation failed with exit code {$exitCode}: {$detail}"
            );
        }

        if ($exitCode === 1 && $stderr !== '') {
            ReviveGuard_DebugLogger::warning('tar completed with warnings: ' . $stderr);
        }
    }

    private function uploadToB2(string $archiveFile, string $timestamp): string
    {
        if ($this->isRcloneAvailable()) {
            return $this->uploadViaRclone($archiveFile);
        }
        return $this->uploadViaDirectApi($archiveFile, $timestamp);
    }

    private function uploadViaRclone(string $archiveFile): string
    {
        $prefix   = (string) get_option('reviveguard_b2_path_prefix', 'reviveguard-backups');
        $prefix   = trim($prefix, '/');
        $filename = basename($archiveFile);
        $fileEsc  = escapeshellarg($archiveFile);
        $destEsc  = escapeshellarg("b2:{$prefix}/");

        system("rclone copy {$fileEsc} {$destEsc} 2>&1", $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('rclone upload failed');
        }

        return "{$prefix}/{$filename}";
    }

    private function uploadViaDirectApi(string $archiveFile, string $timestamp): string
    {
        $keyId      = (string) get_option('reviveguard_b2_key_id', '');
        $appKey     = (string) get_option('reviveguard_b2_app_key', '');
        $bucketName = (string) get_option('reviveguard_b2_bucket_name', '');

        if (empty($keyId) || empty($appKey) || empty($bucketName)) {
            throw new RuntimeException('B2 credentials not configured in ReviveGuard plugin settings');
        }

        // 1. Authorize account
        $authResponse = wp_remote_get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$keyId}:{$appKey}"),
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ]);

        if (is_wp_error($authResponse)) {
            throw new RuntimeException('B2 authorization failed: ' . $authResponse->get_error_message());
        }

        $auth = json_decode(wp_remote_retrieve_body($authResponse), true);
        if (empty($auth['apiUrl']) || empty($auth['authorizationToken'])) {
            throw new RuntimeException('B2 authorization response malformed');
        }

        $apiUrl    = (string) $auth['apiUrl'];
        $authToken = (string) $auth['authorizationToken'];
        $bucketId  = isset($auth['allowed']['bucketId']) ? (string) $auth['allowed']['bucketId'] : null;

        if (empty($bucketId)) {
            // Fetch bucket ID by name
            $listResponse = wp_remote_post("{$apiUrl}/b2api/v2/b2_list_buckets", [
                'headers'   => [
                    'Authorization' => $authToken,
                    'Content-Type'  => 'application/json',
                ],
                'body'      => wp_json_encode(['accountId' => (string) ($auth['accountId'] ?? '')]),
                'timeout'   => 30,
                'sslverify' => true,
            ]);

            if (! is_wp_error($listResponse)) {
                $listData = json_decode(wp_remote_retrieve_body($listResponse), true);
                if (is_array($listData) && ! empty($listData['buckets'])) {
                    foreach ($listData['buckets'] as $bucket) {
                        if (isset($bucket['bucketName']) && $bucket['bucketName'] === $bucketName) {
                            $bucketId = (string) $bucket['bucketId'];
                            break;
                        }
                    }
                }
            }

            if (empty($bucketId)) {
                throw new RuntimeException("Could not find B2 bucket: {$bucketName}");
            }
        }

        // 2. Get upload URL
        $uploadUrlResponse = wp_remote_post("{$apiUrl}/b2api/v2/b2_get_upload_url", [
            'headers'   => [
                'Authorization' => $authToken,
                'Content-Type'  => 'application/json',
            ],
            'body'      => wp_json_encode(['bucketId' => $bucketId]),
            'timeout'   => 30,
            'sslverify' => true,
        ]);

        if (is_wp_error($uploadUrlResponse)) {
            throw new RuntimeException('B2 get_upload_url failed: ' . $uploadUrlResponse->get_error_message());
        }

        $uploadInfo = json_decode(wp_remote_retrieve_body($uploadUrlResponse), true);
        if (empty($uploadInfo['uploadUrl']) || empty($uploadInfo['authorizationToken'])) {
            throw new RuntimeException('B2 get_upload_url response malformed');
        }

        // 3. Upload file (load into memory — suitable for files up to ~100 MB)
        $fileContent = file_get_contents($archiveFile);
        if ($fileContent === false) {
            throw new RuntimeException('Failed to read archive file for upload');
        }

        $prefix   = trim((string) get_option('reviveguard_b2_path_prefix', 'reviveguard-backups'), '/');
        $fileName = $prefix . '/' . basename($archiveFile);
        $sha1     = sha1_file($archiveFile);

        $uploadResponse = wp_remote_post((string) $uploadInfo['uploadUrl'], [
            'headers'   => [
                'Authorization'     => (string) $uploadInfo['authorizationToken'],
                'X-Bz-File-Name'    => rawurlencode($fileName),
                'Content-Type'      => 'application/gzip',
                'Content-Length'    => (string) strlen($fileContent),
                'X-Bz-Content-Sha1' => (string) $sha1,
            ],
            'body'      => $fileContent,
            'timeout'   => 600,
            'sslverify' => true,
        ]);

        if (is_wp_error($uploadResponse)) {
            throw new RuntimeException('B2 file upload failed: ' . $uploadResponse->get_error_message());
        }

        $uploadResult = json_decode(wp_remote_retrieve_body($uploadResponse), true);
        if (empty($uploadResult['fileId'])) {
            throw new RuntimeException('B2 file upload response missing fileId — upload may have failed');
        }

        return $fileName;
    }

    private function isWpCliAvailable(): bool
    {
        $output   = [];
        $exitCode = 0;
        exec('which wp 2>/dev/null', $output, $exitCode);
        return $exitCode === 0 && ! empty($output);
    }

    private function isRcloneAvailable(): bool
    {
        $output   = [];
        $exitCode = 0;
        exec('which rclone 2>/dev/null', $output, $exitCode);
        return $exitCode === 0 && ! empty($output);
    }

    private function reportEvent(ReviveGuard_ApiClient $client, string $severity, string $message): void
    {
        $client->post('/api/v1/agent/event', [
            'type'     => 'backup',
            'severity' => $severity,
            'message'  => $message,
        ]);
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
