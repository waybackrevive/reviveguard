<?php
/**
 * Plugin Name:  ReviveGuard Health Check
 * Plugin URI:   https://reviveguard.com
 * Description:  Generates a signed health-check report for your ReviveGuard site evaluation. Auto-deactivates after 48 hours.
 * Version:      1.0.0
 * Author:       ReviveGuard
 * Author URI:   https://reviveguard.com
 * License:      GPL-2.0+
 * Text Domain:  reviveguard-hc
 */

defined( 'ABSPATH' ) || exit;

// ────────────────────────────────────────────────────────────────────────────
// Constants
// ────────────────────────────────────────────────────────────────────────────
define( 'RG_HC_VERSION',    '1.0.0' );
define( 'RG_HC_FILE',       __FILE__ );
define( 'RG_HC_SLUG',       'reviveguard-healthcheck' );
define( 'RG_HC_TTL',        48 * HOUR_IN_SECONDS ); // auto-deactivate after 48 h

// ────────────────────────────────────────────────────────────────────────────
// Activation / deactivation
// ────────────────────────────────────────────────────────────────────────────
register_activation_hook( RG_HC_FILE, 'rg_hc_activate' );
register_deactivation_hook( RG_HC_FILE, 'rg_hc_deactivate' );

function rg_hc_activate() {
    update_option( 'rg_hc_activated_at', time() );
    if ( ! wp_next_scheduled( 'rg_hc_auto_deactivate' ) ) {
        wp_schedule_single_event( time() + RG_HC_TTL, 'rg_hc_auto_deactivate' );
    }
}

function rg_hc_deactivate() {
    wp_clear_scheduled_hook( 'rg_hc_auto_deactivate' );
}

add_action( 'rg_hc_auto_deactivate', 'rg_hc_do_auto_deactivate' );
function rg_hc_do_auto_deactivate() {
    if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins( plugin_basename( RG_HC_FILE ) );
}

// Enforce TTL on every page load (safety net if cron missed)
add_action( 'plugins_loaded', 'rg_hc_ttl_check' );
function rg_hc_ttl_check() {
    $activated = (int) get_option( 'rg_hc_activated_at', 0 );
    if ( $activated && ( time() - $activated ) > RG_HC_TTL ) {
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        deactivate_plugins( plugin_basename( RG_HC_FILE ) );
        if ( is_admin() ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-info"><p><strong>ReviveGuard Health Check</strong> has been automatically deactivated after 48 hours.</p></div>';
            } );
        }
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Admin menu
// ────────────────────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'rg_hc_add_menu' );
function rg_hc_add_menu() {
    add_management_page(
        'ReviveGuard Health Check',
        'ReviveGuard Health Check',
        'manage_options',
        RG_HC_SLUG,
        'rg_hc_admin_page'
    );
}

// ────────────────────────────────────────────────────────────────────────────
// Admin page — settings + report generation
// ────────────────────────────────────────────────────────────────────────────
function rg_hc_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'reviveguard-hc' ) );
    }

    // Save secret key
    if ( isset( $_POST['rg_hc_save_secret'] ) ) {
        check_admin_referer( 'rg_hc_save_secret' );
        $secret = sanitize_text_field( wp_unslash( $_POST['rg_hc_secret'] ?? '' ) );
        update_option( 'rg_hc_secret', $secret );
        echo '<div class="notice notice-success"><p>Secret key saved.</p></div>';
    }

    $secret = get_option( 'rg_hc_secret', '' );
    $report_code = '';
    $report_generated = false;

    // Generate report
    if ( isset( $_POST['rg_hc_generate'] ) ) {
        check_admin_referer( 'rg_hc_generate' );
        if ( empty( $secret ) ) {
            echo '<div class="notice notice-error"><p>Please save your <strong>Secret Key</strong> first.</p></div>';
        } else {
            $data        = rg_hc_collect_data();
            $json        = wp_json_encode( $data );
            $sig         = hash_hmac( 'sha256', $json, $secret );
            $report_code = base64_encode( $json . '|' . $sig );
            $report_generated = true;
        }
    }

    $activated_at = (int) get_option( 'rg_hc_activated_at', 0 );
    $expires_at   = $activated_at ? ( $activated_at + RG_HC_TTL ) : 0;
    ?>
    <div class="wrap">
        <h1>&#128269; ReviveGuard Health Check</h1>
        <p>This plugin generates a signed health report so ReviveGuard can evaluate your site more thoroughly. It <strong>auto-deactivates in 48 hours</strong>.</p>
        <?php if ( $expires_at ): ?>
            <p style="color:#6b7280;font-size:.875rem;">Auto-deactivates: <?php echo esc_html( date( 'Y-m-d H:i T', $expires_at ) ); ?></p>
        <?php endif; ?>

        <hr style="margin:1.5rem 0">

        <h2>Step 1 — Enter your Secret Key</h2>
        <p style="margin-bottom:.75rem;color:#374151">Find your secret key on your ReviveGuard report upload page.</p>
        <form method="post">
            <?php wp_nonce_field( 'rg_hc_save_secret' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rg_hc_secret">Secret Key</label></th>
                    <td>
                        <input type="text" id="rg_hc_secret" name="rg_hc_secret"
                               value="<?php echo esc_attr( $secret ); ?>"
                               class="regular-text" autocomplete="off">
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Key', 'secondary', 'rg_hc_save_secret' ); ?>
        </form>

        <hr style="margin:1.5rem 0">

        <h2>Step 2 — Generate your Report</h2>
        <p style="margin-bottom:.75rem;color:#374151">Click the button below. We will scan this site and generate a one-time report code.</p>
        <form method="post">
            <?php wp_nonce_field( 'rg_hc_generate' ); ?>
            <?php submit_button( 'Generate Report &#x2192;', 'primary', 'rg_hc_generate' ); ?>
        </form>

        <?php if ( $report_generated ): ?>
        <hr style="margin:1.5rem 0">
        <h2>Step 3 — Copy &amp; paste your report code</h2>
        <p style="color:#374151;margin-bottom:.75rem">Copy <strong>all</strong> of the text below and paste it into your ReviveGuard report upload page.</p>
        <textarea id="rg_hc_code" rows="6" style="width:100%;font-family:monospace;font-size:.78rem;padding:.5rem;border:1px solid #d1d5db;border-radius:4px" readonly><?php echo esc_textarea( $report_code ); ?></textarea>
        <p style="margin-top:.5rem">
            <button type="button" onclick="(function(){var t=document.getElementById('rg_hc_code');t.select();document.execCommand('copy');alert('Report code copied!');})()">Copy to clipboard</button>
        </p>
        <?php endif; ?>
    </div>
    <?php
}

