<?php
/**
 * Maintenance Mode import/export view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$export_url = wp_nonce_url(add_query_arg([
    'action' => 'kng_maintenance_export',
], admin_url('admin-post.php')), 'kng_maintenance_export');
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-download purple"></span>
        <h2><?php esc_html_e('Export Configuration', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Download your Maintenance Mode configuration as JSON.', 'king-addons'); ?></p>
        <a href="<?php echo esc_url($export_url); ?>" class="ka-btn ka-btn-primary">
            <?php esc_html_e('Download JSON', 'king-addons'); ?>
        </a>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-upload purple"></span>
        <h2><?php esc_html_e('Import Configuration', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="kng_maintenance_import">
            <?php wp_nonce_field('kng_maintenance_import'); ?>
            <input type="file" name="import_file" accept="application/json" required>
            <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" style="margin-left:12px;">
                <?php esc_html_e('Import JSON', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>
