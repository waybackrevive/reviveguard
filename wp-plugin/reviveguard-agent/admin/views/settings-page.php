<?php
defined('ABSPATH') || exit;

$connectionStatus  = (string) get_option('reviveguard_connection_status', 'pending');
$lastHeartbeatTs   = (int) get_option('reviveguard_last_heartbeat', 0);
$lastHeartbeat     = $lastHeartbeatTs > 0 ? wp_date('Y-m-d H:i:s', $lastHeartbeatTs) : __('Never', 'reviveguard-agent');
$apiUrl            = (string) get_option('reviveguard_api_base_url', REVIVEGUARD_API_BASE);
$b2KeyId           = (string) get_option('reviveguard_b2_key_id', '');
$b2BucketName      = (string) get_option('reviveguard_b2_bucket_name', '');
$b2PathPrefix      = (string) get_option('reviveguard_b2_path_prefix', 'reviveguard-backups');
$b2AppKeySet       = (string) get_option('reviveguard_b2_app_key', '') !== '';
$b2Configured      = $b2KeyId !== '' && $b2AppKeySet && $b2BucketName !== '';
$saved             = isset($_GET['saved']) && $_GET['saved'] === '1'; // phpcs:ignore WordPress.Security.NonceVerification
$tokenIsSet        = ReviveGuard_TokenStore::get() !== '';
$activeTab         = isset($_GET['rg_tab']) ? sanitize_key((string) wp_unslash($_GET['rg_tab'])) : 'connection'; // phpcs:ignore WordPress.Security.NonceVerification
if (! in_array($activeTab, ['connection', 'support'], true)) {
    // Legacy bookmark: advanced/logs → support
    $activeTab = in_array($activeTab, ['advanced', 'logs'], true) ? 'support' : 'connection';
}

$logTail = ReviveGuard_DebugLogger::tail(200);
$logText = $logTail['lines'] !== []
    ? implode("\n", $logTail['lines'])
    : ($logTail['exists']
        ? __('No log entries yet. Activity will appear here after heartbeats, backups, or updates.', 'reviveguard-agent')
        : __('No log file yet. It is created automatically when the agent runs.', 'reviveguard-agent'));

$statusLabels = [
    'connected'  => ['label' => __('Connected', 'reviveguard-agent'), 'class' => 'rg-status--ok'],
    'error'      => ['label' => __('Error', 'reviveguard-agent'), 'class' => 'rg-status--error'],
    'auth_error' => ['label' => __('Auth Error', 'reviveguard-agent'), 'class' => 'rg-status--error'],
    'pending'    => ['label' => __('Pending', 'reviveguard-agent'), 'class' => 'rg-status--pending'],
];
$statusInfo = $statusLabels[$connectionStatus] ?? $statusLabels['pending'];

$testNonce    = wp_create_nonce('reviveguard_test_heartbeat');
$adminPostUrl = esc_url(admin_url('admin-post.php'));
$basePageUrl  = admin_url('options-general.php?page=reviveguard-settings');

