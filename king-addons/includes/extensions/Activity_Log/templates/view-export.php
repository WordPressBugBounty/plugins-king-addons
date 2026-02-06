<?php
/**
 * Activity Log export view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$export_url = wp_nonce_url(add_query_arg([
    'action' => 'kng_activity_log_export',
], admin_url('admin-post.php')), 'kng_activity_log_export');
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-download purple"></span>
        <h2><?php esc_html_e('Export CSV', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Export the current activity log into a CSV file. For filtered exports, use the Logs page filters first.', 'king-addons'); ?></p>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'king-addons-activity-log', 'view' => 'logs'], admin_url('admin.php'))); ?>" class="ka-btn ka-btn-secondary">
            <?php esc_html_e('Go to Logs', 'king-addons'); ?>
        </a>
        <a href="<?php echo esc_url($export_url); ?>" class="ka-btn ka-btn-primary">
            <?php esc_html_e('Download CSV', 'king-addons'); ?>
        </a>
    </div>
    <?php if (!$is_pro) : ?>
        <div class="ka-card-footer">
            <small><?php esc_html_e('Free exports are limited to the retention window (14 days).', 'king-addons'); ?></small>
        </div>
    <?php endif; ?>
</div>
