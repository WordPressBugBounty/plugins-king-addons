<?php
/**
 * Docs & Knowledge Base admin page template.
 *
 * @package King_Addons
 * @var array $options Current options
 * @var bool $is_premium Whether premium is active
 */

if (!defined('ABSPATH')) {
    exit;
}

$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$pages = get_pages(['post_status' => 'publish']);
$roles = wp_roles()->get_names();

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}
?>
<style>
/* Page specific styles */
.ka-docs-kb-page .ka-admin-wrap {
    --ka-accent: #0066ff;
    --ka-accent-hover: #0052cc;
}

/* Layout Preview */
.ka-layout-preview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 16px;
}

.ka-layout-option {
    position: relative;
    border: 2px solid #e5e5ea;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ka-layout-option:hover {
    border-color: #c5c5ca;
}

.ka-layout-option.active {
    border-color: var(--ka-accent);
}

.ka-layout-option input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.ka-layout-preview-img {
    width: 100%;
    height: 120px;
    background: #f5f5f7;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.ka-layout-preview-img svg {
    width: 100%;
    height: 100%;
    max-width: 160px;
}

.ka-layout-preview-label {
    padding: 12px;
    text-align: center;
    font-weight: 500;
    color: #1d1d1f;
    background: #fff;
    border-top: 1px solid #f1f1f4;
}

.ka-layout-option.active .ka-layout-preview-label {
    color: var(--ka-accent);
}

/* Box Layout Preview */
.ka-layout-box-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.ka-layout-box-item {
    background: #fff;
    border: 1px solid #e5e5ea;
    border-radius: 8px;
    padding: 10px;
}

.ka-layout-box-item-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}

.ka-layout-box-icon {
    width: 16px;
    height: 16px;
    background: var(--ka-accent);
    border-radius: 4px;
}

.ka-layout-box-title {
    height: 6px;
    width: 50px;
    background: #1d1d1f;
    border-radius: 3px;
}

.ka-layout-box-line {
    height: 4px;
    background: #e5e5ea;
    border-radius: 2px;
    margin-bottom: 4px;
}

.ka-layout-box-line:last-child {
    width: 70%;
}

/* Card Layout Preview */
.ka-layout-card-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.ka-layout-card-item {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 12px;
    text-align: center;
}

.ka-layout-card-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--ka-accent), #5ac8fa);
    border-radius: 10px;
    margin: 0 auto 8px;
}

.ka-layout-card-title {
    height: 6px;
    width: 60%;
    background: #1d1d1f;
    border-radius: 3px;
    margin: 0 auto 6px;
}

.ka-layout-card-count {
    height: 4px;
    width: 30px;
    background: #86868b;
    border-radius: 2px;
    margin: 0 auto;
}

/* Modern Layout Preview */
.ka-layout-modern-preview {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ka-layout-modern-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}

.ka-layout-modern-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--ka-accent), #5ac8fa);
    border-radius: 10px;
    flex-shrink: 0;
}

.ka-layout-modern-content {
    flex: 1;
}

.ka-layout-modern-title {
    height: 6px;
    width: 80px;
    background: #1d1d1f;
    border-radius: 3px;
    margin-bottom: 4px;
}

.ka-layout-modern-desc {
    height: 4px;
    width: 120px;
    background: #e5e5ea;
    border-radius: 2px;
}

.ka-layout-modern-arrow {
    width: 16px;
    height: 16px;
    color: #86868b;
}

/* Pro Badge */
.ka-pro-badge-inline {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    text-transform: uppercase;
}

/* Stats Cards */
.ka-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.ka-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

.ka-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1d1d1f;
    margin-bottom: 4px;
}

.ka-stat-label {
    font-size: 13px;
    color: #6e6e73;
}

.ka-stat-card--accent .ka-stat-value {
    color: var(--ka-accent);
}

/* Dark Mode */
.ka-v3-dark .ka-layout-option {
    border-color: #3a3a3a;
    background: #1e1e1e;
}

.ka-v3-dark .ka-layout-option:hover {
    border-color: #4a4a4a;
}

.ka-v3-dark .ka-layout-option.active {
    border-color: var(--ka-accent);
}

