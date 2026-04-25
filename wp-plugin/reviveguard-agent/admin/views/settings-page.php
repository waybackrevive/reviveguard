<?php
defined('ABSPATH') || exit;

$connectionStatus  = (string) get_option('reviveguard_connection_status', 'pending');
$lastHeartbeatTs   = (int) get_option('reviveguard_last_heartbeat', 0);
$lastHeartbeat     = $lastHeartbeatTs > 0 ? wp_date('Y-m-d H:i:s', $lastHeartbeatTs) : 'Never';
$apiUrl            = (string) get_option('reviveguard_api_base_url', REVIVEGUARD_API_BASE);
$b2KeyId           = (string) get_option('reviveguard_b2_key_id', '');
$b2BucketName      = (string) get_option('reviveguard_b2_bucket_name', '');
$b2PathPrefix      = (string) get_option('reviveguard_b2_path_prefix', 'reviveguard-backups');
$saved             = isset($_GET['saved']) && $_GET['saved'] === '1'; // phpcs:ignore WordPress.Security.NonceVerification
$tokenIsSet        = ReviveGuard_TokenStore::get() !== '';

$statusLabels = [
    'connected'  => ['label' => 'Connected',    'class' => 'rg-status--ok'],
    'error'      => ['label' => 'Error',         'class' => 'rg-status--error'],
    'auth_error' => ['label' => 'Auth Error',    'class' => 'rg-status--error'],
    'pending'    => ['label' => 'Pending',       'class' => 'rg-status--pending'],
];
$statusInfo = $statusLabels[$connectionStatus] ?? $statusLabels['pending'];

