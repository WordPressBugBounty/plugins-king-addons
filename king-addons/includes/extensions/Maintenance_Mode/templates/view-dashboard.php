<?php
/**
 * Maintenance Mode dashboard view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$enabled = !empty($settings['enabled']);
$mode = $settings['mode'] ?? 'coming_soon';
$template_source = $settings['template_source'] ?? 'built_in';
$template_label = $template_source === 'built_in'
    ? ($templates[$settings['template_id'] ?? 'minimal'] ?? __('Built-in', 'king-addons'))
    : ($template_source === 'elementor' ? __('Elementor Template', 'king-addons') : __('WordPress Page', 'king-addons'));

$schedule_enabled = !empty($settings['schedule_enabled']);
$schedule_start = $settings['schedule_start'] ? get_date_from_gmt($settings['schedule_start'], 'Y-m-d H:i') : '';
$schedule_end = $settings['schedule_end'] ? get_date_from_gmt($settings['schedule_end'], 'Y-m-d H:i') : '';
$schedule_status = $schedule_enabled
    ? ($schedule_start || $schedule_end ? __('Scheduled', 'king-addons') : __('Enabled', 'king-addons'))
    : __('Off', 'king-addons');

$schedule_windows_count = 0;
if (!empty($settings['schedule_windows']) && is_array($settings['schedule_windows'])) {
    $schedule_windows_count = count($settings['schedule_windows']);
}

$recurring_enabled = !empty($settings['recurring_enabled']);
$recurring_rules_count = 0;
if (!empty($settings['recurring_rules']) && is_array($settings['recurring_rules'])) {
    $recurring_rules_count = count($settings['recurring_rules']);
}

$ip_count = is_array($settings['whitelist_ips']) ? count($settings['whitelist_ips']) : 0;
$path_count = is_array($settings['whitelist_paths']) ? count($settings['whitelist_paths']) : 0;
?>

<div class="ka-stats-grid">
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Status', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo $enabled ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('Maintenance visibility', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Mode', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo $mode === 'maintenance' ? esc_html__('Maintenance', 'king-addons') : esc_html__('Coming Soon', 'king-addons'); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('HTTP response behavior', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Template', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html($template_label); ?></div>
        <div class="ka-stat-note"><?php esc_html_e('Selected page source', 'king-addons'); ?></div>
    </div>
    <div class="ka-stat-card">
        <div class="ka-stat-label"><?php esc_html_e('Schedule', 'king-addons'); ?></div>
        <div class="ka-stat-value"><?php echo esc_html($schedule_status); ?></div>
        <div class="ka-stat-note">
            <?php
            if (!$schedule_enabled) {
                esc_html_e('Automation is disabled', 'king-addons');
            } else {
                $parts = [];
                if ($schedule_windows_count > 0) {
                    $parts[] = sprintf(
                        /* translators: %d: number of windows */
                        _n('%d window', '%d windows', $schedule_windows_count, 'king-addons'),
                        $schedule_windows_count
                    );
                }
                if ($recurring_enabled) {
                    $parts[] = sprintf(
                        /* translators: %d: number of rules */
                        _n('%d recurring rule', '%d recurring rules', $recurring_rules_count, 'king-addons'),
                        $recurring_rules_count
                    );
                }
                echo esc_html($parts ? implode(' • ', $parts) : __('Legacy schedule window', 'king-addons'));
            }
            ?>
        </div>
    </div>
</div>

<div class="kng-maintenance-dashboard-grid">
    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-flag purple"></span>
            <h2><?php esc_html_e('Access Snapshot', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="kng-maintenance-stat-row">
                <span><?php esc_html_e('Whitelisted IPs', 'king-addons'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($ip_count)); ?></strong>
            </div>
            <div class="kng-maintenance-stat-row">
                <span><?php esc_html_e('Whitelisted Paths', 'king-addons'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($path_count)); ?></strong>
            </div>
            <div class="kng-maintenance-stat-row">
                <span><?php esc_html_e('Admins bypass', 'king-addons'); ?></span>
                <strong><?php echo !empty($settings['exclude_admin']) ? esc_html__('Yes', 'king-addons') : esc_html__('No', 'king-addons'); ?></strong>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-clock purple"></span>
            <h2><?php esc_html_e('Schedule Preview', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <?php if ($schedule_enabled && ($schedule_start || $schedule_end)) : ?>
                <div class="kng-maintenance-schedule-preview">
                    <div>
                        <span><?php esc_html_e('Start', 'king-addons'); ?></span>
                        <strong><?php echo esc_html($schedule_start ?: __('Not set', 'king-addons')); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e('End', 'king-addons'); ?></span>
                        <strong><?php echo esc_html($schedule_end ?: __('Not set', 'king-addons')); ?></strong>
                    </div>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('Scheduling is disabled. Activate a window to automate maintenance.', 'king-addons'); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'king-addons-maintenance-mode', 'view' => 'schedule'], admin_url('admin.php'))); ?>" class="ka-btn ka-btn-secondary ka-btn-sm">
                <?php esc_html_e('Edit Schedule', 'king-addons'); ?>
            </a>
        </div>
    </div>
</div>

<?php if (!$is_pro) : ?>
    <div class="ka-upgrade-card">
        <h3><?php esc_html_e('Upgrade to Maintenance Suite Pro', 'king-addons'); ?></h3>
        <p><?php esc_html_e('Unlock private site mode, advanced rules, analytics, and multi-schedule automation.', 'king-addons'); ?></p>
        <ul>
            <li><?php esc_html_e('Password and token access', 'king-addons'); ?></li>
            <li><?php esc_html_e('Device, country, and role rules', 'king-addons'); ?></li>
            <li><?php esc_html_e('Analytics and advanced SEO controls', 'king-addons'); ?></li>
        </ul>
        <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
        </a>
    </div>
<?php endif; ?>
