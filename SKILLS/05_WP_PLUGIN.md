# SKILL: WordPress Agent Plugin

> Load this skill before writing any WordPress plugin code.
> References: `06_AGENT_PLUGIN_SPEC.md`, `04_API_DESIGN.md`

---

## What This Covers
The complete WordPress plugin (`reviveguard-agent`) — all PHP classes, WP Cron setup, command execution, admin settings page, backup handler, update handler, and packaging.

---

## Core Design Rules (Never Violate)

1. **Zero Composer dependencies** — pure WordPress PHP. No autoloaders, no external packages.
2. **Fail silently** — never throw exceptions that affect the site. All errors: log to internal debug log.
3. **Never use `error_log()`** — use the internal debug logger only.
4. **Never slow down WP admin** — no heavy operations on page load. Everything via WP Cron or Action Scheduler.
5. **Token in encrypted storage** — never in plaintext. Use `wp_options` with `reviveguard_agent_token` key, encrypted using `AUTH_SALT`.

---

## File Structure

```
reviveguard-agent/
├── reviveguard-agent.php              ← Plugin headers + bootstrap
├── includes/
│   ├── class-api-client.php           ← All outgoing HTTP to your platform
│   ├── class-heartbeat.php            ← Heartbeat + command receive
│   ├── class-command-runner.php       ← Command dispatch
│   ├── class-backup-handler.php       ← Backup creation + B2 upload
│   ├── class-update-handler.php       ← WP-CLI updates + fallback
│   ├── class-plugin-inventory.php     ← Installed plugins collector
│   ├── class-site-info.php            ← Site metadata collector
│   └── class-debug-logger.php         ← Internal debug log
├── admin/
│   ├── class-admin-page.php           ← Settings page registration
│   └── views/
│       └── settings-page.php          ← HTML template
├── assets/
│   └── admin.css
└── README.md
```

---

## `reviveguard-agent.php` (Main Plugin File)

```php
<?php
/**
 * Plugin Name:     ReviveGuard Site Agent
 * Plugin URI:      https://app.reviveguard.com
 * Description:     Connects this WordPress site to ReviveGuard monitoring platform.
 * Version:         1.0.0
 * Requires at least: 5.8
 * Requires PHP:    7.4
 * Author:          WaybackRevive LLC
 * License:         Proprietary
 */

defined('ABSPATH') || exit;

define('REVIVEGUARD_VERSION',  '1.0.0');
define('REVIVEGUARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REVIVEGUARD_API_BASE',   'https://app.reviveguard.com');

require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-debug-logger.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-api-client.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-site-info.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-plugin-inventory.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-command-runner.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-backup-handler.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-update-handler.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-heartbeat.php';

if (is_admin()) {
    require_once REVIVEGUARD_PLUGIN_DIR . 'admin/class-admin-page.php';
    new ReviveGuard_AdminPage();
}

// Register WP Cron event (every 5 minutes)
add_filter('cron_schedules', function (array $schedules): array {
    $schedules['reviveguard_5min'] = [
        'interval' => 300,
        'display'  => 'Every 5 Minutes',
    ];
    return $schedules;
});

// Schedule heartbeat on activation
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('reviveguard_heartbeat_event')) {
        wp_schedule_event(time(), 'reviveguard_5min', 'reviveguard_heartbeat_event');
    }
});

// Remove schedule on deactivation
register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('reviveguard_heartbeat_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'reviveguard_heartbeat_event');
    }
});

// Hook heartbeat to cron event
add_action('reviveguard_heartbeat_event', function () {
    $heartbeat = new ReviveGuard_Heartbeat();
    $heartbeat->run();
});
```

---

## `class-api-client.php`

