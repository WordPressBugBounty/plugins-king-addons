<?php
/**
 * Data Table Builder import/export view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-download purple"></span>
        <h2><?php esc_html_e('Export CSV', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Export any table from the All Tables list.', 'king-addons'); ?></p>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'king-addons-table-builder', 'view' => 'tables'], admin_url('admin.php'))); ?>" class="ka-btn ka-btn-secondary">
            <?php esc_html_e('Go to Tables', 'king-addons'); ?>
        </a>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-upload purple"></span>
        <h2><?php esc_html_e('Import CSV', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="kng_table_import">
            <?php wp_nonce_field('kng_table_import'); ?>
            <input type="file" name="import_file" accept=".csv" required>
            <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" style="margin-left:12px;">
                <?php esc_html_e('Import CSV', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-lock pink"></span>
        <h2><?php esc_html_e('Pro Imports & Exports', 'king-addons'); ?></h2>
        <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
    </div>
    <div class="ka-card-body">
        <ul>
            <li><?php esc_html_e('Google Sheets sync', 'king-addons'); ?></li>
            <li><?php esc_html_e('JSON import/export', 'king-addons'); ?></li>
            <li><?php esc_html_e('PDF export and shareable presets', 'king-addons'); ?></li>
        </ul>
        <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
            <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
        </a>
    </div>
</div>
