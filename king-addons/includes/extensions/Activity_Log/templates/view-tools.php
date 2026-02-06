<?php
/**
 * Activity Log tools view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$total_logs = $this->get_logs_count([]);
$retention_days = (int) ($settings['retention_days'] ?? 14);
if (!$is_pro) {
    $retention_days = min($retention_days, 14);
}
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-database purple"></span>
        <h2><?php esc_html_e('Database Summary', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <div class="kng-activity-summary-grid">
            <div>
                <span class="kng-activity-muted"><?php esc_html_e('Total log entries', 'king-addons'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($total_logs)); ?></strong>
            </div>
            <div>
                <span class="kng-activity-muted"><?php esc_html_e('Retention', 'king-addons'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($retention_days)); ?> <?php esc_html_e('days', 'king-addons'); ?></strong>
            </div>
        </div>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-trash purple"></span>
        <h2><?php esc_html_e('Purge Old Logs', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Run the retention cleanup now. This removes entries older than the configured retention window.', 'king-addons'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="kng_activity_log_purge">
            <?php wp_nonce_field('kng_activity_log_purge'); ?>
            <button type="submit" class="ka-btn ka-btn-secondary">
                <?php esc_html_e('Purge Now', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>