```php
<?php
defined('ABSPATH') || exit;

final class ReviveGuard_ApiClient
{
    private string $token;
    private string $baseUrl;
    private int $timeout = 15; // seconds
    
    public function __construct()
    {
        $this->token   = ReviveGuard_TokenStore::get();
        $this->baseUrl = get_option('reviveguard_api_base_url', REVIVEGUARD_API_BASE);
    }
    
    public function post(string $endpoint, array $data): ?array
    {
        if (empty($this->token)) {
            ReviveGuard_DebugLogger::warning('API call skipped — no token configured');
            return null;
        }
        
        $url      = rtrim($this->baseUrl, '/') . $endpoint;
        $body     = wp_json_encode($data);
        $headers  = [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'ReviveGuard-Agent/' . REVIVEGUARD_VERSION,
        ];
        
        $response = wp_remote_post($url, [
            'headers'   => $headers,
            'body'      => $body,
            'timeout'   => $this->timeout,
            'sslverify' => true,  // always verify SSL
        ]);
        
        if (is_wp_error($response)) {
            ReviveGuard_DebugLogger::error('API request failed: ' . $response->get_error_message());
            $this->incrementFailureCount();
            return null;
        }
        
        $statusCode = wp_remote_retrieve_response_code($response);
        
        if ($statusCode === 401) {
            update_option('reviveguard_connection_status', 'auth_error');
            ReviveGuard_DebugLogger::error('API returned 401 — token may be invalid');
            return null;
        }
        
        if ($statusCode < 200 || $statusCode >= 300) {
            ReviveGuard_DebugLogger::error("API returned {$statusCode} for {$endpoint}");
            $this->incrementFailureCount();
            return null;
        }
        
        $this->resetFailureCount();
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    private function incrementFailureCount(): void
    {
        $count = (int) get_option('reviveguard_failure_count', 0) + 1;
        update_option('reviveguard_failure_count', $count);
        
        if ($count >= 3) {
            update_option('reviveguard_connection_status', 'error');
        }
    }
    
    private function resetFailureCount(): void
    {
        update_option('reviveguard_failure_count', 0);
        update_option('reviveguard_connection_status', 'connected');
    }
}
```

---

## `class-heartbeat.php`

```php
<?php
defined('ABSPATH') || exit;

final class ReviveGuard_Heartbeat
{
    public function run(): void
    {
        $token = ReviveGuard_TokenStore::get();
        if (empty($token)) {
            return; // Plugin not configured — do nothing
        }
        
        $siteInfo  = new ReviveGuard_SiteInfo();
        $client    = new ReviveGuard_ApiClient();
        $inventory = new ReviveGuard_PluginInventory();
        
        $payload   = $siteInfo->collect();
        $response  = $client->post('/api/v1/agent/heartbeat', $payload);
        
        if ($response === null) {
            return;
        }
        
        update_option('reviveguard_last_heartbeat', current_time('timestamp'));
        update_option('reviveguard_connection_status', 'connected');
        
        // Check if plugin list has changed — send if so
        if ($inventory->hasChanged()) {
            $this->sendPluginList($client, $inventory);
        }
        
        // Process any commands received
        if (!empty($response['commands'])) {
            $runner = new ReviveGuard_CommandRunner();
            foreach ($response['commands'] as $command) {
                $runner->queue($command);
            }
        }
    }
    
    private function sendPluginList(ReviveGuard_ApiClient $client, ReviveGuard_PluginInventory $inventory): void
    {
        $plugins = $inventory->collect();
        $client->post('/api/v1/agent/plugin-list', ['plugins' => $plugins]);
        $inventory->saveChecksum($plugins);
    }
}
```

---

## `class-backup-handler.php`