.ka-v3-dark .ka-layout-preview-img {
    background: #141414;
}

.ka-v3-dark .ka-layout-preview-label {
    background: #1e1e1e;
    color: #f5f5f7;
    border-color: #2a2a2a;
}

.ka-v3-dark .ka-layout-box-item,
.ka-v3-dark .ka-layout-card-item,
.ka-v3-dark .ka-layout-modern-item {
    background: #2a2a2a;
    border-color: #3a3a3a;
}

.ka-v3-dark .ka-layout-box-title,
.ka-v3-dark .ka-layout-card-title,
.ka-v3-dark .ka-layout-modern-title {
    background: #f5f5f7;
}

.ka-v3-dark .ka-layout-box-line,
.ka-v3-dark .ka-layout-modern-desc {
    background: #4a4a4a;
}

.ka-v3-dark .ka-stat-card {
    background: #1e1e1e;
}

.ka-v3-dark .ka-stat-value {
    color: #f5f5f7;
}

/* Save Button */
.ka-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(0, 102, 255, 0.25);
}

.ka-save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 102, 255, 0.35);
}

.ka-save-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.ka-card-footer {
    background: transparent;
    box-shadow: none;
    padding: 24px 0;
}
</style>

<div class="wrap ka-docs-kb-page">
    <script>
    (function() {
        const mode = '<?php echo esc_js($theme_mode); ?>';
        const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
        document.body.classList.toggle('ka-v3-dark', isDark);
    })();
    </script>

    <div class="ka-admin-wrap">
        <!-- Header -->
        <div class="ka-admin-header">
            <div class="ka-admin-header-left">
                <div class="ka-admin-header-icon" style="background: linear-gradient(135deg, #0066ff 0%, #5ac8fa 100%);">
                    <span class="dashicons dashicons-book-alt"></span>
                </div>
                <div>
                    <h1 class="ka-admin-title"><?php esc_html_e('Docs & Knowledge Base', 'king-addons'); ?></h1>
                    <p class="ka-admin-subtitle"><?php esc_html_e('Create beautiful documentation for your website', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="ka-admin-header-actions">
                <?php if (!empty($options['enabled'])): ?>
                <span class="ka-status-badge enabled">
                    <span class="ka-status-badge-dot"></span>
                    <?php esc_html_e('Enabled', 'king-addons'); ?>
                </span>
                <?php else: ?>
                <span class="ka-status-badge disabled">
                    <span class="ka-status-badge-dot"></span>
                    <?php esc_html_e('Disabled', 'king-addons'); ?>
                </span>
                <?php endif; ?>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . \King_Addons\Docs_KB::POST_TYPE)); ?>" class="ka-btn ka-btn-secondary">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php esc_html_e('All Docs', 'king-addons'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . \King_Addons\Docs_KB::POST_TYPE)); ?>" class="ka-btn ka-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add New Doc', 'king-addons'); ?>
                </a>
                <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="<?php echo esc_attr(esc_html__('Theme', 'king-addons')); ?>" data-active="<?php echo esc_attr($theme_mode); ?>">
                    <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
                    <button type="button" class="ka-v3-segmented-btn" data-theme="light" aria-pressed="<?php echo $theme_mode === 'light' ? 'true' : 'false'; ?>">
                        <span class="ka-v3-segmented-icon" aria-hidden="true">☀︎</span>
                        <?php esc_html_e('Light', 'king-addons'); ?>
                    </button>
                    <button type="button" class="ka-v3-segmented-btn" data-theme="dark" aria-pressed="<?php echo $theme_mode === 'dark' ? 'true' : 'false'; ?>">
                        <span class="ka-v3-segmented-icon" aria-hidden="true">☾</span>
                        <?php esc_html_e('Dark', 'king-addons'); ?>
                    </button>
                    <button type="button" class="ka-v3-segmented-btn" data-theme="auto" aria-pressed="<?php echo $theme_mode === 'auto' ? 'true' : 'false'; ?>">
                        <span class="ka-v3-segmented-icon" aria-hidden="true">◐</span>
                        <?php esc_html_e('Auto', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </div>

        <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible" style="margin: 20px 0;">
            <p><?php esc_html_e('Settings saved successfully.', 'king-addons'); ?></p>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <?php
        $total_docs = wp_count_posts(\King_Addons\Docs_KB::POST_TYPE)->publish ?? 0;
        $total_categories = wp_count_terms(['taxonomy' => \King_Addons\Docs_KB::TAXONOMY, 'hide_empty' => false]);
        $total_views = 0;
        $total_feedback = 0;
        
        if ($is_premium && !empty($options['analytics_enabled'])) {
            global $wpdb;
            $total_views = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_kng_doc_views'
            )) ?: 0;
        }
        ?>
        <div class="ka-stats-grid">
            <div class="ka-stat-card">
                <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($total_docs)); ?></div>
                <div class="ka-stat-label"><?php esc_html_e('Total Articles', 'king-addons'); ?></div>
            </div>
            <div class="ka-stat-card">
                <div class="ka-stat-value"><?php echo esc_html(number_format_i18n($total_categories)); ?></div>
                <div class="ka-stat-label"><?php esc_html_e('Categories', 'king-addons'); ?></div>
            </div>
            <div class="ka-stat-card ka-stat-card--accent">
                <div class="ka-stat-value"><?php echo $is_premium ? esc_html(number_format_i18n($total_views)) : '—'; ?></div>
                <div class="ka-stat-label"><?php esc_html_e('Total Views', 'king-addons'); ?> <?php if (!$is_premium): ?><span class="ka-pro-badge-inline">PRO</span><?php endif; ?></div>
            </div>
            <div class="ka-stat-card">
                <div class="ka-stat-value"><?php echo $is_premium && !empty($options['search_enabled']) ? esc_html(count(get_option('king_addons_docs_search_logs', []))) : '—'; ?></div>
                <div class="ka-stat-label"><?php esc_html_e('Searches', 'king-addons'); ?> <?php if (!$is_premium): ?><span class="ka-pro-badge-inline">PRO</span><?php endif; ?></div>
            </div>
        </div>

        <!-- Settings Form -->
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="king_addons_docs_kb_save">
            <?php wp_nonce_field('king_addons_docs_kb_save', 'king_docs_kb_nonce'); ?>

            <!-- General Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Documentation', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="enabled" value="1" <?php checked(!empty($options['enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show docs section on your site', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Documentation Slug', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: #6e6e73;"><?php echo esc_html(home_url('/')); ?></span>
                                    <input type="text" name="docs_slug" value="<?php echo esc_attr($options['docs_slug']); ?>" style="width: 120px;">
                                </div>
                                <p class="ka-row-desc"><?php esc_html_e('URL slug for your documentation', 'king-addons'); ?></p>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Main Docs Page', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <select name="main_page_id">
                                    <option value="0"><?php esc_html_e('— Use Archive Template —', 'king-addons'); ?></option>
                                    <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($options['main_page_id'], $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Articles Per Page', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" name="docs_per_page" value="<?php echo esc_attr($options['docs_per_page']); ?>" min="1" max="50" style="width: 80px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-layout"></span>
                    <h2><?php esc_html_e('Layout & Appearance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Category Layout', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-layout-preview">
                                <!-- Box Layout -->
                                <label class="ka-layout-option <?php echo $options['layout'] === 'box' ? 'active' : ''; ?>">
                                    <input type="radio" name="layout" value="box" <?php checked($options['layout'], 'box'); ?>>
                                    <div class="ka-layout-preview-img">
                                        <div class="ka-layout-box-preview">
                                            <div class="ka-layout-box-item">
                                                <div class="ka-layout-box-item-header">
                                                    <div class="ka-layout-box-icon"></div>
                                                    <div class="ka-layout-box-title"></div>
                                                </div>
                                                <div class="ka-layout-box-line"></div>
                                                <div class="ka-layout-box-line"></div>
                                                <div class="ka-layout-box-line"></div>
                                            </div>
                                            <div class="ka-layout-box-item">
                                                <div class="ka-layout-box-item-header">
                                                    <div class="ka-layout-box-icon"></div>
                                                    <div class="ka-layout-box-title"></div>
                                                </div>
                                                <div class="ka-layout-box-line"></div>
                                                <div class="ka-layout-box-line"></div>
                                                <div class="ka-layout-box-line"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ka-layout-preview-label"><?php esc_html_e('Box', 'king-addons'); ?></div>
                                </label>

                                <!-- Card Layout -->
                                <label class="ka-layout-option <?php echo $options['layout'] === 'card' ? 'active' : ''; ?>">
                                    <input type="radio" name="layout" value="card" <?php checked($options['layout'], 'card'); ?>>
                                    <div class="ka-layout-preview-img">
                                        <div class="ka-layout-card-preview">
                                            <div class="ka-layout-card-item">
                                                <div class="ka-layout-card-icon"></div>
                                                <div class="ka-layout-card-title"></div>
                                                <div class="ka-layout-card-count"></div>
                                            </div>
                                            <div class="ka-layout-card-item">
                                                <div class="ka-layout-card-icon"></div>
                                                <div class="ka-layout-card-title"></div>
                                                <div class="ka-layout-card-count"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ka-layout-preview-label"><?php esc_html_e('Card', 'king-addons'); ?></div>
                                </label>

                                <!-- Modern Layout -->
                                <label class="ka-layout-option <?php echo $options['layout'] === 'modern' ? 'active' : ''; ?>">
                                    <input type="radio" name="layout" value="modern" <?php checked($options['layout'], 'modern'); ?>>
                                    <div class="ka-layout-preview-img">
                                        <div class="ka-layout-modern-preview">
                                            <div class="ka-layout-modern-item">
                                                <div class="ka-layout-modern-icon"></div>
                                                <div class="ka-layout-modern-content">
                                                    <div class="ka-layout-modern-title"></div>
                                                    <div class="ka-layout-modern-desc"></div>
                                                </div>
                                                <svg class="ka-layout-modern-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                            </div>
                                            <div class="ka-layout-modern-item">
                                                <div class="ka-layout-modern-icon"></div>
                                                <div class="ka-layout-modern-content">
                                                    <div class="ka-layout-modern-title"></div>
                                                    <div class="ka-layout-modern-desc"></div>
                                                </div>
                                                <svg class="ka-layout-modern-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ka-layout-preview-label"><?php esc_html_e('Modern', 'king-addons'); ?></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Columns', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <select name="columns">
                                    <option value="2" <?php selected($options['columns'], 2); ?>>2</option>
                                    <option value="3" <?php selected($options['columns'], 3); ?>>3</option>
                                    <option value="4" <?php selected($options['columns'], 4); ?>>4</option>
                                </select>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Show Article Count', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="show_article_count" value="1" <?php checked(!empty($options['show_article_count'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Show Category Icon', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="show_category_icon" value="1" <?php checked(!empty($options['show_category_icon'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <h4 style="margin: 24px 0 16px; color: #6e6e73; font-size: 13px; text-transform: uppercase;"><?php esc_html_e('Colors', 'king-addons'); ?></h4>
                    <div class="ka-grid-3">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Primary Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="primary_color" value="<?php echo esc_attr($options['primary_color']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Icon Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="category_icon_color" value="<?php echo esc_attr($options['category_icon_color']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Link Color', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="link_color" value="<?php echo esc_attr($options['link_color']); ?>" class="ka-color-picker">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single Article Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-media-document"></span>
                    <h2><?php esc_html_e('Single Article', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Table of Contents', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="toc_enabled" value="1" <?php checked(!empty($options['toc_enabled'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Auto-generate TOC from headings', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Sticky TOC', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="toc_sticky" value="1" <?php checked(!empty($options['toc_sticky'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Keep TOC visible on scroll', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('TOC Headings', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <select name="toc_headings">
                                    <option value="h2" <?php selected($options['toc_headings'], 'h2'); ?>>H2 only</option>
                                    <option value="h2,h3" <?php selected($options['toc_headings'], 'h2,h3'); ?>>H2, H3</option>
                                    <option value="h2,h3,h4" <?php selected($options['toc_headings'], 'h2,h3,h4'); ?>>H2, H3, H4</option>
                                </select>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Sidebar', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="sidebar_enabled" value="1" <?php checked(!empty($options['sidebar_enabled'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Show category navigation sidebar', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Prev/Next Navigation', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="navigation_enabled" value="1" <?php checked(!empty($options['navigation_enabled'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Print Button', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="print_button" value="1" <?php checked(!empty($options['print_button'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Settings -->
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-search"></span>
                    <h2><?php esc_html_e('Search', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Search', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="search_enabled" value="1" <?php checked(!empty($options['search_enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show live search on docs pages', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Search Placeholder', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="search_placeholder" value="<?php echo esc_attr($options['search_placeholder']); ?>">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Minimum Characters', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="search_min_chars" value="<?php echo esc_attr($options['search_min_chars']); ?>" min="1" max="5" style="width: 80px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro: Internal Docs -->
            <div class="ka-card <?php echo !$is_premium ? 'ka-card-pro' : ''; ?>">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-lock"></span>
                    <h2><?php esc_html_e('Internal Documentation', 'king-addons'); ?> <span class="ka-pro-badge-inline">PRO</span></h2>
                </div>
                <div class="ka-card-body">
                    <?php if (!$is_premium): ?>
                    <div class="ka-pro-overlay">
                        <p><?php esc_html_e('Create private documentation visible only to specific user roles.', 'king-addons'); ?></p>
                        <a href="#" class="ka-btn ka-btn-primary"><?php esc_html_e('Upgrade to Pro', 'king-addons'); ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Internal Docs', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="internal_docs_enabled" value="1" <?php checked(!empty($options['internal_docs_enabled'])); ?> <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                <span class="ka-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Allowed Roles', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <?php foreach ($roles as $role_key => $role_name): ?>
                            <label style="display: inline-flex; align-items: center; margin-right: 16px; margin-bottom: 8px;">
                                <input type="checkbox" name="internal_docs_roles[]" value="<?php echo esc_attr($role_key); ?>" 
                                    <?php checked(in_array($role_key, $options['internal_docs_roles'] ?? [])); ?>
                                    <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                <span style="margin-left: 6px;"><?php echo esc_html($role_name); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro: Feedback -->
            <div class="ka-card <?php echo !$is_premium ? 'ka-card-pro' : ''; ?>">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-thumbs-up"></span>
                    <h2><?php esc_html_e('Article Feedback', 'king-addons'); ?> <span class="ka-pro-badge-inline">PRO</span></h2>
                </div>
                <div class="ka-card-body">
                    <?php if (!$is_premium): ?>
                    <div class="ka-pro-overlay">
                        <p><?php esc_html_e('Let readers rate your articles with helpful/not helpful feedback.', 'king-addons'); ?></p>
                        <a href="#" class="ka-btn ka-btn-primary"><?php esc_html_e('Upgrade to Pro', 'king-addons'); ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Feedback', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="feedback_enabled" value="1" <?php checked(!empty($options['feedback_enabled'])); ?> <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                <span class="ka-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Feedback Question', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="feedback_question" value="<?php echo esc_attr($options['feedback_question']); ?>" <?php echo !$is_premium ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro: Related Docs -->
            <div class="ka-card <?php echo !$is_premium ? 'ka-card-pro' : ''; ?>">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-links"></span>
                    <h2><?php esc_html_e('Related Articles', 'king-addons'); ?> <span class="ka-pro-badge-inline">PRO</span></h2>
                </div>
                <div class="ka-card-body">
                    <?php if (!$is_premium): ?>
                    <div class="ka-pro-overlay">
                        <p><?php esc_html_e('Show related articles at the end of each doc to keep readers engaged.', 'king-addons'); ?></p>
                        <a href="#" class="ka-btn ka-btn-primary"><?php esc_html_e('Upgrade to Pro', 'king-addons'); ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Show Related Articles', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="related_enabled" value="1" <?php checked(!empty($options['related_enabled'])); ?> <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                    <span class="ka-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Number of Articles', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="related_count" value="<?php echo esc_attr($options['related_count']); ?>" min="1" max="6" style="width: 80px;" <?php echo !$is_premium ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro: Analytics -->
            <div class="ka-card <?php echo !$is_premium ? 'ka-card-pro' : ''; ?>">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-chart-area"></span>
                    <h2><?php esc_html_e('Analytics & Reports', 'king-addons'); ?> <span class="ka-pro-badge-inline">PRO</span></h2>
                </div>
                <div class="ka-card-body">
                    <?php if (!$is_premium): ?>
                    <div class="ka-pro-overlay">
                        <p><?php esc_html_e('Track views, search analytics, and get email reports.', 'king-addons'); ?></p>
                        <a href="#" class="ka-btn ka-btn-primary"><?php esc_html_e('Upgrade to Pro', 'king-addons'); ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable Analytics', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="analytics_enabled" value="1" <?php checked(!empty($options['analytics_enabled'])); ?> <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Track article views and search queries', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-grid-2">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Email Reports', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="analytics_email_report" value="1" <?php checked(!empty($options['analytics_email_report'])); ?> <?php echo !$is_premium ? 'disabled' : ''; ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Weekly analytics summary', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Report Email', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="email" name="analytics_email" value="<?php echo esc_attr($options['analytics_email']); ?>" <?php echo !$is_premium ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="ka-card ka-card-footer">
                <button type="submit" class="ka-save-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Settings', 'king-addons'); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
    (function() {
        const segment = document.getElementById('ka-v3-theme-segment');
        if (!segment) {
            return;
        }

        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
        const buttons = segment.querySelectorAll('.ka-v3-segmented-btn');
        const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let mode = (segment.getAttribute('data-active') || 'dark').toString();
        let mqlHandler = null;

        function setPressedState(activeMode) {
            segment.setAttribute('data-active', activeMode);
            buttons.forEach((btn) => {
                const theme = btn.getAttribute('data-theme');
                btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
            });
        }

        function saveUISetting(key, value) {
            try {
                const body = new URLSearchParams();
                body.set('action', 'king_addons_save_dashboard_ui');
                body.set('nonce', nonce);
                body.set('key', key);
                body.set('value', value);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString(),
                    credentials: 'same-origin'
                });
            } catch (e) {}
        }

        function applyTheme(isDark) {
            document.body.classList.toggle('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(nextMode, save) {
            mode = nextMode;
            setPressedState(nextMode);

            if (mqlHandler && mql) {
                if (mql.removeEventListener) {
                    mql.removeEventListener('change', mqlHandler);
                } else if (mql.removeListener) {
                    mql.removeListener(mqlHandler);
                }
                mqlHandler = null;
            }

            if (nextMode === 'auto') {
                applyTheme(!!(mql && mql.matches));
                mqlHandler = (e) => {
                    if (mode !== 'auto') {
                        return;
                    }
                    applyTheme(!!e.matches);
                };
                if (mql) {
                    if (mql.addEventListener) {
                        mql.addEventListener('change', mqlHandler);
                    } else if (mql.addListener) {
                        mql.addListener(mqlHandler);
                    }
                }
            } else {
                applyTheme(nextMode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', nextMode);
            }
        }

        window.kaV3ToggleDark = function() {
            const isDark = document.body.classList.contains('ka-v3-dark');
            setThemeMode(isDark ? 'light' : 'dark', true);
        };

        segment.addEventListener('click', (e) => {
            const btn = e.target && e.target.closest ? e.target.closest('.ka-v3-segmented-btn') : null;
            if (!btn) {
                return;
            }
            e.preventDefault();
            const theme = (btn.getAttribute('data-theme') || 'dark').toString();
            setThemeMode(theme, true);
        });

        setThemeMode(mode, false);
    })();

    // Layout option selection
    document.querySelectorAll('.ka-layout-option input').forEach(function(input) {
        input.addEventListener('change', function() {
            document.querySelectorAll('.ka-layout-option').forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.closest('.ka-layout-option').classList.add('active');
        });
    });

    // Color picker init
    jQuery(document).ready(function($) {
        $('.ka-color-picker').wpColorPicker();
    });
    </script>
</div>