$testNonce      = wp_create_nonce('reviveguard_test_heartbeat');
$adminPostUrl   = esc_url(admin_url('admin-post.php'));
?>
<div class="wrap rg-wrap">
    <h1><?php esc_html_e('ReviveGuard Settings', 'reviveguard-agent'); ?></h1>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved.', 'reviveguard-agent'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Status Dashboard -->
    <div class="rg-status-card">
        <h2><?php esc_html_e('Connection Status', 'reviveguard-agent'); ?></h2>
        <table class="rg-info-table">
            <tr>
                <th><?php esc_html_e('Status', 'reviveguard-agent'); ?></th>
                <td>
                    <span class="rg-status <?php echo esc_attr($statusInfo['class']); ?>">
                        <?php echo esc_html($statusInfo['label']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Last Heartbeat', 'reviveguard-agent'); ?></th>
                <td><?php echo esc_html($lastHeartbeat); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Agent Version', 'reviveguard-agent'); ?></th>
                <td><?php echo esc_html(REVIVEGUARD_VERSION); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('WordPress Version', 'reviveguard-agent'); ?></th>
                <td><?php echo esc_html((string) get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('PHP Version', 'reviveguard-agent'); ?></th>
                <td><?php echo esc_html(PHP_VERSION); ?></td>
            </tr>
        </table>

        <button
            id="rg-test-heartbeat"
            class="button button-secondary"
            data-nonce="<?php echo esc_attr($testNonce); ?>"
            data-ajaxurl="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        >
            <?php esc_html_e('Send Test Heartbeat', 'reviveguard-agent'); ?>
        </button>
        <span id="rg-heartbeat-result" class="rg-inline-result"></span>
    </div>

    <!-- Settings Form -->
    <form method="post" action="<?php echo $adminPostUrl; ?>" class="rg-settings-form">
        <?php wp_nonce_field('reviveguard_save_settings'); ?>
        <input type="hidden" name="action" value="reviveguard_save_settings">

        <h2><?php esc_html_e('Connection Settings', 'reviveguard-agent'); ?></h2>
        <table class="form-table" id="rg-b2-table">
            <tr>
                        <?php esc_html_e('Agent Token', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <?php if ($tokenIsSet): ?>
                        <p class="rg-token-set-notice">
                            <span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle;"></span>
                            <strong><?php esc_html_e('Token is set and saved.', 'reviveguard-agent'); ?></strong>
                            <?php esc_html_e('Enter a new value below only if you want to replace it.', 'reviveguard-agent'); ?>
                        </p>
                    <?php endif; ?>
                    <div class="rg-token-wrap">
                        <input
                            type="password"
                            id="reviveguard_agent_token"
                            name="reviveguard_agent_token"
                            class="regular-text"
                            placeholder="<?php echo $tokenIsSet ? esc_attr__('Enter new token to replace existing', 'reviveguard-agent') : esc_attr__('Paste token from ReviveGuard dashboard', 'reviveguard-agent'); ?>"
                            autocomplete="new-password"
                        >
                        <button type="button" id="rg-reveal-token" class="button button-secondary">
                            <?php esc_html_e('Show', 'reviveguard-agent'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Leave blank to keep the existing token. Token is stored encrypted.', 'reviveguard-agent'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="reviveguard_api_base_url">
                        <?php esc_html_e('Platform URL', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="url"
                        id="reviveguard_api_base_url"
                        name="reviveguard_api_base_url"
                        class="regular-text"
                        value="<?php echo esc_attr($apiUrl); ?>"
                    >
                    <p class="description">
                        <?php esc_html_e('Leave as default unless you are self-hosting ReviveGuard.', 'reviveguard-agent'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Backup Storage (Backblaze B2)', 'reviveguard-agent'); ?>
            <button type="button" id="rg-toggle-b2" class="button-link" style="font-size:13px;margin-left:10px;">
                <?php esc_html_e('▸ Show / Hide', 'reviveguard-agent'); ?>
            </button>
        </h2>
        <p class="description" style="margin-bottom:8px;">
            <?php esc_html_e('Optional. Only needed if your plan includes agent-triggered backups stored in Backblaze B2.', 'reviveguard-agent'); ?>
        </p>
        <div id="rg-b2-section" style="display:<?php echo ($b2KeyId || $b2BucketName) ? 'block' : 'none'; ?>">
            <tr>
                <th scope="row">
                    <label for="reviveguard_b2_key_id">
                        <?php esc_html_e('B2 Key ID', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        id="reviveguard_b2_key_id"
                        name="reviveguard_b2_key_id"
                        class="regular-text"
                        value="<?php echo esc_attr($b2KeyId); ?>"
                    >
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="reviveguard_b2_app_key">
                        <?php esc_html_e('B2 Application Key', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="password"
                        id="reviveguard_b2_app_key"
                        name="reviveguard_b2_app_key"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('Leave blank to keep existing', 'reviveguard-agent'); ?>"
                        autocomplete="new-password"
                    >
                    <p class="description">
                        <?php esc_html_e('Leave blank to keep the existing key.', 'reviveguard-agent'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="reviveguard_b2_bucket_name">
                        <?php esc_html_e('B2 Bucket Name', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        id="reviveguard_b2_bucket_name"
                        name="reviveguard_b2_bucket_name"
                        class="regular-text"
                        value="<?php echo esc_attr($b2BucketName); ?>"
                    >
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="reviveguard_b2_path_prefix">
                        <?php esc_html_e('B2 Path Prefix', 'reviveguard-agent'); ?>
                    </label>
                </th>
                <td>
                    <input
                        type="text"
                        id="reviveguard_b2_path_prefix"
                        name="reviveguard_b2_path_prefix"
                        class="regular-text"
                        value="<?php echo esc_attr($b2PathPrefix); ?>"
                    >
                    <p class="description">
                        <?php esc_html_e('Folder path inside your B2 bucket. Example: reviveguard-backups', 'reviveguard-agent'); ?>
                    </p>
                </td>
            </tr>
        </table>
        </div><!-- /#rg-b2-section -->

        <?php submit_button(__('Save Settings', 'reviveguard-agent')); ?>
    </form>
</div>

<script>
(function () {
    'use strict';

    // Reveal token for 5 seconds
    var revealBtn = document.getElementById('rg-reveal-token');
    var tokenField = document.getElementById('reviveguard_agent_token');
    if (revealBtn && tokenField) {
        revealBtn.addEventListener('click', function () {
            tokenField.type = 'text';
            revealBtn.disabled = true;
            setTimeout(function () {
                tokenField.type = 'password';
                revealBtn.disabled = false;
            }, 5000);
        });
    }

    // Test heartbeat via AJAX
    var testBtn = document.getElementById('rg-test-heartbeat');
    var resultEl = document.getElementById('rg-heartbeat-result');
    if (testBtn && resultEl) {
        testBtn.addEventListener('click', function () {
            testBtn.disabled = true;
            resultEl.textContent = 'Sending\u2026';
            resultEl.className = 'rg-inline-result';

            var nonce = testBtn.getAttribute('data-nonce') || '';
            var ajaxUrl = testBtn.getAttribute('data-ajaxurl') || '';

            var formData = new FormData();
            formData.append('action', 'reviveguard_test_heartbeat');
            formData.append('_ajax_nonce', nonce);

            fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        resultEl.textContent = data.data.message;
                        resultEl.className = 'rg-inline-result rg-inline-result--ok';
                    } else {
                        resultEl.textContent = (data.data && data.data.message) ? data.data.message : 'Error';
                        resultEl.className = 'rg-inline-result rg-inline-result--error';
                    }
                })
                .catch(function () {
                    resultEl.textContent = 'Request failed.';
                    resultEl.className = 'rg-inline-result rg-inline-result--error';
                })
                .finally(function () {
                    testBtn.disabled = false;
                });
        });
    }
    // B2 section toggle
    var b2Toggle = document.getElementById('rg-toggle-b2');
    var b2Section = document.getElementById('rg-b2-section');
    if (b2Toggle && b2Section) {
        b2Toggle.addEventListener('click', function () {
            b2Section.style.display = b2Section.style.display === 'none' ? 'block' : 'none';
        });
    }
}());
</script>