```php
<?php
defined('ABSPATH') || exit;

final class ReviveGuard_BackupHandler
{
    public function run(string $commandId, array $params): array
    {
        update_option('reviveguard_pending_command', $commandId);
        
        $timestamp = date('Y-m-d_H-i-s');
        $tmpDir    = sys_get_temp_dir() . '/reviveguard-backup-' . $timestamp . '/';
        
        try {
            // Step 1: Create temp directory
            if (!wp_mkdir_p($tmpDir)) {
                throw new RuntimeException('Failed to create temp directory');
            }
            
            // Step 2: Export database
            $dbFile = $tmpDir . 'database.sql';
            if ($this->isWpCliAvailable()) {
                $exitCode = 0;
                system("wp db export {$dbFile} --allow-root 2>&1", $exitCode);
                if ($exitCode !== 0 || !file_exists($dbFile)) {
                    // Fallback to PHP-based dump
                    $this->phpDatabaseExport($dbFile);
                }
            } else {
                $this->phpDatabaseExport($dbFile);
            }
            
            // Step 3: Create archive (exclude cache, logs, temp)
            $archiveFile = sys_get_temp_dir() . '/reviveguard-' . $timestamp . '.tar.gz';
            $wpRoot      = ABSPATH;
            $excludes    = implode(' ', [
                '--exclude=./wp-content/cache',
                '--exclude=./wp-content/upgrade',
                '--exclude=./.git',
                '--exclude=./node_modules',
                '--exclude=*.log',
            ]);
            
            system("tar -czf {$archiveFile} -C {$wpRoot} . {$excludes} 2>&1", $exitCode);
            if ($exitCode !== 0) {
                throw new RuntimeException("tar failed with exit code {$exitCode}");
            }
            
            // Step 4: Calculate checksum
            $checksum = hash_file('sha256', $archiveFile);
            $fileSize = round(filesize($archiveFile) / (1024 * 1024), 2); // MB
            
            // Step 5: Upload to B2
            $b2Path = $this->uploadToB2($archiveFile, $timestamp);
            
            // Step 6: Clean up temp files
            @unlink($archiveFile);
            $this->rrmdir($tmpDir);
            
            update_option('reviveguard_last_backup', current_time('timestamp'));
            update_option('reviveguard_pending_command', null);
            
            return [
                'status'            => 'success',
                'file_size_mb'      => $fileSize,
                'checksum'          => 'sha256:' . $checksum,
                'b2_path'           => $b2Path,
                'duration_seconds'  => time() - strtotime($timestamp),
            ];
            
        } catch (Throwable $e) {
            $this->rrmdir($tmpDir);
            update_option('reviveguard_pending_command', null);
            ReviveGuard_DebugLogger::error('Backup failed: ' . $e->getMessage());
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
    
    private function uploadToB2(string $archiveFile, string $timestamp): string
    {
        // Try rclone first
        if ($this->isRcloneAvailable()) {
            return $this->uploadViaRclone($archiveFile, $timestamp);
        }
        // Fallback: direct B2 API upload via curl
        return $this->uploadViaDirectApi($archiveFile, $timestamp);
    }
    
    private function uploadViaRclone(string $archiveFile, string $timestamp): string
    {
        $remotePath = get_option('reviveguard_b2_path_prefix', 'backups');
        $filename   = basename($archiveFile);
        
        system("rclone copy {$archiveFile} b2:{$remotePath}/ 2>&1", $exitCode);
        
        if ($exitCode !== 0) {
            throw new RuntimeException('rclone upload failed');
        }
        
        return "{$remotePath}/{$filename}";
    }
    
    private function uploadViaDirectApi(string $archiveFile, string $timestamp): string
    {
        // Use B2 native API: authorize → get upload URL → upload
        // Stored in options: reviveguard_b2_key_id, reviveguard_b2_app_key, reviveguard_b2_bucket_name
        // This is the fallback when rclone is not available
        $keyId      = get_option('reviveguard_b2_key_id');
        $appKey     = get_option('reviveguard_b2_app_key');
        $bucketName = get_option('reviveguard_b2_bucket_name');
        
        if (empty($keyId) || empty($appKey) || empty($bucketName)) {
            throw new RuntimeException('B2 credentials not configured in plugin settings');
        }
        
        // 1. Authorize account
        $authResponse = wp_remote_get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account', [
            'headers' => ['Authorization' => 'Basic ' . base64_encode("{$keyId}:{$appKey}")],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($authResponse)) {
            throw new RuntimeException('B2 auth failed: ' . $authResponse->get_error_message());
        }
        
        $auth       = json_decode(wp_remote_retrieve_body($authResponse), true);
        $apiUrl     = $auth['apiUrl'];
        $authToken  = $auth['authorizationToken'];
        $bucketId   = $auth['allowed']['bucketId'] ?? null;
        
        // 2. Get upload URL
        $uploadUrlResponse = wp_remote_post("{$apiUrl}/b2api/v2/b2_get_upload_url", [
            'headers' => [
                'Authorization' => $authToken,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode(['bucketId' => $bucketId]),
            'timeout' => 30,
        ]);
        
        $uploadInfo = json_decode(wp_remote_retrieve_body($uploadUrlResponse), true);
        
        // 3. Upload file
        $fileName    = "backups/" . basename($archiveFile);
        $fileContent = file_get_contents($archiveFile);
        $sha1        = sha1_file($archiveFile);
        
        $uploadResponse = wp_remote_post($uploadInfo['uploadUrl'], [
            'headers' => [
                'Authorization'      => $uploadInfo['authorizationToken'],
                'X-Bz-File-Name'     => rawurlencode($fileName),
                'Content-Type'       => 'application/gzip',
                'Content-Length'     => strlen($fileContent),
                'X-Bz-Content-Sha1'  => $sha1,
            ],
            'body'    => $fileContent,
            'timeout' => 300,
        ]);
        
        if (is_wp_error($uploadResponse)) {
            throw new RuntimeException('B2 upload failed: ' . $uploadResponse->get_error_message());
        }
        
        return $fileName;
    }
    
    private function isWpCliAvailable(): bool
    {
        exec('which wp 2>/dev/null', $output, $exitCode);
        return $exitCode === 0 && !empty($output);
    }
    
    private function isRcloneAvailable(): bool
    {
        exec('which rclone 2>/dev/null', $output, $exitCode);
        return $exitCode === 0 && !empty($output);
    }
    
    private function phpDatabaseExport(string $outputFile): void
    {
        // Pure PHP DB export using wpdb — no WP-CLI needed
        global $wpdb;
        $tables = $wpdb->get_col('SHOW TABLES');
        $sql    = '';
        
        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $createTable = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            $sql .= "\n\n" . $createTable[1] . ";\n\n";
            
            // Get rows
            $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
            foreach ($rows as $row) {
                $values = array_map(function ($value) use ($wpdb) {
                    return $value === null ? 'NULL' : "'" . esc_sql($value) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n";
            }
        }
        
        file_put_contents($outputFile, $sql);
    }
    
    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
```