$memoryLimit   = (string) ini_get('memory_limit');
$maxExecTime   = (string) ini_get('max_execution_time');
$uploadMax     = (string) ini_get('upload_max_filesize');
$postMax       = (string) ini_get('post_max_size');
$disabledFns   = (string) ini_get('disable_functions');
$hasExec       = function_exists('exec') && (strpos($disabledFns, 'exec') === false);
$hasSystem     = function_exists('system') && (strpos($disabledFns, 'system') === false);
$wpDebug       = defined('WP_DEBUG') && WP_DEBUG;
$isMultisite   = is_multisite();
$abspathWritable = is_writable(ABSPATH);
$contentWritable = is_writable(WP_CONTENT_DIR);
$uploadsDir      = wp_upload_dir();
$uploadsWritable = ! empty($uploadsDir['basedir']) && is_writable($uploadsDir['basedir']);
?>
<div class="wrap rg-wrap">
    <h1><?php esc_html_e('ReviveGuard', 'reviveguard-agent'); ?></h1>
    <p class="rg-lead">
        <?php esc_html_e('Connect this site to your ReviveGuard portal. Most customers only need the connection code below.', 'reviveguard-agent'); ?>
    </p>

    <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved.', 'reviveguard-agent'); ?></p>
        </div>
    <?php endif; ?>

    <nav class="nav-tab-wrapper rg-tabs" aria-label="<?php esc_attr_e('ReviveGuard settings sections', 'reviveguard-agent'); ?>">
        <a href="<?php echo esc_url(add_query_arg('rg_tab', 'connection', $basePageUrl)); ?>"
           class="nav-tab <?php echo $activeTab === 'connection' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Connection', 'reviveguard-agent'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('rg_tab', 'support', $basePageUrl)); ?>"
           class="nav-tab <?php echo $activeTab === 'support' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Support', 'reviveguard-agent'); ?>
        </a>
    </nav>

    <?php if ($activeTab === 'connection'): ?>
        <div class="rg-status-card">
            <h2><?php esc_html_e('Connection status', 'reviveguard-agent'); ?></h2>
            <table class="rg-info-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'reviveguard-agent'); ?></th>
                    <td>
                        <span class="rg-status <?php echo esc_attr($statusInfo['class']); ?>">
                            <?php echo esc_html($statusInfo['label']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Last heartbeat', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html($lastHeartbeat); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Agent version', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html(REVIVEGUARD_VERSION); ?></td>
                </tr>
            </table>

            <button
                type="button"
                id="rg-test-heartbeat"
                class="button button-secondary"
                data-nonce="<?php echo esc_attr($testNonce); ?>"
                data-ajaxurl="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
            >
                <?php esc_html_e('Send test heartbeat', 'reviveguard-agent'); ?>
            </button>
            <span id="rg-heartbeat-result" class="rg-inline-result" aria-live="polite"></span>
        </div>

        <form method="post" action="<?php echo $adminPostUrl; ?>" class="rg-settings-form">
            <?php wp_nonce_field('reviveguard_save_settings'); ?>
            <input type="hidden" name="action" value="reviveguard_save_settings">
            <input type="hidden" name="rg_tab" value="connection">

            <div class="rg-panel">
                <h2><?php esc_html_e('Connect with your portal code', 'reviveguard-agent'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Open your ReviveGuard portal → open this site → Connection tab → copy the code and paste it here. Leave blank after it is saved unless you need to replace it.', 'reviveguard-agent'); ?>
                </p>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="reviveguard_agent_token"><?php esc_html_e('Connection code', 'reviveguard-agent'); ?></label>
                        </th>
                        <td>
                            <?php if ($tokenIsSet): ?>
                                <p class="rg-token-set-notice">
                                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                    <strong><?php esc_html_e('Connected code is saved.', 'reviveguard-agent'); ?></strong>
                                    <?php esc_html_e('Enter a new code below only to replace it.', 'reviveguard-agent'); ?>
                                </p>
                            <?php endif; ?>
                            <div class="rg-token-wrap">
                                <input
                                    type="password"
                                    id="reviveguard_agent_token"
                                    name="reviveguard_agent_token"
                                    class="regular-text"
                                    placeholder="<?php echo $tokenIsSet
                                        ? esc_attr__('Paste new code to replace', 'reviveguard-agent')
                                        : esc_attr__('Paste connection code from portal', 'reviveguard-agent'); ?>"
                                    autocomplete="new-password"
                                >
                                <button type="button" id="rg-reveal-token" class="button button-secondary">
                                    <?php esc_html_e('Show', 'reviveguard-agent'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php esc_html_e('Stored encrypted on this site. Do not share this code publicly.', 'reviveguard-agent'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save connection', 'reviveguard-agent')); ?>
            </div>
        </form>

    <?php else: ?>
        <div class="rg-panel">
            <div class="rg-logs-header">
                <div>
                    <h2><?php esc_html_e('System status', 'reviveguard-agent'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Share this screen with ReviveGuard support when something fails. Everything here is read-only.', 'reviveguard-agent'); ?>
                    </p>
                </div>
                <button type="button" id="rg-copy-support" class="button button-secondary"
                        data-copied="<?php esc_attr_e('Copied!', 'reviveguard-agent'); ?>"
                        data-copy="<?php esc_attr_e('Copy all for support', 'reviveguard-agent'); ?>">
                    <?php esc_html_e('Copy all for support', 'reviveguard-agent'); ?>
                </button>
            </div>

            <h3><?php esc_html_e('Connection', 'reviveguard-agent'); ?></h3>
            <table class="rg-info-table rg-info-table--wide">
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'reviveguard-agent'); ?></th>
                    <td>
                        <span class="rg-status <?php echo esc_attr($statusInfo['class']); ?>">
                            <?php echo esc_html($statusInfo['label']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Connection code', 'reviveguard-agent'); ?></th>
                    <td><?php echo $tokenIsSet ? esc_html__('Saved', 'reviveguard-agent') : esc_html__('Not set', 'reviveguard-agent'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Last heartbeat', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html($lastHeartbeat); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Platform URL', 'reviveguard-agent'); ?></th>
                    <td><code><?php echo esc_html($apiUrl !== '' ? $apiUrl : REVIVEGUARD_API_BASE); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cloud backup storage', 'reviveguard-agent'); ?></th>
                    <td>
                        <?php if ($b2Configured): ?>
                            <?php esc_html_e('Configured', 'reviveguard-agent'); ?>
                            <?php if ($b2BucketName !== ''): ?>
                                · <?php echo esc_html(sprintf(/* translators: %s bucket name */ __('bucket %s', 'reviveguard-agent'), $b2BucketName)); ?>
                            <?php endif; ?>
                            <?php if ($b2PathPrefix !== ''): ?>
                                · <?php echo esc_html($b2PathPrefix); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="rg-status rg-status--pending"><?php esc_html_e('Not configured', 'reviveguard-agent'); ?></span>
                            — <?php esc_html_e('ReviveGuard support sets this up for cloud backups.', 'reviveguard-agent'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Software', 'reviveguard-agent'); ?></h3>
            <table class="rg-info-table rg-info-table--wide">
                <tr>
                    <th scope="row"><?php esc_html_e('Agent version', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html(REVIVEGUARD_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('WordPress', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html((string) get_bloginfo('version')); ?><?php echo $isMultisite ? ' · ' . esc_html__('Multisite', 'reviveguard-agent') : ''; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('PHP', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html(PHP_VERSION); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Server software', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html(isset($_SERVER['SERVER_SOFTWARE']) ? (string) $_SERVER['SERVER_SOFTWARE'] : '—'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Site URL', 'reviveguard-agent'); ?></th>
                    <td><code><?php echo esc_html(home_url('/')); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('WP_DEBUG', 'reviveguard-agent'); ?></th>
                    <td><?php echo $wpDebug ? esc_html__('On', 'reviveguard-agent') : esc_html__('Off', 'reviveguard-agent'); ?></td>
                </tr>
            </table>

            <h3><?php esc_html_e('Hosting capabilities', 'reviveguard-agent'); ?></h3>
            <table class="rg-info-table rg-info-table--wide">
                <tr>
                    <th scope="row"><?php esc_html_e('PHP memory_limit', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html($memoryLimit !== '' ? $memoryLimit : '—'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('max_execution_time', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html($maxExecTime !== '' ? $maxExecTime : '—'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('upload_max_filesize / post_max_size', 'reviveguard-agent'); ?></th>
                    <td><?php echo esc_html($uploadMax . ' / ' . $postMax); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('exec() available', 'reviveguard-agent'); ?></th>
                    <td><?php echo $hasExec ? esc_html__('Yes', 'reviveguard-agent') : esc_html__('No', 'reviveguard-agent'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('system() available', 'reviveguard-agent'); ?></th>
                    <td><?php echo $hasSystem ? esc_html__('Yes', 'reviveguard-agent') : esc_html__('No', 'reviveguard-agent'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('ABSPATH writable', 'reviveguard-agent'); ?></th>
                    <td><?php echo $abspathWritable ? esc_html__('Yes', 'reviveguard-agent') : esc_html__('No', 'reviveguard-agent'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('wp-content writable', 'reviveguard-agent'); ?></th>
                    <td><?php echo $contentWritable ? esc_html__('Yes', 'reviveguard-agent') : esc_html__('No', 'reviveguard-agent'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Uploads writable', 'reviveguard-agent'); ?></th>
                    <td><?php echo $uploadsWritable ? esc_html__('Yes', 'reviveguard-agent') : esc_html__('No', 'reviveguard-agent'); ?></td>
                </tr>
            </table>

            <?php
            $supportSummary = [
                '=== ReviveGuard Support Snapshot ===',
                'Generated (UTC): ' . gmdate('Y-m-d H:i:s'),
                'Status: ' . $statusInfo['label'],
                'Connection code: ' . ($tokenIsSet ? 'Saved' : 'Not set'),
                'Last heartbeat: ' . $lastHeartbeat,
                'Platform URL: ' . ($apiUrl !== '' ? $apiUrl : REVIVEGUARD_API_BASE),
                'Cloud backup storage: ' . ($b2Configured ? ('Configured · bucket ' . $b2BucketName . ' · ' . $b2PathPrefix) : 'Not configured'),
                'Agent: ' . REVIVEGUARD_VERSION,
                'WordPress: ' . (string) get_bloginfo('version') . ($isMultisite ? ' (Multisite)' : ''),
                'PHP: ' . PHP_VERSION,
                'Server: ' . (isset($_SERVER['SERVER_SOFTWARE']) ? (string) $_SERVER['SERVER_SOFTWARE'] : '—'),
                'Site URL: ' . home_url('/'),
                'WP_DEBUG: ' . ($wpDebug ? 'On' : 'Off'),
                'memory_limit: ' . ($memoryLimit !== '' ? $memoryLimit : '—'),
                'max_execution_time: ' . ($maxExecTime !== '' ? $maxExecTime : '—'),
                'upload/post max: ' . $uploadMax . ' / ' . $postMax,
                'exec: ' . ($hasExec ? 'Yes' : 'No'),
                'system: ' . ($hasSystem ? 'Yes' : 'No'),
                'ABSPATH writable: ' . ($abspathWritable ? 'Yes' : 'No'),
                'wp-content writable: ' . ($contentWritable ? 'Yes' : 'No'),
                'Uploads writable: ' . ($uploadsWritable ? 'Yes' : 'No'),
                '',
                '=== Latest logs ===',
                $logText,
            ];
            $supportBlob = implode("\n", $supportSummary);
            ?>
            <textarea id="rg-support-blob" class="screen-reader-text" readonly aria-hidden="true"><?php echo esc_textarea($supportBlob); ?></textarea>

            <h3><?php esc_html_e('Latest logs', 'reviveguard-agent'); ?></h3>
            <p class="rg-log-meta">
                <?php
                echo esc_html(sprintf(
                    /* translators: 1: number of lines, 2: relative path hint */
                    __('Showing last %1$d lines · %2$s · view and copy only', 'reviveguard-agent'),
                    count($logTail['lines']),
                    $logTail['path_hint']
                ));
                ?>
            </p>

            <label class="screen-reader-text" for="rg-log-box"><?php esc_html_e('Agent debug log', 'reviveguard-agent'); ?></label>
            <textarea
                id="rg-log-box"
                class="rg-log-box"
                rows="16"
                readonly
                spellcheck="false"
            ><?php echo esc_textarea($logText); ?></textarea>

            <p class="description" style="margin-top:10px;">
                <?php esc_html_e('Use “Copy all for support” to send system status + logs in one paste. Logs cannot be edited or deleted here.', 'reviveguard-agent'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    'use strict';

    var revealBtn = document.getElementById('rg-reveal-token');
    var tokenField = document.getElementById('reviveguard_agent_token');
    if (revealBtn && tokenField) {
        revealBtn.addEventListener('click', function () {
            var showing = tokenField.type === 'text';
            tokenField.type = showing ? 'password' : 'text';
            revealBtn.textContent = showing ? 'Show' : 'Hide';
            if (! showing) {
                window.setTimeout(function () {
                    tokenField.type = 'password';
                    revealBtn.textContent = 'Show';
                }, 5000);
            }
        });
    }

    var testBtn = document.getElementById('rg-test-heartbeat');
    var resultEl = document.getElementById('rg-heartbeat-result');
    if (testBtn && resultEl) {
        testBtn.addEventListener('click', function () {
            testBtn.disabled = true;
            resultEl.textContent = 'Sending\u2026';
            resultEl.className = 'rg-inline-result';

            var formData = new FormData();
            formData.append('action', 'reviveguard_test_heartbeat');
            formData.append('_ajax_nonce', testBtn.getAttribute('data-nonce') || '');

            fetch(testBtn.getAttribute('data-ajaxurl') || '', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
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

    function copyText(text, btn) {
        var doneLabel = btn.getAttribute('data-copied') || 'Copied!';
        var copyLabel = btn.getAttribute('data-copy') || 'Copy';
        function markCopied() {
            btn.textContent = doneLabel;
            window.setTimeout(function () { btn.textContent = copyLabel; }, 2000);
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(markCopied).catch(function () {
                fallbackCopy(text, markCopied);
            });
        } else {
            fallbackCopy(text, markCopied);
        }
    }

    function fallbackCopy(text, onDone) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'absolute';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); onDone(); } catch (e) {}
        document.body.removeChild(ta);
    }

    var copySupportBtn = document.getElementById('rg-copy-support');
    var supportBlob = document.getElementById('rg-support-blob');
    if (copySupportBtn && supportBlob) {
        copySupportBtn.addEventListener('click', function () {
            copyText(supportBlob.value || '', copySupportBtn);
        });
    }
}());
</script>
