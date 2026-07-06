<?php
defined('ABSPATH') || exit;

/**
 * One-click wp-admin login from the ReviveGuard client portal.
 */
final class ReviveGuard_SsoLogin
{
    public function register(): void
    {
        add_action('init', [$this, 'maybe_login'], 1);
    }

    public function maybe_login(): void
    {
        if (empty($_GET['reviveguard_sso'])) {
            return;
        }

        if (is_user_logged_in() && current_user_can('manage_options')) {
            wp_safe_redirect(admin_url());
            exit;
        }

        $token = sanitize_text_field(wp_unslash($_GET['reviveguard_sso']));

        if ($token === '') {
            wp_die(esc_html__('Invalid login link.', 'reviveguard-agent'), 403);
        }

        $client = new ReviveGuard_ApiClient();
        $result = $client->post('/api/v1/agent/sso-consume', ['login_token' => $token]);

        if (empty($result['ok'])) {
            wp_die(esc_html__('This login link has expired or was already used. Open WordPress admin from ReviveGuard again.', 'reviveguard-agent'), 403);
        }

        $admins = get_users([
            'role'    => 'administrator',
            'number'  => 1,
            'orderby' => 'ID',
            'order'   => 'ASC',
        ]);

        if (empty($admins)) {
            wp_die(esc_html__('No administrator account found on this site.', 'reviveguard-agent'), 500);
        }

        $user = $admins[0];
        wp_set_auth_cookie($user->ID, false);
        wp_set_current_user($user->ID);

        do_action('wp_login', $user->user_login, $user);

        wp_safe_redirect(admin_url());
        exit;
    }
}