---

## `class-update-handler.php`

```php
<?php
defined('ABSPATH') || exit;

final class ReviveGuard_UpdateHandler
{
    public function run(string $commandId): array
    {
        // Safety check: verify recent backup exists (last 24 hours)
        $lastBackup = (int) get_option('reviveguard_last_backup', 0);
        if (time() - $lastBackup > 86400) {
            // Trigger backup first, then schedule update after
            ReviveGuard_DebugLogger::info('No recent backup — triggering backup before updates');
            // Queue backup command and return — updates will run after backup
            return [
                'status' => 'deferred',
                'reason' => 'Backup triggered first for safety. Updates will run after backup completes.',
            ];
        }
        
        $results = [];
        
        if ($this->isWpCliAvailable()) {
            $results = $this->updateViaWpCli();
        } else {
            $results = $this->updateViaWordPressApi();
        }
        
        return ['status' => 'success', 'results' => $results];
    }
    
    private function updateViaWpCli(): array
    {
        $results = [];
        
        // Core update
        exec('wp core update --allow-root 2>&1', $output, $exitCode);
        $results['core'] = ['exit_code' => $exitCode, 'output' => implode("\n", $output)];
        
        // Plugin updates
        exec('wp plugin update --all --allow-root --format=json 2>&1', $output, $exitCode);
        $results['plugins'] = json_decode(implode('', $output), true) ?? [];
        
        // Theme updates
        exec('wp theme update --all --allow-root 2>&1', $output, $exitCode);
        $results['themes'] = ['exit_code' => $exitCode];
        
        return $results;
    }
    
    private function updateViaWordPressApi(): array
    {
        // Fallback: use WordPress native update functions
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        
        wp_update_plugins();
        wp_update_themes();
        wp_version_check();
        
        $pluginUpdates = get_site_transient('update_plugins');
        $results       = ['method' => 'wordpress_api', 'plugins' => []];
        
        if (!empty($pluginUpdates->response)) {
            foreach ($pluginUpdates->response as $pluginFile => $updateInfo) {
                $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
                $result   = $upgrader->upgrade($pluginFile);
                $results['plugins'][$pluginFile] = $result ? 'updated' : 'failed';
            }
        }
        
        return $results;
    }
    
    private function isWpCliAvailable(): bool
    {
        exec('which wp 2>/dev/null', $output, $exitCode);
        return $exitCode === 0 && !empty($output);
    }
}
```