// ────────────────────────────────────────────────────────────────────────────
// Data collection
// ────────────────────────────────────────────────────────────────────────────
function rg_hc_collect_data(): array {
    global $wpdb;

    // WordPress core info
    $wp_version  = get_bloginfo( 'version' );
    $wp_updates  = rg_hc_get_core_update_available();
    $site_url    = get_site_url();
    $admin_email = get_option( 'admin_email' );

    // PHP info
    $php_version = PHP_VERSION;

    // Active theme
    $theme = wp_get_theme();
    $theme_data = [
        'name'    => $theme->get( 'Name' ),
        'version' => $theme->get( 'Version' ),
        'update'  => rg_hc_theme_has_update( $theme->get_stylesheet() ),
    ];

    // All plugins
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins    = get_plugins();
    $active_plugins = get_option( 'active_plugins', [] );
    $plugin_updates = get_site_transient( 'update_plugins' );

    $plugins = [];
    foreach ( $all_plugins as $file => $data ) {
        $has_update = isset( $plugin_updates->response[ $file ] );
        $plugins[]  = [
            'slug'    => dirname( $file ),
            'name'    => $data['Name'],
            'version' => $data['Version'],
            'active'  => in_array( $file, $active_plugins, true ),
            'update'  => $has_update,
        ];
    }

    // Security plugins detected
    $security_slugs   = [ 'wordfence', 'better-wp-security', 'all-in-one-wp-security-and-firewall', 'sucuri-scanner', 'wp-cerber', 'jetpack' ];
    $security_plugins = array_filter( $plugins, fn( $p ) => in_array( $p['slug'], $security_slugs, true ) && $p['active'] );

    // Backup plugins detected
    $backup_slugs   = [ 'updraftplus', 'backwpup', 'backupbuddy', 'duplicator', 'jetpack-backup', 'all-in-one-wp-migration', 'wp-all-backup' ];
    $backup_plugins = array_filter( $plugins, fn( $p ) => in_array( $p['slug'], $backup_slugs, true ) && $p['active'] );

    // Database size (information_schema may be restricted on shared hosting — fail gracefully)
    $db_size_mb = null;
    $db_name    = DB_NAME;
    $size_row   = $wpdb->get_row( $wpdb->prepare(
        "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
         FROM information_schema.tables
         WHERE table_schema = %s",
        $db_name
    ) );
    if ( $size_row ) {
        $db_size_mb = (float) $size_row->size_mb;
    }

    // Users with admin role
    $admin_users = get_users( [ 'role' => 'administrator', 'fields' => [ 'ID', 'user_login', 'user_email' ] ] );
    $admin_count = count( $admin_users );

    // SSL (naive check from within WP)
    $ssl_active = is_ssl() || ( strpos( $site_url, 'https://' ) === 0 );

    // Updates summary
    $plugins_needing_update = count( array_filter( $plugins, fn( $p ) => $p['update'] ) );

    return [
        'generated_at'           => gmdate( 'c' ),
        'site_url'               => $site_url,
        'admin_email'            => $admin_email,
        'wp_version'             => $wp_version,
        'wp_update_available'    => $wp_updates,
        'php_version'            => $php_version,
        'ssl_active'             => $ssl_active,
        'admin_user_count'       => $admin_count,
        'total_plugins'          => count( $plugins ),
        'active_plugins'         => count( array_filter( $plugins, fn( $p ) => $p['active'] ) ),
        'plugins_needing_update' => $plugins_needing_update,
        'plugins'                => $plugins,
        'theme'                  => $theme_data,
        'security_plugins'       => array_values( $security_plugins ),
        'backup_plugins'         => array_values( $backup_plugins ),
        'db_size_mb'             => $db_size_mb,
        'generator_version'      => RG_HC_VERSION,
    ];
}

function rg_hc_get_core_update_available(): bool {
    $updates = get_core_updates();
    if ( is_array( $updates ) ) {
        foreach ( $updates as $update ) {
            if ( isset( $update->response ) && $update->response === 'upgrade' ) {
                return true;
            }
        }
    }
    return false;
}

function rg_hc_theme_has_update( string $stylesheet ): bool {
    $theme_updates = get_site_transient( 'update_themes' );
    return isset( $theme_updates->response[ $stylesheet ] );
}
