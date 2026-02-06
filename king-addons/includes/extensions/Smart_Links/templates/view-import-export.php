<?php
/**
 * Smart Links import/export view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$export_url = wp_nonce_url(
    add_query_arg([
        'action' => 'kng_smart_links_export',
    ], admin_url('admin-post.php')),
    'kng_smart_links_export'
);
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-download green"></span>
        <h2><?php esc_html_e('Export Links', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Download all smart links as CSV.', 'king-addons'); ?></p>
        <a href="<?php echo esc_url($export_url); ?>" class="ka-btn ka-btn-primary">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export CSV', 'king-addons'); ?>
        </a>
    </div>
</div>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-upload green"></span>
        <h2><?php esc_html_e('Import Links', 'king-addons'); ?></h2>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('CSV columns: destination_url, slug (optional), title (optional), tags (optional).', 'king-addons'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="kng_smart_links_import">
            <?php wp_nonce_field('kng_smart_links_import'); ?>
            <input type="file" name="import_file" accept=".csv" required>
            <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" style="margin-left:12px;">
                <?php esc_html_e('Import CSV', 'king-addons'); ?>
            </button>
        </form>
    </div>
</div>

<div class="ka-card ka-smart-links-pro-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-lock pink"></span>
        <h2><?php esc_html_e('JSON Export & API', 'king-addons'); ?></h2>
        <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
    </div>
    <div class="ka-card-body">
        <p><?php esc_html_e('Export full settings, rules, and deep link templates as JSON. Create API keys for external integrations.', 'king-addons'); ?></p>
    </div>
</div>
