<?php
defined('ABSPATH') || exit;

/**
 * Registers the ReviveGuard settings page under Settings > ReviveGuard.
 * Handles saving and the "Send Test Heartbeat" AJAX action.
 */
final class ReviveGuard_AdminPage
{
    public function __construct()
    {
        add_action('admin_menu',            [$this, 'registerMenu']);
        add_action('admin_init',            [$this, 'registerSettings']);
        add_action('admin_post_reviveguard_save_settings', [$this, 'handleSave']);
        add_action('wp_ajax_reviveguard_test_heartbeat',   [$this, 'handleTestHeartbeat']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerMenu(): void
    {
        add_options_page(
            'ReviveGuard Settings',
            'ReviveGuard',
            'manage_options',
            'reviveguard-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('reviveguard', 'reviveguard_api_base_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => REVIVEGUARD_API_BASE,
        ]);
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'reviveguard-agent'));
        }
        require REVIVEGUARD_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    public function handleSave(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied.', 'reviveguard-agent'));
        }

        check_admin_referer('reviveguard_save_settings');

        // API Base URL
        $apiUrl = isset($_POST['reviveguard_api_base_url'])
            ? esc_url_raw(wp_unslash((string) $_POST['reviveguard_api_base_url']))
            : REVIVEGUARD_API_BASE;
        update_option('reviveguard_api_base_url', $apiUrl);

        // B2 Settings
        $b2KeyId = isset($_POST['reviveguard_b2_key_id'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reviveguard_b2_key_id']))
            : '';
        $b2AppKey = isset($_POST['reviveguard_b2_app_key'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reviveguard_b2_app_key']))
            : '';
        $b2BucketName = isset($_POST['reviveguard_b2_bucket_name'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reviveguard_b2_bucket_name']))
            : '';
        $b2PathPrefix = isset($_POST['reviveguard_b2_path_prefix'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reviveguard_b2_path_prefix']))
            : 'reviveguard-backups';

        update_option('reviveguard_b2_key_id',      $b2KeyId);
        update_option('reviveguard_b2_bucket_name', $b2BucketName);
        update_option('reviveguard_b2_path_prefix', $b2PathPrefix);

        // Only update B2 app key if a new value was entered (don't overwrite with blank)
        if (! empty($b2AppKey)) {
            update_option('reviveguard_b2_app_key', $b2AppKey);
        }

        // Agent Token — only update if a new value was provided
        $rawToken = isset($_POST['reviveguard_agent_token'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reviveguard_agent_token']))
            : '';
        if (! empty($rawToken)) {
            ReviveGuard_TokenStore::set($rawToken);
            update_option('reviveguard_connection_status', 'pending');
        }

        wp_redirect(admin_url('options-general.php?page=reviveguard-settings&saved=1'));
        exit;
    }

    public function handleTestHeartbeat(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        check_ajax_referer('reviveguard_test_heartbeat');

        $heartbeat = new ReviveGuard_Heartbeat();
        $heartbeat->run();

        $status = (string) get_option('reviveguard_connection_status', 'error');

        if ($status === 'connected') {
            wp_send_json_success(['message' => 'Heartbeat sent successfully — connection confirmed.']);
        } else {
            wp_send_json_error(['message' => 'Heartbeat failed. Check the debug log for details.']);
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'settings_page_reviveguard-settings') {
            return;
        }
        wp_enqueue_style(
            'reviveguard-admin',
            plugin_dir_url(REVIVEGUARD_PLUGIN_DIR . 'reviveguard-agent.php') . 'assets/admin.css',
            [],
            REVIVEGUARD_VERSION
        );
    }
}