---

## Token Storage (`class-token-store.php` — helper)

```php
final class ReviveGuard_TokenStore
{
    private const OPTION_KEY = 'reviveguard_agent_token';
    
    public static function get(): string
    {
        $encrypted = get_option(self::OPTION_KEY, '');
        if (empty($encrypted)) return '';
        return self::decrypt($encrypted);
    }
    
    public static function set(string $rawToken): void
    {
        update_option(self::OPTION_KEY, self::encrypt($rawToken));
    }
    
    private static function encrypt(string $value): string
    {
        $salt = defined('AUTH_SALT') ? AUTH_SALT : wp_generate_password(64, true, true);
        return base64_encode(openssl_encrypt($value, 'AES-256-CBC', $salt, 0, substr($salt, 0, 16)));
    }
    
    private static function decrypt(string $encrypted): string
    {
        $salt = defined('AUTH_SALT') ? AUTH_SALT : '';
        if (empty($salt)) return '';
        $decoded = base64_decode($encrypted);
        return openssl_decrypt($decoded, 'AES-256-CBC', $salt, 0, substr($salt, 0, 16)) ?: '';
    }
}
```

---

## Admin Settings Page

`views/settings-page.php` — renders the status dashboard described in the spec:
- Connection status (connected / error / pending)
- Last heartbeat timestamp
- WP version, PHP version, active plugin count
- Token field (masked, with reveal button that shows for 5 seconds)
- API URL field
- "Save Settings" button
- "Send Test Heartbeat" button

---

## Phase 1 Scope Reminder

**OUT — Do not build:**
- Staging test before updates
- Selective plugin updates (all-or-nothing)
- Automatic rollback after failed update
- Performance metrics
- WordPress Multisite support

---

## Definition of Done

```
[ ] Plugin installs on WordPress 5.8+ without errors
[ ] WP Cron schedules reviveguard_heartbeat_event every 5 minutes
[ ] Heartbeat sends valid JSON to platform, receives 200
[ ] Token stored encrypted in wp_options
[ ] Settings page shows connection status, last heartbeat time
[ ] Test Heartbeat button works, shows success/error feedback
[ ] Backup command: creates archive, uploads to B2 (or reports error clearly)
[ ] Backup verifies upload via filesize check
[ ] Update command: runs via WP-CLI if available, falls back to WP API
[ ] Plugin list sent when changes detected (not on every heartbeat)
[ ] All errors logged to internal debug log, never to error_log
[ ] Plugin packaged as .zip and installable via WP admin upload
[ ] plugin deactivation removes WP Cron schedule
[ ] No PHP warnings or notices on PHP 8.3
```
