<?php
/**
 * Maintenance Mode analytics view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$overview = $this->get_analytics_overview();

$reason_labels = [
    'wp_admin' => __('WP Admin', 'king-addons'),
    'admin_ajax_allowed' => __('Admin AJAX allowed', 'king-addons'),
    'rest_logged_in' => __('REST (logged-in user)', 'king-addons'),
    'rest_allowed' => __('REST allowed', 'king-addons'),
    'login_page' => __('Login page', 'king-addons'),
    'elementor_editor' => __('Elementor editor', 'king-addons'),
    'user_allowed' => __('Allowed user/role', 'king-addons'),
    'ip_whitelist' => __('IP whitelist', 'king-addons'),
    'path_whitelist' => __('Path whitelist', 'king-addons'),
    'cron' => __('WP-Cron', 'king-addons'),
    'wp_cli' => __('WP-CLI', 'king-addons'),
];

$bypass_total = $overview['bypass_by_reason_total'] ?? [];
if (!is_array($bypass_total)) {
    $bypass_total = [];
}

$bypass_24h = $overview['bypass_by_reason_24h'] ?? [];
if (!is_array($bypass_24h)) {
    $bypass_24h = [];
}

$top_blocked_paths = $overview['top_paths_24h_blocked'] ?? [];
if (!is_array($top_blocked_paths)) {
    $top_blocked_paths = [];
}

$top_bypass_paths = $overview['top_paths_24h_bypass'] ?? [];
if (!is_array($top_bypass_paths)) {
    $top_bypass_paths = [];
}

$reset_url = wp_nonce_url(add_query_arg([
    'action' => 'kng_maintenance_reset_analytics',
], admin_url('admin-post.php')), 'kng_maintenance_reset_analytics');
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-chart-area purple"></span>
        <h2><?php esc_html_e('Analytics Overview', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <div class="ka-stats-grid" style="margin-top: 0;">
            <div class="ka-stat-card">
                <div class="ka-stat-label"><?php esc_html_e('Blocked visits', 'king-addons'); ?></div>
                <div class="ka-stat-value"><?php echo esc_html(number_format_i18n((int) $overview['blocked_total'])); ?></div>
                <div class="ka-stat-note">
                    <?php
                    echo esc_html(sprintf(
                        /* translators: %s: 24h count */
                        __('Last 24h: %s', 'king-addons'),
                        number_format_i18n((int) $overview['blocked_24h'])
                    ));
                    ?>
                </div>
            </div>

            <div class="ka-stat-card">
                <div class="ka-stat-label"><?php esc_html_e('Unique visitors', 'king-addons'); ?></div>
                <div class="ka-stat-value"><?php echo esc_html(number_format_i18n((int) $overview['unique_24h'])); ?></div>
                <div class="ka-stat-note"><?php esc_html_e('Unique blocked IPs in the last 24 hours (hashed).', 'king-addons'); ?></div>
            </div>

            <div class="ka-stat-card">
                <div class="ka-stat-label"><?php esc_html_e('Bypass usage', 'king-addons'); ?></div>
                <div class="ka-stat-value"><?php echo esc_html(number_format_i18n((int) $overview['bypass_total'])); ?></div>
                <div class="ka-stat-note">
                    <?php
                    echo esc_html(sprintf(
                        /* translators: %s: 24h count */
                        __('Last 24h: %s', 'king-addons'),
                        number_format_i18n((int) $overview['bypass_24h'])
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="kng-maintenance-section" style="margin-top: 16px;">
            <h3 style="margin: 0 0 10px;"><?php esc_html_e('Bypass breakdown', 'king-addons'); ?></h3>

            <?php if (empty($bypass_total) && empty($bypass_24h)) : ?>
                <p class="description" style="margin: 0;">
                    <?php esc_html_e('No bypass events tracked yet.', 'king-addons'); ?>
                </p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Reason', 'king-addons'); ?></th>
                            <th style="width: 140px;"><?php esc_html_e('Total', 'king-addons'); ?></th>
                            <th style="width: 140px;"><?php esc_html_e('Last 24h', 'king-addons'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $reasons = array_unique(array_merge(array_keys($bypass_total), array_keys($bypass_24h)));
                        foreach ($reasons as $reason) :
                            $label = $reason_labels[$reason] ?? $reason;
                            $total = (int) ($bypass_total[$reason] ?? 0);
                            $last24 = (int) ($bypass_24h[$reason] ?? 0);
                            if ($total === 0 && $last24 === 0) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td><?php echo esc_html(number_format_i18n($total)); ?></td>
                                <td><?php echo esc_html(number_format_i18n($last24)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="kng-maintenance-section" style="margin-top: 16px;">
            <h3 style="margin: 0 0 10px;"><?php esc_html_e('Top paths (last 24h)', 'king-addons'); ?></h3>
            <p class="description" style="margin: 0 0 10px;">
                <?php esc_html_e('Paths are privacy-masked and stored only as hashed keys + masked samples.', 'king-addons'); ?>
            </p>

            <div class="kng-maintenance-content-grid" style="margin-bottom: 0;">
                <div>
                    <h4 style="margin: 0 0 8px;"><?php esc_html_e('Blocked', 'king-addons'); ?></h4>
                    <?php if (empty($top_blocked_paths)) : ?>
                        <p class="description" style="margin: 0;"><?php esc_html_e('No blocked paths tracked yet.', 'king-addons'); ?></p>
                    <?php else : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Path', 'king-addons'); ?></th>
                                    <th style="width: 120px;"><?php esc_html_e('Hits', 'king-addons'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_blocked_paths as $row) :
                                    $mask = isset($row['mask']) ? (string) $row['mask'] : '';
                                    $count = (int) ($row['count'] ?? 0);
                                    if ($mask === '' || $count <= 0) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><code><?php echo esc_html($mask); ?></code></td>
                                        <td><?php echo esc_html(number_format_i18n($count)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div>
                    <h4 style="margin: 0 0 8px;"><?php esc_html_e('Bypass', 'king-addons'); ?></h4>
                    <?php if (empty($top_bypass_paths)) : ?>
                        <p class="description" style="margin: 0;"><?php esc_html_e('No bypass paths tracked yet.', 'king-addons'); ?></p>
                    <?php else : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Path', 'king-addons'); ?></th>
                                    <th style="width: 120px;"><?php esc_html_e('Hits', 'king-addons'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_bypass_paths as $row) :
                                    $mask = isset($row['mask']) ? (string) $row['mask'] : '';
                                    $count = (int) ($row['count'] ?? 0);
                                    if ($mask === '' || $count <= 0) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><code><?php echo esc_html($mask); ?></code></td>
                                        <td><?php echo esc_html(number_format_i18n($count)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <p class="kng-maintenance-note" style="margin-top: 12px;">
            <?php esc_html_e('Counts are updated when maintenance mode is active and a front-end request is blocked or bypassed.', 'king-addons'); ?>
        </p>

        <div class="kng-maintenance-actions" style="margin-top: 12px;">
            <a href="<?php echo esc_url($reset_url); ?>" class="ka-btn ka-btn-secondary" onclick="return confirm('<?php echo esc_js(__('Reset analytics? This cannot be undone.', 'king-addons')); ?>');">
                <?php esc_html_e('Reset analytics', 'king-addons'); ?>
            </a>
        </div>
    </div>
</div>
