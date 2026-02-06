<?php
/**
 * Pricing Table Builder - Admin Editor Page.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$list_url = admin_url('admin.php?page=king-addons-pricing-tables');
$save_url = admin_url('admin-post.php');
$table_id_val = $table ? $table->ID : 0;
$table_title = $table ? $table->post_title : '';
$is_new = $table_id_val === 0;
$page_title = $is_new ? __('Create Pricing Table', 'king-addons') : __('Edit Pricing Table', 'king-addons');
?>
<script>
// Pass initial config to JS BEFORE admin.js loads
window.kngPTInitialConfig = <?php echo wp_json_encode($config); ?>;
</script>
<div class="kng-v3-wrap kng-pt-editor">
    <div class="kng-v3-header">
        <div class="kng-v3-header-content">
            <a href="<?php echo esc_url($list_url); ?>" class="kng-pt-back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="kng-v3-title"><?php echo esc_html($page_title); ?></h1>
        </div>
        <div class="kng-v3-header-actions">
            <button type="button" class="kng-v3-btn kng-v3-btn-outline" id="kng-pt-preview-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?php esc_html_e('Preview', 'king-addons'); ?>
            </button>
            <button type="button" class="kng-v3-btn kng-v3-btn-secondary" id="kng-pt-save-draft">
                <?php esc_html_e('Save Draft', 'king-addons'); ?>
            </button>
            <button type="button" class="kng-v3-btn kng-v3-btn-primary" id="kng-pt-publish">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                <?php echo $is_new ? esc_html__('Publish', 'king-addons') : esc_html__('Update', 'king-addons'); ?>
            </button>
        </div>
    </div>

    <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
    <div class="kng-v3-notice kng-v3-notice-success">
        <p><?php esc_html_e('Pricing table saved successfully.', 'king-addons'); ?></p>
    </div>
    <?php endif; ?>

    <form id="kng-pt-form" method="post" action="<?php echo esc_url($save_url); ?>">
        <input type="hidden" name="action" value="king_addons_pt_save">
        <input type="hidden" name="table_id" value="<?php echo esc_attr($table_id_val); ?>">
        <input type="hidden" name="config" id="kng-pt-config-input" value="">
        <?php wp_nonce_field('kng_pt_save', 'kng_pt_nonce'); ?>

        <div class="kng-pt-editor-layout">
            <!-- Left Panel: Settings -->
            <div class="kng-pt-editor-sidebar">
                <!-- Tabs -->
                <div class="kng-pt-tabs">
                    <button type="button" class="kng-pt-tab is-active" data-tab="general">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <?php esc_html_e('General', 'king-addons'); ?>
                    </button>
                    <button type="button" class="kng-pt-tab" data-tab="billing">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <?php esc_html_e('Billing', 'king-addons'); ?>
                    </button>
                    <button type="button" class="kng-pt-tab" data-tab="plans">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <?php esc_html_e('Plans', 'king-addons'); ?>
                    </button>
                    <button type="button" class="kng-pt-tab" data-tab="features">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <?php esc_html_e('Features', 'king-addons'); ?>
                    </button>
                    <button type="button" class="kng-pt-tab" data-tab="styles">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        <?php esc_html_e('Styles', 'king-addons'); ?>
                    </button>
                    <button type="button" class="kng-pt-tab" data-tab="advanced">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                        <?php esc_html_e('Advanced', 'king-addons'); ?>
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="kng-pt-tab-panels">
                    
                    <!-- General Tab -->
                    <div class="kng-pt-tab-panel is-active" data-panel="general">
                        <div class="kng-v3-field">
                            <label class="kng-v3-label" for="kng-pt-name"><?php esc_html_e('Table Name', 'king-addons'); ?></label>
                            <input type="text" id="kng-pt-name" class="kng-v3-input" value="<?php echo esc_attr($config['table']['name'] ?? ''); ?>" placeholder="<?php esc_attr_e('My Pricing Table', 'king-addons'); ?>">
                        </div>
                        
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Layout Mode', 'king-addons'); ?></label>
                            <div class="kng-v3-radio-cards">
                                <label class="kng-v3-radio-card">
                                    <input type="radio" name="layout_mode" value="cards" <?php checked(($config['table']['layout']['mode'] ?? 'cards'), 'cards'); ?>>
                                    <span class="kng-v3-radio-card-content">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="2" y="4" width="6" height="16" rx="1"/><rect x="9" y="4" width="6" height="16" rx="1"/><rect x="16" y="4" width="6" height="16" rx="1"/></svg>
                                        <span><?php esc_html_e('Cards', 'king-addons'); ?></span>
                                    </span>
                                </label>
                                <label class="kng-v3-radio-card">
                                    <input type="radio" name="layout_mode" value="table" <?php checked(($config['table']['layout']['mode'] ?? 'cards'), 'table'); ?>>
                                    <span class="kng-v3-radio-card-content">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                                        <span><?php esc_html_e('Table', 'king-addons'); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="kng-v3-field-group">
                            <div class="kng-v3-field">
                                <label class="kng-v3-label" for="kng-pt-cols-desktop"><?php esc_html_e('Columns (Desktop)', 'king-addons'); ?></label>
                                <select id="kng-pt-cols-desktop" class="kng-v3-select">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected(($config['table']['layout']['columns_desktop'] ?? 3), $i); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label" for="kng-pt-cols-tablet"><?php esc_html_e('Columns (Tablet)', 'king-addons'); ?></label>
                                <select id="kng-pt-cols-tablet" class="kng-v3-select">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected(($config['table']['layout']['columns_tablet'] ?? 2), $i); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label" for="kng-pt-cols-mobile"><?php esc_html_e('Columns (Mobile)', 'king-addons'); ?></label>
                                <select id="kng-pt-cols-mobile" class="kng-v3-select">
                                    <?php for ($i = 1; $i <= 2; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected(($config['table']['layout']['columns_mobile'] ?? 1), $i); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="kng-v3-field-group">
                            <div class="kng-v3-field">
                                <label class="kng-v3-label" for="kng-pt-gap"><?php esc_html_e('Gap (px)', 'king-addons'); ?></label>
                                <input type="number" id="kng-pt-gap" class="kng-v3-input" value="<?php echo esc_attr($config['table']['layout']['gap'] ?? 24); ?>" min="0" max="100">
                            </div>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label" for="kng-pt-max-width"><?php esc_html_e('Max Width (px)', 'king-addons'); ?></label>
                                <input type="number" id="kng-pt-max-width" class="kng-v3-input" value="<?php echo esc_attr($config['table']['layout']['max_width'] ?? 1200); ?>" min="600" max="2000">
                            </div>
                        </div>

                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Alignment', 'king-addons'); ?></label>
                            <div class="kng-v3-btn-group">
                                <button type="button" class="kng-v3-btn-group-item <?php echo ($config['table']['layout']['alignment'] ?? 'center') === 'left' ? 'is-active' : ''; ?>" data-value="left">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                                </button>
                                <button type="button" class="kng-v3-btn-group-item <?php echo ($config['table']['layout']['alignment'] ?? 'center') === 'center' ? 'is-active' : ''; ?>" data-value="center">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="10" x2="6" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="18" y1="18" x2="6" y2="18"/></svg>
                                </button>
                                <button type="button" class="kng-v3-btn-group-item <?php echo ($config['table']['layout']['alignment'] ?? 'center') === 'right' ? 'is-active' : ''; ?>" data-value="right">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="21" y1="10" x2="7" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="7" y2="18"/></svg>
                                </button>
                            </div>
                            <input type="hidden" id="kng-pt-alignment" value="<?php echo esc_attr($config['table']['layout']['alignment'] ?? 'center'); ?>">
                        </div>
                    </div>

                    <!-- Billing Tab -->
                    <div class="kng-pt-tab-panel" data-panel="billing">
                        <div class="kng-v3-field">
                            <div class="kng-v3-toggle-wrap">
                                <span class="kng-v3-toggle-label"><?php esc_html_e('Enable Billing Toggle', 'king-addons'); ?></span>
                                <label class="kng-v3-toggle">
                                    <input type="checkbox" id="kng-pt-billing-enabled" <?php checked(!empty($config['billing']['enabled'])); ?>>
                                    <span class="kng-v3-toggle-slider"></span>
                                </label>
                            </div>
                            <p class="kng-v3-field-help"><?php esc_html_e('Show monthly/annual toggle for visitors', 'king-addons'); ?></p>
                        </div>

                        <div class="kng-pt-billing-options" style="<?php echo empty($config['billing']['enabled']) ? 'display:none' : ''; ?>">
                            <div class="kng-v3-field">
                                <label class="kng-v3-label"><?php esc_html_e('Toggle Style', 'king-addons'); ?></label>
                                <div class="kng-v3-radio-cards kng-v3-radio-cards-sm">
                                    <label class="kng-v3-radio-card">
                                        <input type="radio" name="billing_type" value="segmented" <?php checked(($config['billing']['type'] ?? 'segmented'), 'segmented'); ?>>
                                        <span class="kng-v3-radio-card-content"><?php esc_html_e('Segmented', 'king-addons'); ?></span>
                                    </label>
                                    <label class="kng-v3-radio-card">
                                        <input type="radio" name="billing_type" value="switch" <?php checked(($config['billing']['type'] ?? 'segmented'), 'switch'); ?>>
                                        <span class="kng-v3-radio-card-content"><?php esc_html_e('Switch', 'king-addons'); ?></span>
                                    </label>
                                    <label class="kng-v3-radio-card">
                                        <input type="radio" name="billing_type" value="tabs" <?php checked(($config['billing']['type'] ?? 'segmented'), 'tabs'); ?>>
                                        <span class="kng-v3-radio-card-content"><?php esc_html_e('Tabs', 'king-addons'); ?></span>
                                    </label>
                                </div>
                            </div>

                            <div class="kng-v3-divider"></div>
                            <h4 class="kng-v3-section-title"><?php esc_html_e('Billing Periods', 'king-addons'); ?></h4>
                            
                            <div id="kng-pt-periods-list" class="kng-pt-periods-list">
                                <?php 
                                $periods = $config['billing']['periods'] ?? [];
                                foreach ($periods as $index => $period): 
                                ?>
                                <div class="kng-pt-period-item" data-index="<?php echo $index; ?>">
                                    <div class="kng-pt-period-header">
                                        <span class="kng-pt-period-drag">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="8" y1="6" x2="8" y2="6"/><line x1="16" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="8" y2="12"/><line x1="16" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="8" y2="18"/><line x1="16" y1="18" x2="16" y2="18"/></svg>
                                        </span>
                                        <span class="kng-pt-period-name"><?php echo esc_html($period['label']); ?></span>
                                        <label class="kng-pt-period-default">
                                            <input type="radio" name="default_period" value="<?php echo esc_attr($period['key']); ?>" <?php checked(!empty($period['is_default'])); ?>>
                                            <?php esc_html_e('Default', 'king-addons'); ?>
                                        </label>
                                    </div>
                                    <div class="kng-pt-period-fields">
                                        <div class="kng-v3-field-group">
                                            <div class="kng-v3-field">
                                                <label class="kng-v3-label"><?php esc_html_e('Key', 'king-addons'); ?></label>
                                                <input type="text" class="kng-v3-input kng-pt-period-key" value="<?php echo esc_attr($period['key']); ?>">
                                            </div>
                                            <div class="kng-v3-field">
                                                <label class="kng-v3-label"><?php esc_html_e('Label', 'king-addons'); ?></label>
                                                <input type="text" class="kng-v3-input kng-pt-period-label" value="<?php echo esc_attr($period['label']); ?>">
                                            </div>
                                            <div class="kng-v3-field">
                                                <label class="kng-v3-label"><?php esc_html_e('Suffix', 'king-addons'); ?></label>
                                                <input type="text" class="kng-v3-input kng-pt-period-suffix" value="<?php echo esc_attr($period['suffix'] ?? ''); ?>" placeholder="/mo">
                                            </div>
                                        </div>
                                        <div class="kng-v3-field">
                                            <div class="kng-v3-toggle-wrap kng-v3-toggle-wrap-sm">
                                                <span class="kng-v3-toggle-label"><?php esc_html_e('Show Badge', 'king-addons'); ?></span>
                                                <label class="kng-v3-toggle">
                                                    <input type="checkbox" class="kng-pt-period-badge-enabled" <?php checked(!empty($period['badge']['enabled'])); ?>>
                                                    <span class="kng-v3-toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="kng-v3-field kng-pt-period-badge-text-wrap" style="<?php echo empty($period['badge']['enabled']) ? 'display:none' : ''; ?>">
                                            <label class="kng-v3-label"><?php esc_html_e('Badge Text', 'king-addons'); ?></label>
                                            <input type="text" class="kng-v3-input kng-pt-period-badge-text" value="<?php echo esc_attr($period['badge']['text'] ?? ''); ?>" placeholder="Save 16%">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="kng-v3-divider"></div>
                            <h4 class="kng-v3-section-title"><?php esc_html_e('Currency', 'king-addons'); ?></h4>
                            
                            <div class="kng-v3-field-group">
                                <div class="kng-v3-field">
                                    <label class="kng-v3-label" for="kng-pt-currency"><?php esc_html_e('Symbol', 'king-addons'); ?></label>
                                    <input type="text" id="kng-pt-currency" class="kng-v3-input" value="<?php echo esc_attr($config['billing']['currency'] ?? '$'); ?>" maxlength="5">
                                </div>
                                <div class="kng-v3-field">
                                    <label class="kng-v3-label" for="kng-pt-currency-pos"><?php esc_html_e('Position', 'king-addons'); ?></label>
                                    <select id="kng-pt-currency-pos" class="kng-v3-select">
                                        <option value="before" <?php selected(($config['billing']['currency_position'] ?? 'before'), 'before'); ?>><?php esc_html_e('Before ($99)', 'king-addons'); ?></option>
                                        <option value="after" <?php selected(($config['billing']['currency_position'] ?? 'before'), 'after'); ?>><?php esc_html_e('After (99$)', 'king-addons'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plans Tab -->
                    <div class="kng-pt-tab-panel" data-panel="plans">
                        <div class="kng-pt-plans-header">
                            <h4 class="kng-v3-section-title"><?php esc_html_e('Plans', 'king-addons'); ?></h4>
                            <button type="button" class="kng-v3-btn kng-v3-btn-secondary kng-v3-btn-sm" id="kng-pt-add-plan">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <?php esc_html_e('Add Plan', 'king-addons'); ?>
                            </button>
                        </div>

                        <div id="kng-pt-plans-list" class="kng-pt-plans-list">
                            <!-- Plans will be rendered by JS -->
                        </div>
                    </div>

                    <!-- Features Tab -->
                    <div class="kng-pt-tab-panel" data-panel="features">
                        <div class="kng-v3-field">
                            <label class="kng-v3-label"><?php esc_html_e('Features Display Mode', 'king-addons'); ?></label>
                            <div class="kng-v3-radio-cards kng-v3-radio-cards-sm">
                                <label class="kng-v3-radio-card">
                                    <input type="radio" name="features_mode" value="per_plan" <?php checked(($config['features']['mode'] ?? 'per_plan'), 'per_plan'); ?>>
                                    <span class="kng-v3-radio-card-content"><?php esc_html_e('Per Plan', 'king-addons'); ?></span>
                                </label>
                                <label class="kng-v3-radio-card">
                                    <input type="radio" name="features_mode" value="comparison" <?php checked(($config['features']['mode'] ?? 'per_plan'), 'comparison'); ?>>
                                    <span class="kng-v3-radio-card-content"><?php esc_html_e('Comparison', 'king-addons'); ?></span>
                                </label>
                            </div>
                            <p class="kng-v3-field-help"><?php esc_html_e('Per Plan: Each plan shows its own features. Comparison: Global feature list with per-plan availability.', 'king-addons'); ?></p>
                        </div>

                        <div class="kng-v3-field">
                            <div class="kng-v3-toggle-wrap">
                                <span class="kng-v3-toggle-label"><?php esc_html_e('Show Feature Icons', 'king-addons'); ?></span>
                                <label class="kng-v3-toggle">
                                    <input type="checkbox" id="kng-pt-show-icons" <?php checked(!empty($config['features']['show_icons'])); ?>>
                                    <span class="kng-v3-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Styles Tab -->
                    <div class="kng-pt-tab-panel" data-panel="styles">
                        <h4 class="kng-v3-section-title"><?php esc_html_e('Preset', 'king-addons'); ?></h4>
                        <div class="kng-pt-presets-grid" id="kng-pt-presets">
                            <?php foreach ($presets as $preset_id => $preset): ?>
                            <button type="button" 
                                    class="kng-pt-preset-card <?php echo ($config['style']['preset_id'] ?? 'free_modern_cards') === $preset_id ? 'is-active' : ''; ?> <?php echo !empty($preset['disabled']) ? 'is-disabled' : ''; ?>"
                                    data-preset="<?php echo esc_attr($preset_id); ?>"
                                    <?php echo !empty($preset['disabled']) ? 'disabled' : ''; ?>>
                                <span class="kng-pt-preset-name"><?php echo esc_html($preset['name']); ?></span>
                                <?php if (!empty($preset['is_pro'])): ?>
                                <span class="kng-pt-preset-badge kng-pt-badge-pro"><?php esc_html_e('PRO', 'king-addons'); ?></span>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="kng-v3-divider"></div>
                        <h4 class="kng-v3-section-title"><?php esc_html_e('Color Overrides', 'king-addons'); ?></h4>
                        
                        <div class="kng-pt-color-overrides">
                            <div class="kng-v3-field">
                                <label class="kng-v3-label"><?php esc_html_e('Accent Color', 'king-addons'); ?></label>
                                <input type="text" class="kng-v3-color-input" id="kng-pt-color-accent" value="<?php echo esc_attr($config['style']['tokens']['colors']['accent'] ?? ''); ?>" data-default-color="">
                            </div>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label"><?php esc_html_e('Card Background', 'king-addons'); ?></label>
                                <input type="text" class="kng-v3-color-input" id="kng-pt-color-card-bg" value="<?php echo esc_attr($config['style']['tokens']['colors']['card_bg'] ?? ''); ?>" data-default-color="">
                            </div>
                            <div class="kng-v3-field">
                                <label class="kng-v3-label"><?php esc_html_e('Text Color', 'king-addons'); ?></label>
                                <input type="text" class="kng-v3-color-input" id="kng-pt-color-text" value="<?php echo esc_attr($config['style']['tokens']['colors']['text'] ?? ''); ?>" data-default-color="">
                            </div>
                        </div>

                        <div class="kng-v3-divider"></div>
                        <h4 class="kng-v3-section-title"><?php esc_html_e('Custom CSS Class', 'king-addons'); ?></h4>
                        
                        <div class="kng-v3-field">
                            <input type="text" id="kng-pt-custom-class" class="kng-v3-input" value="<?php echo esc_attr($config['style']['overrides']['custom_css_class'] ?? ''); ?>" placeholder="my-custom-class">
                        </div>
                    </div>

                    <!-- Advanced Tab -->
                    <div class="kng-pt-tab-panel" data-panel="advanced">
                        <div class="kng-v3-field">
                            <div class="kng-v3-toggle-wrap">
                                <span class="kng-v3-toggle-label"><?php esc_html_e('Hide Billing Toggle', 'king-addons'); ?></span>
                                <label class="kng-v3-toggle">
                                    <input type="checkbox" id="kng-pt-hide-toggle" <?php checked(!empty($config['advanced']['hide_toggle'])); ?>>
                                    <span class="kng-v3-toggle-slider"></span>
                                </label>
                            </div>
                            <p class="kng-v3-field-help"><?php esc_html_e('Hide the toggle even if billing is enabled', 'king-addons'); ?></p>
                        </div>

                        <div class="kng-v3-field">
                            <label class="kng-v3-label" for="kng-pt-force-period"><?php esc_html_e('Force Period', 'king-addons'); ?></label>
                            <select id="kng-pt-force-period" class="kng-v3-select">
                                <option value=""><?php esc_html_e('None (use default)', 'king-addons'); ?></option>
                                <option value="monthly" <?php selected(($config['advanced']['force_period'] ?? ''), 'monthly'); ?>><?php esc_html_e('Monthly', 'king-addons'); ?></option>
                                <option value="annual" <?php selected(($config['advanced']['force_period'] ?? ''), 'annual'); ?>><?php esc_html_e('Annual', 'king-addons'); ?></option>
                            </select>
                            <p class="kng-v3-field-help"><?php esc_html_e('Force a specific billing period to be shown', 'king-addons'); ?></p>
                        </div>

                        <div class="kng-v3-field">
                            <div class="kng-v3-toggle-wrap">
                                <span class="kng-v3-toggle-label"><?php esc_html_e('Disable Animations', 'king-addons'); ?></span>
                                <label class="kng-v3-toggle">
                                    <input type="checkbox" id="kng-pt-disable-animations" <?php checked(!empty($config['advanced']['disable_animations'])); ?>>
                                    <span class="kng-v3-toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="kng-v3-divider"></div>
                        <h4 class="kng-v3-section-title"><?php esc_html_e('Shortcode', 'king-addons'); ?></h4>
                        
                        <?php if ($table_id_val > 0): ?>
                        <div class="kng-pt-shortcode-box">
                            <code class="kng-pt-shortcode-code">[kng_pricing_table id="<?php echo esc_attr($table_id_val); ?>"]</code>
                            <button type="button" class="kng-pt-copy-btn" data-copy='[kng_pricing_table id="<?php echo esc_attr($table_id_val); ?>"]'>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                        <?php else: ?>
                        <p class="kng-v3-field-help"><?php esc_html_e('Save the table first to get the shortcode', 'king-addons'); ?></p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Right Panel: Preview -->
            <div class="kng-pt-editor-preview">
                <div class="kng-pt-preview-header">
                    <span class="kng-pt-preview-label"><?php esc_html_e('Live Preview', 'king-addons'); ?></span>
                    <div class="kng-pt-preview-devices">
                        <button type="button" class="kng-pt-device-btn is-active" data-device="desktop" title="<?php esc_attr_e('Desktop', 'king-addons'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </button>
                        <button type="button" class="kng-pt-device-btn" data-device="tablet" title="<?php esc_attr_e('Tablet', 'king-addons'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                        </button>
                        <button type="button" class="kng-pt-device-btn" data-device="mobile" title="<?php esc_attr_e('Mobile', 'king-addons'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <div class="kng-pt-preview-frame" id="kng-pt-preview-frame">
                    <div class="kng-pt-preview-content" id="kng-pt-preview-content">
                        <!-- Preview rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Plan Item Template -->
<template id="kng-pt-plan-template">
    <div class="kng-pt-plan-item" data-plan-id="">
        <div class="kng-pt-plan-header">
            <span class="kng-pt-plan-drag">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="8" cy="6" r="1"/><circle cx="16" cy="6" r="1"/><circle cx="8" cy="12" r="1"/><circle cx="16" cy="12" r="1"/><circle cx="8" cy="18" r="1"/><circle cx="16" cy="18" r="1"/></svg>
            </span>
            <input type="text" class="kng-pt-plan-name-input kng-v3-input" placeholder="<?php esc_attr_e('Plan Name', 'king-addons'); ?>">
            <button type="button" class="kng-pt-plan-toggle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <button type="button" class="kng-pt-plan-delete" title="<?php esc_attr_e('Delete Plan', 'king-addons'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="kng-pt-plan-body">
            <div class="kng-v3-field">
                <label class="kng-v3-label"><?php esc_html_e('Subtitle', 'king-addons'); ?></label>
                <input type="text" class="kng-v3-input kng-pt-plan-subtitle" placeholder="<?php esc_attr_e('For individuals', 'king-addons'); ?>">
            </div>
            
            <div class="kng-v3-field-group">
                <div class="kng-v3-field">
                    <div class="kng-v3-toggle-wrap kng-v3-toggle-wrap-compact">
                        <span class="kng-v3-toggle-label"><?php esc_html_e('Highlight', 'king-addons'); ?></span>
                        <label class="kng-v3-toggle kng-v3-toggle-sm">
                            <input type="checkbox" class="kng-pt-plan-highlight">
                            <span class="kng-v3-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="kng-v3-field">
                    <div class="kng-v3-toggle-wrap kng-v3-toggle-wrap-compact">
                        <span class="kng-v3-toggle-label"><?php esc_html_e('Badge', 'king-addons'); ?></span>
                        <label class="kng-v3-toggle kng-v3-toggle-sm">
                            <input type="checkbox" class="kng-pt-plan-badge-enabled">
                            <span class="kng-v3-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="kng-pt-plan-badge-fields" style="display: none;">
                <div class="kng-v3-field">
                    <label class="kng-v3-label"><?php esc_html_e('Badge Text', 'king-addons'); ?></label>
                    <input type="text" class="kng-v3-input kng-pt-plan-badge-text" placeholder="<?php esc_attr_e('Popular', 'king-addons'); ?>">
                </div>
            </div>

            <div class="kng-v3-divider-sm"></div>
            <h5 class="kng-v3-subsection-title"><?php esc_html_e('Pricing', 'king-addons'); ?></h5>
            
            <div class="kng-pt-plan-pricing">
                <div class="kng-pt-plan-pricing-period" data-period="monthly">
                    <span class="kng-pt-pricing-period-label"><?php esc_html_e('Monthly', 'king-addons'); ?></span>
                    <div class="kng-v3-field-group">
                        <div class="kng-v3-field">
                            <input type="text" class="kng-v3-input kng-pt-plan-price" placeholder="29">
                        </div>
                        <div class="kng-v3-field">
                            <input type="text" class="kng-v3-input kng-pt-plan-note" placeholder="<?php esc_attr_e('billed monthly', 'king-addons'); ?>">
                        </div>
                    </div>
                </div>
                <div class="kng-pt-plan-pricing-period" data-period="annual">
                    <span class="kng-pt-pricing-period-label"><?php esc_html_e('Annual', 'king-addons'); ?></span>
                    <div class="kng-v3-field-group">
                        <div class="kng-v3-field">
                            <input type="text" class="kng-v3-input kng-pt-plan-price" placeholder="290">
                        </div>
                        <div class="kng-v3-field">
                            <input type="text" class="kng-v3-input kng-pt-plan-note" placeholder="<?php esc_attr_e('billed annually', 'king-addons'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="kng-v3-divider-sm"></div>
            <h5 class="kng-v3-subsection-title"><?php esc_html_e('Call to Action', 'king-addons'); ?></h5>
            
            <div class="kng-v3-field-group">
                <div class="kng-v3-field">
                    <label class="kng-v3-label"><?php esc_html_e('Button Text', 'king-addons'); ?></label>
                    <input type="text" class="kng-v3-input kng-pt-plan-cta-text" placeholder="<?php esc_attr_e('Get Started', 'king-addons'); ?>">
                </div>
                <div class="kng-v3-field">
                    <label class="kng-v3-label"><?php esc_html_e('URL', 'king-addons'); ?></label>
                    <input type="url" class="kng-v3-input kng-pt-plan-cta-url" placeholder="https://">
                </div>
            </div>
            <div class="kng-v3-field-group">
                <div class="kng-v3-field">
                    <label class="kng-v3-label"><?php esc_html_e('Style', 'king-addons'); ?></label>
                    <select class="kng-v3-select kng-pt-plan-cta-style">
                        <option value="primary"><?php esc_html_e('Primary', 'king-addons'); ?></option>
                        <option value="secondary"><?php esc_html_e('Secondary', 'king-addons'); ?></option>
                        <option value="outline"><?php esc_html_e('Outline', 'king-addons'); ?></option>
                    </select>
                </div>
                <div class="kng-v3-field">
                    <label class="kng-v3-label"><?php esc_html_e('Target', 'king-addons'); ?></label>
                    <select class="kng-v3-select kng-pt-plan-cta-target">
                        <option value="_self"><?php esc_html_e('Same Window', 'king-addons'); ?></option>
                        <option value="_blank"><?php esc_html_e('New Window', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>

            <div class="kng-v3-divider-sm"></div>
            <div class="kng-pt-plan-features-header">
                <h5 class="kng-v3-subsection-title"><?php esc_html_e('Features', 'king-addons'); ?></h5>
                <button type="button" class="kng-v3-btn kng-v3-btn-text kng-v3-btn-xs kng-pt-add-feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <?php esc_html_e('Add', 'king-addons'); ?>
                </button>
            </div>
            
            <div class="kng-pt-plan-features-list">
                <!-- Features rendered by JS -->
            </div>
        </div>
    </div>
</template>

<!-- Feature Item Template -->
<template id="kng-pt-feature-template">
    <div class="kng-pt-feature-item">
        <span class="kng-pt-feature-drag">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="8" cy="6" r="1"/><circle cx="16" cy="6" r="1"/><circle cx="8" cy="12" r="1"/><circle cx="16" cy="12" r="1"/></svg>
        </span>
        <input type="text" class="kng-v3-input kng-v3-input-sm kng-pt-feature-text" placeholder="<?php esc_attr_e('Feature name', 'king-addons'); ?>">
        <select class="kng-v3-select kng-v3-select-sm kng-pt-feature-state">
            <option value="enabled"><?php esc_html_e('✓', 'king-addons'); ?></option>
            <option value="disabled"><?php esc_html_e('✗', 'king-addons'); ?></option>
        </select>
        <button type="button" class="kng-pt-feature-delete">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
</template>
