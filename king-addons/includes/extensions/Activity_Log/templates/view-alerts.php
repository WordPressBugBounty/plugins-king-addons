<?php
/**
 * Activity Log alerts view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$alerts = $settings['alerts'] ?? [];
$emails = $alerts['failed_login_emails'] ?? '';
if ($emails === '') {
    $emails = get_option('admin_email');
}
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-warning purple"></span>
        <h2><?php esc_html_e('Failed Login Alert', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="kng-activity-alert-form">
            <input type="hidden" name="action" value="kng_activity_log_save_alerts">
            <?php wp_nonce_field('kng_activity_log_alerts'); ?>

            <label class="ka-toggle">
                <input type="checkbox" name="failed_login_enabled" value="1" <?php checked(!empty($alerts['failed_login_enabled'])); ?> />
                <span class="ka-toggle-slider"></span>
                <span class="ka-toggle-label"><?php esc_html_e('Enable failed login alert', 'king-addons'); ?></span>
            </label>

            <div class="kng-activity-alert-grid">
                <div class="kng-field">
                    <label><?php esc_html_e('Threshold', 'king-addons'); ?></label>
                    <input type="number" min="1" max="50" name="failed_login_threshold" value="<?php echo esc_attr($alerts['failed_login_threshold'] ?? 5); ?>">
                    <small><?php esc_html_e('Failed logins before alert triggers.', 'king-addons'); ?></small>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Window (minutes)', 'king-addons'); ?></label>
                    <input type="number" min="1" max="60" name="failed_login_window" value="<?php echo esc_attr($alerts['failed_login_window'] ?? 10); ?>">
                    <small><?php esc_html_e('Rolling time window.', 'king-addons'); ?></small>
                </div>
                <div class="kng-field">
                    <label><?php esc_html_e('Email Recipients', 'king-addons'); ?></label>
                    <input type="text" name="failed_login_emails" value="<?php echo esc_attr($emails); ?>" placeholder="security@example.com">
                    <small><?php esc_html_e('Comma-separated list of emails.', 'king-addons'); ?></small>
                </div>
            </div>

            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Alert', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>
