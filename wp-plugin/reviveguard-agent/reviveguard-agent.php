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

define('REVIVEGUARD_VERSION',    '1.0.0');
define('REVIVEGUARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REVIVEGUARD_API_BASE',   'https://app.reviveguard.com');

require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-debug-logger.php';
require_once REVIVEGUARD_PLUGIN_DIR . 'includes/class-token-store.php';
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

// Register 5-minute cron schedule
add_filter('cron_schedules', function (array $schedules): array {
    $schedules['reviveguard_5min'] = [
        'interval' => 300,
        'display'  => 'Every 5 Minutes',
    ];
    return $schedules;
});

// Schedule heartbeat on activation
register_activation_hook(__FILE__, function (): void {
    if (! wp_next_scheduled('reviveguard_heartbeat_event')) {
        wp_schedule_event(time(), 'reviveguard_5min', 'reviveguard_heartbeat_event');
    }
    update_option('reviveguard_agent_version', REVIVEGUARD_VERSION);
    update_option('reviveguard_connection_status', 'pending');
});

// Remove schedule on deactivation
register_deactivation_hook(__FILE__, function (): void {
    $timestamp = wp_next_scheduled('reviveguard_heartbeat_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'reviveguard_heartbeat_event');
    }
});

// Hook heartbeat to cron event
add_action('reviveguard_heartbeat_event', function (): void {
    $heartbeat = new ReviveGuard_Heartbeat();
    $heartbeat->run();
});
