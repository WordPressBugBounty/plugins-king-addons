<?php
/**
 * Pricing Table Builder - Admin List Page.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$new_url = admin_url('admin.php?page=king-addons-pricing-tables&action=new');
?>
<div class="kng-v3-wrap">
    <div class="kng-v3-header">
        <div class="kng-v3-header-content">
            <h1 class="kng-v3-title"><?php esc_html_e('Pricing Tables', 'king-addons'); ?></h1>
            <p class="kng-v3-subtitle"><?php esc_html_e('Create beautiful pricing tables for your website', 'king-addons'); ?></p>
        </div>
        <div class="kng-v3-header-actions">
            <a href="<?php echo esc_url($new_url); ?>" class="kng-v3-btn kng-v3-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <?php esc_html_e('Create New Table', 'king-addons'); ?>
            </a>
        </div>
    </div>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
    <div class="kng-v3-notice kng-v3-notice-success">
        <p><?php esc_html_e('Pricing table deleted successfully.', 'king-addons'); ?></p>
    </div>
    <?php endif; ?>

    <div class="kng-v3-content">
        <?php if (empty($tables)): ?>
        <div class="kng-pt-empty">
            <div class="kng-pt-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="64" height="64">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <h2 class="kng-pt-empty-title"><?php esc_html_e('No Pricing Tables Yet', 'king-addons'); ?></h2>
            <p class="kng-pt-empty-text"><?php esc_html_e('Create your first pricing table to showcase your plans and pricing.', 'king-addons'); ?></p>
            <a href="<?php echo esc_url($new_url); ?>" class="kng-v3-btn kng-v3-btn-primary kng-v3-btn-lg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <?php esc_html_e('Create Your First Table', 'king-addons'); ?>
            </a>
        </div>
        <?php else: ?>
        <div class="kng-pt-grid-admin">
            <?php foreach ($tables as $table): 
                $config_json = get_post_meta($table->ID, \King_Addons\Pricing_Table_Builder::META_CONFIG, true);
                $config = json_decode($config_json, true);
                $plans_count = count($config['plans'] ?? []);
                $preset_id = $config['style']['preset_id'] ?? 'free_modern_cards';
                $is_published = $table->post_status === 'publish';
                
                $edit_url = admin_url('admin.php?page=king-addons-pricing-tables&action=edit&table_id=' . $table->ID);
                $delete_url = wp_nonce_url(admin_url('admin-post.php?action=king_addons_pt_delete&table_id=' . $table->ID), 'kng_pt_delete_' . $table->ID);
                $duplicate_url = wp_nonce_url(admin_url('admin-post.php?action=king_addons_pt_duplicate&table_id=' . $table->ID), 'kng_pt_duplicate_' . $table->ID);
            ?>
            <div class="kng-pt-table-card">
                <div class="kng-pt-table-card-header">
                    <h3 class="kng-pt-table-card-title"><?php echo esc_html($table->post_title); ?></h3>
                    <span class="kng-pt-status kng-pt-status--<?php echo $is_published ? 'published' : 'draft'; ?>">
                        <?php echo $is_published ? esc_html__('Published', 'king-addons') : esc_html__('Draft', 'king-addons'); ?>
                    </span>
                </div>
                <div class="kng-pt-table-card-meta">
                    <span class="kng-pt-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo esc_html(get_the_modified_date('M j, Y', $table->ID)); ?>
                    </span>
                    <span class="kng-pt-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <?php printf(_n('%d plan', '%d plans', $plans_count, 'king-addons'), $plans_count); ?>
                    </span>
                </div>
                <div class="kng-pt-table-card-shortcode">
                    <code class="kng-pt-shortcode" data-copy="[kng_pricing_table id=&quot;<?php echo esc_attr($table->ID); ?>&quot;]">[kng_pricing_table id="<?php echo esc_attr($table->ID); ?>"]</code>
                    <button type="button" class="kng-pt-copy-btn" title="<?php esc_attr_e('Copy shortcode', 'king-addons'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>
                <div class="kng-pt-table-card-actions">
                    <a href="<?php echo esc_url($edit_url); ?>" class="kng-v3-btn kng-v3-btn-secondary kng-v3-btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        <?php esc_html_e('Edit', 'king-addons'); ?>
                    </a>
                    <a href="<?php echo esc_url($duplicate_url); ?>" class="kng-v3-btn kng-v3-btn-outline kng-v3-btn-sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <?php esc_html_e('Duplicate', 'king-addons'); ?>
                    </a>
                    <a href="<?php echo esc_url($delete_url); ?>" class="kng-v3-btn kng-v3-btn-danger kng-v3-btn-sm" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this pricing table?', 'king-addons')); ?>')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        <?php esc_html_e('Delete', 'king-addons'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
