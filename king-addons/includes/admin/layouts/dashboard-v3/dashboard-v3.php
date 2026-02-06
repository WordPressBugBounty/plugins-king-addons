<?php
/**
 * Dashboard V3 - Premium "Pro" UI with Premium style inspired design.
 *
 * Modern, premium redesign inspired by high-end product pages.
 * Features glassmorphism, bento grids, iOS-style toggles, and Titanium aesthetic.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle AJAX requests for dashboard settings
if (wp_doing_ajax()) {
    return;
}

// Load extensions list
require_once __DIR__ . '/extensions-list.php';

// Get dashboard UI settings (view mode is shared, theme is per-user)
$dashboard_settings = get_option('king_addons_dashboard_ui', [
    'show_descriptions' => true,
]);
$show_descriptions = !empty($dashboard_settings['show_descriptions']);

$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}

// Handle settings update
if (isset($_GET['settings-updated'])) {
    add_settings_error('king_addons_messages', 'king_addons_message', esc_html__('Settings Saved', 'king-addons'), 'updated');
}
settings_errors('king_addons_messages');

$options = get_option('king_addons_options', []);
$modules_map = \King_Addons\ModulesMap::getModulesMapArray();
$widgets = $modules_map['widgets'] ?? [];
$features = $modules_map['features'] ?? [];

// Hide modules hard-disabled via constants (used to QA/rollout new widgets/features).
$ka_v3_is_hard_disabled = static function (string $prefix, string $id): bool {
    $const = $prefix . strtoupper(str_replace('-', '_', $id));
    return defined($const) && constant($const) === false;
};

if (!empty($widgets)) {
    $widgets = array_filter(
        $widgets,
        static fn($widget, $widget_id) => !$ka_v3_is_hard_disabled('KING_ADDONS_WGT_', (string) $widget_id),
        ARRAY_FILTER_USE_BOTH
    );
}

if (!empty($features)) {
    $features = array_filter(
        $features,
        static fn($feature, $feature_id) => !$ka_v3_is_hard_disabled('KING_ADDONS_FEAT_', (string) $feature_id),
        ARRAY_FILTER_USE_BOTH
    );
}

// Count enabled/disabled modules
$total_modules = count($widgets);
$enabled_count = 0;
foreach ($widgets as $widget_id => $widget) {
    if (isset($options[$widget_id]) && $options[$widget_id] === 'enabled') {
        $enabled_count++;
    }
}
$disabled_count = $total_modules - $enabled_count;

// Features count (enabled by default)
$total_features = count($features);
$enabled_features = 0;
foreach ($features as $feature_id => $feature) {
    $feature_enabled = !isset($options[$feature_id]) || $options[$feature_id] === 'enabled';
    if ($feature_enabled) {
        $enabled_features++;
    }
}
$disabled_features = $total_features - $enabled_features;

// Get extensions list
$extensions = king_addons_get_extensions_list();

// Count enabled extensions (default is enabled)
$total_extensions = count($extensions);
$enabled_extensions = 0;
foreach ($extensions as $ext_id => $ext) {
    $ext_enabled = !isset($options['ext_' . $ext_id]) || $options['ext_' . $ext_id] === 'enabled';
    if ($ext_enabled) {
        $enabled_extensions++;
    }
}
$disabled_extensions = $total_extensions - $enabled_extensions;

// Group widgets by category
$categories = [
    'content' => [
        'title' => esc_html__('Content Elements', 'king-addons'),
        'icon' => 'dashicons-text-page',
        'widgets' => [],
    ],
    'media' => [
        'title' => esc_html__('Media & Gallery', 'king-addons'),
        'icon' => 'dashicons-format-gallery',
        'widgets' => [],
    ],
    'woocommerce' => [
        'title' => esc_html__('WooCommerce', 'king-addons'),
        'icon' => 'dashicons-cart',
        'widgets' => [],
    ],
    'navigation' => [
        'title' => esc_html__('Navigation & Menus', 'king-addons'),
        'icon' => 'dashicons-menu',
        'widgets' => [],
    ],
    'creative' => [
        'title' => esc_html__('Creative Effects', 'king-addons'),
        'icon' => 'dashicons-art',
        'widgets' => [],
    ],
    'forms' => [
        'title' => esc_html__('Forms & Input', 'king-addons'),
        'icon' => 'dashicons-feedback',
        'widgets' => [],
    ],
    'theme-builder' => [
        'title' => esc_html__('Theme Builder', 'king-addons'),
        'icon' => 'dashicons-admin-appearance',
        'widgets' => [],
    ],
    'other' => [
        'title' => esc_html__('Other Widgets', 'king-addons'),
        'icon' => 'dashicons-screenoptions',
        'widgets' => [],
    ],
];

// Categorize widgets strictly by explicit 'category' field
foreach ($widgets as $widget_id => $widget) {
    $category_id = isset($widget['category']) ? (string) $widget['category'] : '';
    if ($category_id === '' || !isset($categories[$category_id])) {
        $category_id = 'other';
    }
    $categories[$category_id]['widgets'][$widget_id] = $widget;
}

// Remove empty categories
$categories = array_filter($categories, function($cat) {
    return !empty($cat['widgets']);
});

$is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();

// Enqueue the CSS file
$shared_css_url = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
$shared_css_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
$shared_css_version = file_exists($shared_css_path) ? filemtime($shared_css_path) : KING_ADDONS_VERSION;

$css_url = KING_ADDONS_URL . 'includes/admin/layouts/dashboard-v3/dashboard-v3.css';
$css_path = KING_ADDONS_PATH . 'includes/admin/layouts/dashboard-v3/dashboard-v3.css';
$css_version = file_exists($css_path) ? filemtime($css_path) : KING_ADDONS_VERSION;
?>

<link rel="stylesheet" href="<?php echo esc_url($shared_css_url); ?>?v=<?php echo esc_attr($shared_css_version); ?>">
<link rel="stylesheet" href="<?php echo esc_url($css_url); ?>?v=<?php echo esc_attr($css_version); ?>">

<script>
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function applyThemeClasses(isDark) {
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
        if (document.body) {
            document.body.classList.toggle('ka-v3-dark', isDark);
        }
    }

    function ensureBodyReady(fn) {
        if (document.body) {
            fn();
            return;
        }
        document.addEventListener('DOMContentLoaded', fn, { once: true });
    }

    if (mode === 'auto') {
        const isDark = !!(mql && mql.matches);
        applyThemeClasses(isDark);
        ensureBodyReady(function() { applyThemeClasses(isDark); });
    } else {
        const isDark = mode === 'dark';
        applyThemeClasses(isDark);
        ensureBodyReady(function() { applyThemeClasses(isDark); });
    }
})();
</script>

<div class="ka-v3-wrap">
    <!-- Header -->
    <div class="ka-v3-header">
        <div>
            <h1 class="ka-v3-title"><?php esc_html_e('King Addons', 'king-addons'); ?> <em>for Elementor</em></h1>
            <p class="ka-v3-subtitle"><?php esc_html_e('The ultimate toolkit for Elementor. Build faster, design better.', 'king-addons'); ?></p>
        
    <!-- Performance Tip -->
    <div class="ka-v3-tip">
        <div class="ka-v3-tip-icon">
            <span class="dashicons dashicons-performance"></span>
        </div>
        <div class="ka-v3-tip-content">
            <strong><?php esc_html_e('Smart Loading', 'king-addons'); ?></strong>
            <span><?php esc_html_e('Assets load only when a widget, feature, or extension is used on a page. Disabling items here completely removes their backend code (PHP) from loading, making your site even faster.', 'king-addons'); ?></span>
        </div>
    </div>
        </div>
        <div class="ka-v3-header-actions">
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
            <a href="https://www.youtube.com/@kingaddons" target="_blank" class="ka-v3-btn ka-v3-btn-secondary">
                <span class="dashicons dashicons-book"></span>
                <?php esc_html_e('Guides', 'king-addons'); ?>
            </a>
            <?php if (!$is_pro): ?>
            <a href="https://kingaddons.com/pricing/?utm_source=king-addons-dashboard" target="_blank" class="ka-v3-btn ka-v3-btn-primary">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bento Grid Stats -->
    <div class="ka-v3-bento">
        <div class="ka-v3-bento-card large">
            <div class="ka-v3-bento-bg" style="background: #0071e3; width: 250px; height: 250px; top: -80px; right: -60px;"></div>
            <span class="ka-v3-bento-label"><?php esc_html_e('Widget Library', 'king-addons'); ?></span>
            <div>
                <div class="ka-v3-bento-value"><?php echo esc_html($total_modules); ?></div>
                <div class="ka-v3-bento-desc"><?php esc_html_e('Premium Widgets Available', 'king-addons'); ?></div>
            </div>
        </div>
        <div class="ka-v3-bento-card">
            <div class="ka-v3-bento-bg" style="background: #34c759; width: 150px; height: 150px; bottom: -40px; left: -40px;"></div>
            <span class="ka-v3-bento-label"><?php esc_html_e('Enabled', 'king-addons'); ?></span>
            <div>
                <div class="ka-v3-bento-value"><?php echo esc_html($enabled_count); ?></div>
                <div class="ka-v3-bento-desc"><?php esc_html_e('Widgets Enabled', 'king-addons'); ?></div>
            </div>
        </div>
        <div class="ka-v3-bento-card">
            <div class="ka-v3-bento-bg" style="background: #af52de; width: 150px; height: 150px; top: 0; right: -20px;"></div>
            <span class="ka-v3-bento-label"><?php esc_html_e('Disabled', 'king-addons'); ?></span>
            <div>
                <div class="ka-v3-bento-value"><?php echo esc_html($disabled_count); ?></div>
                <div class="ka-v3-bento-desc"><?php esc_html_e('Widgets Disabled', 'king-addons'); ?></div>
            </div>
        </div>
        <?php
        // Trinity Backup Promo Card - check if plugin is installed/active
        $trinity_plugin_slug = 'trinity-backup/trinity-backup.php';
        $trinity_installed = file_exists(WP_PLUGIN_DIR . '/' . $trinity_plugin_slug);
        $trinity_active = is_plugin_active($trinity_plugin_slug);
        

        // TEMPORARILY DISABLED
        $trinity_PROMO = false;

        // Only show promo if plugin is not active
        if (!$trinity_active && $trinity_PROMO):
        ?>
        <!-- Trinity Backup Promo Card -->
        <div class="ka-v3-bento-card ka-v3-promo-card" id="ka-trinity-promo">
            <img src="https://ps.w.org/trinity-backup/assets/icon-256x256.png" alt="Trinity Backup" class="ka-v3-promo-icon">
            <div class="ka-v3-promo-text">
                <span class="ka-v3-promo-label"><?php esc_html_e('From the Developer', 'king-addons'); ?></span>
                <span class="ka-v3-promo-title">Trinity Backup</span>
                <span class="ka-v3-promo-desc"><?php esc_html_e('Simple & reliable backups', 'king-addons'); ?></span>
            </div>
            <?php if ($trinity_installed): ?>
                <button type="button" class="ka-v3-promo-btn" id="ka-trinity-activate" data-action="activate">
                    <span class="ka-v3-promo-btn-text"><?php esc_html_e('Activate', 'king-addons'); ?></span>
                    <span class="ka-v3-promo-btn-loading"></span>
                </button>
            <?php else: ?>
                <button type="button" class="ka-v3-promo-btn" id="ka-trinity-install" data-action="install">
                    <span class="ka-v3-promo-btn-text"><?php esc_html_e('Install Free', 'king-addons'); ?></span>
                    <span class="ka-v3-promo-btn-loading"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // Trinity Backup Slim Banner - check if plugin is installed/active
    $trinity_plugin_slug_slim = 'trinity-backup/trinity-backup.php';
    $trinity_installed_slim = file_exists(WP_PLUGIN_DIR . '/' . $trinity_plugin_slug_slim);
    $trinity_active_slim = is_plugin_active($trinity_plugin_slug_slim);

    $trinity_active_slim_ENABLED = false;

    // Only show banner if plugin is not active
    if (!$trinity_active_slim && $trinity_active_slim_ENABLED):
    ?>
    <!-- Trinity Backup Slim Banner -->
    <div class="ka-v3-trinity-slim" id="ka-trinity-slim">
        <div class="ka-v3-trinity-slim-left">
            <img src="https://ps.w.org/trinity-backup/assets/icon-256x256.png" alt="Trinity Backup" class="ka-v3-trinity-slim-icon">
            <div class="ka-v3-trinity-slim-text">
                <span class="ka-v3-trinity-slim-title">Trinity Backup</span>
                <span class="ka-v3-trinity-slim-desc"><?php esc_html_e('Free one-click backups for WordPress. From the King Addons developer.', 'king-addons'); ?></span>
            </div>
        </div>
        <?php if ($trinity_installed_slim): ?>
            <button type="button" class="ka-v3-trinity-slim-btn" id="ka-trinity-slim-activate" data-action="activate">
                <span class="ka-v3-trinity-slim-btn-text"><?php esc_html_e('Activate', 'king-addons'); ?></span>
                <span class="ka-v3-trinity-slim-btn-loading"></span>
                <svg class="ka-v3-trinity-slim-btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        <?php else: ?>
            <button type="button" class="ka-v3-trinity-slim-btn" id="ka-trinity-slim-install" data-action="install">
                <span class="ka-v3-trinity-slim-btn-text"><?php esc_html_e('Install Free', 'king-addons'); ?></span>
                <span class="ka-v3-trinity-slim-btn-loading"></span>
                <svg class="ka-v3-trinity-slim-btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="ka-v3-nav">
        <button type="button" class="ka-v3-nav-item active" data-tab="widgets">
            <?php esc_html_e('Widgets', 'king-addons'); ?>
            <span class="ka-v3-nav-badge"><?php echo esc_html($total_modules); ?></span>
        </button>
        <button type="button" class="ka-v3-nav-item" data-tab="features">
            <?php esc_html_e('Features', 'king-addons'); ?>
            <span class="ka-v3-nav-badge"><?php echo esc_html($total_features); ?></span>
        </button>
        <button type="button" class="ka-v3-nav-item" data-tab="extensions">
            <?php esc_html_e('Extensions', 'king-addons'); ?>
            <span class="ka-v3-nav-badge"><?php echo esc_html($total_extensions); ?></span>
        </button>
    </div>

    <!-- Single Form for all tabs -->
    <form action="options.php" method="post" id="ka-v3-dashboard-form">
        <?php settings_fields('king_addons'); ?>

        <!-- Widgets Tab Content -->
        <div class="ka-v3-tab-content active" id="ka-v3-tab-widgets">
            
            <!-- Quick Actions -->
            <div class="ka-v3-quick-actions">
                <?php if (defined('KING_ADDONS_EXT_HEADER_FOOTER_BUILDER') && KING_ADDONS_EXT_HEADER_FOOTER_BUILDER): ?>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=king-addons-el-hf')); ?>" class="ka-v3-btn ka-v3-btn-secondary">
                    <span class="dashicons dashicons-welcome-widgets-menus"></span>
                    <?php esc_html_e('Header & Footer', 'king-addons'); ?>
                </a>
                <?php endif; ?>
                <?php if (defined('KING_ADDONS_EXT_POPUP_BUILDER') && KING_ADDONS_EXT_POPUP_BUILDER): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-popup-builder')); ?>" class="ka-v3-btn ka-v3-btn-secondary">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e('Popup Builder', 'king-addons'); ?>
                </a>
                <?php endif; ?>
                <?php if (defined('KING_ADDONS_EXT_TEMPLATES_CATALOG') && KING_ADDONS_EXT_TEMPLATES_CATALOG): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=king-addons-templates')); ?>" class="ka-v3-btn ka-v3-btn-secondary">
                    <span class="dashicons dashicons-layout"></span>
                    <?php esc_html_e('Templates', 'king-addons'); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Controls Bar -->
            <div class="ka-v3-controls">
                <div class="ka-v3-search">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="ka-v3-search" placeholder="<?php esc_attr_e('Search widgets...', 'king-addons'); ?>">
                </div>
                <div class="ka-v3-filters">
                    <button type="button" class="ka-v3-filter-btn active" data-filter="all"><?php esc_html_e('All', 'king-addons'); ?></button>
                    <button type="button" class="ka-v3-filter-btn" data-filter="enabled"><?php esc_html_e('Enabled', 'king-addons'); ?></button>
                    <button type="button" class="ka-v3-filter-btn" data-filter="disabled"><?php esc_html_e('Disabled', 'king-addons'); ?></button>
                </div>
                <div class="ka-v3-collapse-all">
                    <button type="button" class="ka-v3-filter-btn" id="ka-v3-collapse-toggle" data-state="expanded">
                        <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                        <span class="ka-v3-collapse-text"><?php esc_html_e('Collapse categories', 'king-addons'); ?></span>
                    </button>
                </div>
            </div>

            <!-- Module Categories -->
            <?php foreach ($categories as $cat_id => $category): 
                $category_enabled_count = 0;
                foreach ($category['widgets'] as $widget_id => $widget) {
                    if (isset($options[$widget_id]) && $options[$widget_id] === 'enabled') {
                        $category_enabled_count++;
                    }
                }
                $category_total = count($category['widgets']);
                $category_all_enabled = $category_enabled_count === $category_total;
            ?>
            <div class="ka-v3-category" data-category="<?php echo esc_attr($cat_id); ?>">
                <div class="ka-v3-category-header">
                    <div class="ka-v3-category-title">
                        <span class="dashicons <?php echo esc_attr($category['icon']); ?>"></span>
                        <h3><?php echo esc_html($category['title']); ?></h3>
                        <span class="ka-v3-category-count"><?php echo esc_html($category_total); ?></span>
                    </div>
                    <div class="ka-v3-category-toggle">
                        <button type="button" class="ka-v3-category-collapse" aria-expanded="true" aria-label="<?php echo esc_attr(esc_html__('Collapse category', 'king-addons')); ?>">
                            <span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                        </button>
                        <label class="ka-v3-toggle">
                            <input type="checkbox" class="ka-v3-category-toggle-input" data-category="<?php echo esc_attr($cat_id); ?>" <?php checked($category_all_enabled); ?>>
                            <span class="ka-v3-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="ka-v3-category-body">
                <div class="ka-v3-grid">
                    <?php foreach ($category['widgets'] as $widget_id => $widget): 
                        $is_enabled = isset($options[$widget_id]) && $options[$widget_id] === 'enabled';
                    ?>
                    <div class="ka-v3-card <?php echo $is_enabled ? '' : 'disabled'; ?>" data-widget="<?php echo esc_attr($widget_id); ?>" data-enabled="<?php echo $is_enabled ? '1' : '0'; ?>">
                        <div class="ka-v3-card-header">
                            <div class="ka-v3-card-icon">
                                <?php 
                                $icon_url = KING_ADDONS_URL . 'includes/admin/img/' . $widget_id . '.svg';
                                $default_icon = KING_ADDONS_URL . 'includes/admin/img/default-widget.svg';
                                ?>
                                <img src="<?php echo esc_url($icon_url); ?>?v=<?php echo esc_attr(KING_ADDONS_VERSION); ?>" 
                                     alt="<?php echo esc_attr($widget['title']); ?>" 
                                     onerror="this.onerror=null; this.src='<?php echo esc_url($default_icon); ?>';">
                            </div>
                            <label class="ka-v3-toggle">
                                <input type="hidden" name="king_addons_options[<?php echo esc_attr($widget_id); ?>]" value="disabled">
                                <input type="checkbox" name="king_addons_options[<?php echo esc_attr($widget_id); ?>]" value="enabled" <?php checked($is_enabled); ?>>
                                <span class="ka-v3-toggle-slider"></span>
                            </label>
                        </div>
                        <h4 class="ka-v3-card-title"><?php echo esc_html($widget['title']); ?></h4>
                        <?php if (!empty($widget['description'])): ?>
                        <p class="ka-v3-card-desc"><?php echo esc_html($widget['description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($widget['demo-link'])): ?>
                        <div class="ka-v3-card-footer">
                            <a href="<?php echo esc_url($widget['demo-link']); ?>?utm_source=ka-dashboard-v3" target="_blank" class="ka-v3-card-link">
                                <?php esc_html_e('View Demo', 'king-addons'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Features Tab Content -->
        <div class="ka-v3-tab-content" id="ka-v3-tab-features">
            <div class="ka-v3-grid">
                <?php foreach ($features as $feature_id => $feature):
                    $feature_enabled = !isset($options[$feature_id]) || $options[$feature_id] === 'enabled';
                ?>
                <div class="ka-v3-card <?php echo $feature_enabled ? '' : 'disabled'; ?>" data-feature="<?php echo esc_attr($feature_id); ?>" data-enabled="<?php echo $feature_enabled ? '1' : '0'; ?>">
                    <div class="ka-v3-card-header">
                        <div class="ka-v3-card-icon">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </div>
                        <label class="ka-v3-toggle">
                            <input type="hidden" name="king_addons_options[<?php echo esc_attr($feature_id); ?>]" value="disabled">
                            <input type="checkbox" name="king_addons_options[<?php echo esc_attr($feature_id); ?>]" value="enabled" <?php checked($feature_enabled); ?>>
                            <span class="ka-v3-toggle-slider"></span>
                        </label>
                    </div>
                    <h4 class="ka-v3-card-title"><?php echo esc_html($feature['title'] ?? $feature_id); ?></h4>
                    <?php if (!empty($feature['description'])): ?>
                    <p class="ka-v3-card-desc"><?php echo esc_html($feature['description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($feature['demo-link'])): ?>
                    <div class="ka-v3-card-footer">
                        <a href="<?php echo esc_url($feature['demo-link']); ?>" target="_blank" class="ka-v3-card-link">
                            <?php esc_html_e('Learn More', 'king-addons'); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Extensions Tab Content -->
        <div class="ka-v3-tab-content" id="ka-v3-tab-extensions">
            <div class="ka-v3-grid">
                <?php foreach ($extensions as $ext_id => $ext): 
                    $ext_available = true;
                    $ext_requirement_msg = '';
                    $ext_requirement_url = '';

                    if ($ext_id === 'woo-builder' && (!class_exists('WooCommerce') || !function_exists('WC'))) {
                        $ext_available = false;
                        $ext_requirement_msg = $ext['requires']['woocommerce']['message'] ?? esc_html__('WooCommerce is required.', 'king-addons');
                        $ext_requirement_url = $ext['requires']['woocommerce']['install_url'] ?? admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
                    }

                    $ext_enabled = $ext_available && (!isset($options['ext_' . $ext_id]) || $options['ext_' . $ext_id] === 'enabled');
                ?>
                <div class="ka-v3-card <?php echo $ext_enabled ? '' : 'disabled'; ?> <?php echo $ext_available ? '' : 'ka-v3-card-unavailable'; ?>" data-ext="<?php echo esc_attr($ext_id); ?>" data-enabled="<?php echo $ext_enabled ? '1' : '0'; ?>" data-available="<?php echo $ext_available ? '1' : '0'; ?>">
                    <div class="ka-v3-card-header">
                        <div class="ka-v3-card-icon">
                            <span class="dashicons <?php echo esc_attr($ext['icon']); ?>"></span>
                        </div>
                        <label class="ka-v3-toggle">
                            <input type="hidden" name="king_addons_options[ext_<?php echo esc_attr($ext_id); ?>]" value="disabled">
                            <input type="checkbox" name="king_addons_options[ext_<?php echo esc_attr($ext_id); ?>]" value="enabled" <?php checked($ext_enabled); ?> <?php disabled(!$ext_available); ?>>
                            <span class="ka-v3-toggle-slider"></span>
                        </label>
                    </div>
                    <h4 class="ka-v3-card-title"><?php echo esc_html($ext['title']); ?></h4>
                    <p class="ka-v3-card-desc"><?php echo esc_html($ext['description']); ?></p>
                    <?php if (!$ext_available && $ext_requirement_msg !== ''): ?>
                        <p class="ka-v3-card-requirement"><?php echo esc_html($ext_requirement_msg); ?></p>
                    <?php endif; ?>
                    <div class="ka-v3-card-footer">
                        <?php if ($ext_available): ?>
                            <a href="<?php echo esc_url($ext['link']); ?>" class="ka-v3-card-link <?php echo !$ext_enabled ? 'ka-v3-link-disabled' : ''; ?>">
                                <?php esc_html_e('Open Panel', 'king-addons'); ?>
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($ext_requirement_url); ?>" class="ka-v3-card-link">
                                <?php esc_html_e('Install / Activate WooCommerce', 'king-addons'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
        // Large Trinity Backup Banner
        $trinity_plugin_slug_banner = 'trinity-backup/trinity-backup.php';
        $trinity_installed_banner = file_exists(WP_PLUGIN_DIR . '/' . $trinity_plugin_slug_banner);
        $trinity_active_banner = is_plugin_active($trinity_plugin_slug_banner);
        
        // TEMPORARILY DISABLED
        $trinity_PROMO_BANNER = false;

        // Only show banner if plugin is not active
        if (!$trinity_active_banner && $trinity_PROMO_BANNER):
        ?>
        <!-- Trinity Backup Large Banner -->
        <div class="ka-v3-trinity-banner" id="ka-trinity-banner">
            <div class="ka-v3-trinity-banner-content">
                <div class="ka-v3-trinity-banner-left">
                    <img src="https://ps.w.org/trinity-backup/assets/icon-256x256.png" alt="Trinity Backup" class="ka-v3-trinity-banner-icon">
                    <div class="ka-v3-trinity-banner-text">
                        <span class="ka-v3-trinity-banner-label"><?php esc_html_e('From King Addons Developer', 'king-addons'); ?></span>
                        <h3 class="ka-v3-trinity-banner-title">Trinity Backup</h3>
                        <p class="ka-v3-trinity-banner-desc"><?php esc_html_e('The simplest way to backup your WordPress site. One-click backups, easy restore, and rock-solid reliability. 100% Free.', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-v3-trinity-banner-right">
                    <div class="ka-v3-trinity-banner-features">
                        <div class="ka-v3-trinity-banner-feature">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('One-Click Backups', 'king-addons'); ?>
                        </div>
                        <div class="ka-v3-trinity-banner-feature">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Easy Restore', 'king-addons'); ?>
                        </div>
                        <div class="ka-v3-trinity-banner-feature">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('100% Free Forever', 'king-addons'); ?>
                        </div>
                    </div>
                    <?php if ($trinity_installed_banner): ?>
                        <button type="button" class="ka-v3-trinity-banner-btn" id="ka-trinity-banner-activate" data-action="activate">
                            <span class="ka-v3-trinity-banner-btn-text"><?php esc_html_e('Activate Plugin', 'king-addons'); ?></span>
                            <span class="ka-v3-trinity-banner-btn-loading"></span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="ka-v3-trinity-banner-btn" id="ka-trinity-banner-install" data-action="install">
                            <span class="ka-v3-trinity-banner-btn-text"><?php esc_html_e('Install Free Plugin', 'king-addons'); ?></span>
                            <span class="ka-v3-trinity-banner-btn-loading"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="ka-v3-trinity-banner-close" id="ka-trinity-banner-close" title="<?php esc_attr_e('Dismiss', 'king-addons'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Sticky Footer -->
        <div class="ka-v3-footer">
            <button type="button" class="ka-v3-btn ka-v3-btn-secondary" id="ka-v3-enable-all"><?php esc_html_e('Enable All', 'king-addons'); ?></button>
            <button type="button" class="ka-v3-btn ka-v3-btn-secondary" id="ka-v3-disable-all"><?php esc_html_e('Disable All', 'king-addons'); ?></button>
            <button type="submit" class="ka-v3-btn ka-v3-btn-primary"><?php esc_html_e('Save Changes', 'king-addons'); ?></button>
        </div>
    </form>
</div>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {
        let currentFilter = 'all';
        let searchTokens = [];
        let currentTab = 'widgets';

        // Bento stats data per tab (total / enabled / disabled)
        const kaBentoStats = {
            widgets: [
                {
                    label: '<?php echo esc_js(esc_html__('Widget Library', 'king-addons')); ?>',
                    value: <?php echo (int) $total_modules; ?>,
                    desc: '<?php echo esc_js(esc_html__('Premium Widgets Available', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Enabled', 'king-addons')); ?>',
                    value: <?php echo (int) $enabled_count; ?>,
                    desc: '<?php echo esc_js(esc_html__('Widgets Enabled', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Disabled', 'king-addons')); ?>',
                    value: <?php echo (int) $disabled_count; ?>,
                    desc: '<?php echo esc_js(esc_html__('Widgets Disabled', 'king-addons')); ?>'
                }
            ],
            features: [
                {
                    label: '<?php echo esc_js(esc_html__('Features', 'king-addons')); ?>',
                    value: <?php echo (int) $total_features; ?>,
                    desc: '<?php echo esc_js(esc_html__('Total Features', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Enabled', 'king-addons')); ?>',
                    value: <?php echo (int) $enabled_features; ?>,
                    desc: '<?php echo esc_js(esc_html__('Enabled', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Disabled', 'king-addons')); ?>',
                    value: <?php echo (int) $disabled_features; ?>,
                    desc: '<?php echo esc_js(esc_html__('Disabled', 'king-addons')); ?>'
                }
            ],
            extensions: [
                {
                    label: '<?php echo esc_js(esc_html__('Extensions', 'king-addons')); ?>',
                    value: <?php echo (int) $total_extensions; ?>,
                    desc: '<?php echo esc_js(esc_html__('Total Extensions', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Enabled', 'king-addons')); ?>',
                    value: <?php echo (int) $enabled_extensions; ?>,
                    desc: '<?php echo esc_js(esc_html__('Enabled', 'king-addons')); ?>'
                },
                {
                    label: '<?php echo esc_js(esc_html__('Disabled', 'king-addons')); ?>',
                    value: <?php echo (int) $disabled_extensions; ?>,
                    desc: '<?php echo esc_js(esc_html__('Disabled', 'king-addons')); ?>'
                }
            ]
        };

        function easeInOutQuad(t) {
            return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
        }

        function animateTextSwap($el, nextText) {
            const currentText = $el.text();
            if (currentText === nextText) {
                return;
            }

            const el = $el.get(0);
            if (!el || !el.animate) {
                $el.text(nextText);
                return;
            }

            const out = el.animate(
                [
                    { opacity: 1, transform: 'translateY(0px)' },
                    { opacity: 0, transform: 'translateY(-4px)' }
                ],
                { duration: 140, easing: 'ease-out', fill: 'forwards' }
            );

            out.onfinish = function() {
                $el.text(nextText);
                el.animate(
                    [
                        { opacity: 0, transform: 'translateY(4px)' },
                        { opacity: 1, transform: 'translateY(0px)' }
                    ],
                    { duration: 180, easing: 'ease-out', fill: 'both' }
                );
            };
        }

        function updateBento(tabKey) {
            const data = kaBentoStats[tabKey];
            if (!data) {
                return;
            }

            const $cards = $('.ka-v3-bento .ka-v3-bento-card');
            if ($cards.length < 3) {
                return;
            }

            $cards.each(function(index) {
                const cardData = data[index];
                if (!cardData) {
                    return;
                }

                const $card = $(this);
                const $label = $card.find('.ka-v3-bento-label').first();
                const $value = $card.find('.ka-v3-bento-value').first();
                const $desc = $card.find('.ka-v3-bento-desc').first();

                animateTextSwap($label, cardData.label);
                animateTextSwap($value, String(cardData.value));
                animateTextSwap($desc, cardData.desc);
            });
        }

        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';

        // Save UI setting via AJAX
        function saveUISetting(key, value) {
            $.post(ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: nonce,
                key: key,
                value: value
            });
        }

        // Theme segmented control
        const $themeSegment = $('#ka-v3-theme-segment');
        const $themeSegmentButtons = $themeSegment.find('.ka-v3-segmented-btn');

        const themeMql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        let themeMode = ($themeSegment.attr('data-active') || 'dark').toString();
        let themeMqlHandler = null;

        function updateSegment(mode) {
            $themeSegment.attr('data-active', mode);
            $themeSegmentButtons.each(function() {
                const theme = $(this).data('theme');
                $(this).attr('aria-pressed', theme === mode ? 'true' : 'false');
            });
        }

        function applyThemeClass(isDark) {
            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(mode, save) {
            themeMode = mode;
            updateSegment(mode);

            if (themeMqlHandler && themeMql) {
                if (themeMql.removeEventListener) {
                    themeMql.removeEventListener('change', themeMqlHandler);
                } else if (themeMql.removeListener) {
                    themeMql.removeListener(themeMqlHandler);
                }
                themeMqlHandler = null;
            }

            if (mode === 'auto') {
                applyThemeClass(!!(themeMql && themeMql.matches));
                themeMqlHandler = function(e) {
                    if (themeMode !== 'auto') {
                        return;
                    }
                    applyThemeClass(!!e.matches);
                };
                if (themeMql) {
                    if (themeMql.addEventListener) {
                        themeMql.addEventListener('change', themeMqlHandler);
                    } else if (themeMql.addListener) {
                        themeMql.addListener(themeMqlHandler);
                    }
                }
            } else {
                applyThemeClass(mode === 'dark');
            }

            if (save) {
                saveUISetting('theme_mode', mode);
            }
        }

        $themeSegment.on('click', '.ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            const mode = ($(this).data('theme') || 'dark').toString();
            setThemeMode(mode, true);
        });

        // Ensure mode applies for Auto (listener + system state)
        setThemeMode(themeMode, false);

        // Tab Navigation
        $('.ka-v3-nav-item').on('click', function() {
            const tab = $(this).data('tab');
            currentTab = tab;
            
            $('.ka-v3-nav-item').removeClass('active');
            $(this).addClass('active');
            
            $('.ka-v3-tab-content').removeClass('active');
            $('#ka-v3-tab-' + tab).addClass('active');

            // Update top bento stats for the active tab
            updateBento(tab);

            // Scroll dashboard to the top on tab switch
            const $wrap = $('.ka-v3-wrap');
            const targetTop = $wrap.length ? Math.max(0, Math.round($wrap.offset().top) - 20) : 0;
            try {
                window.scrollTo({ top: targetTop, behavior: 'smooth' });
            } catch (e) {
                window.scrollTo(0, targetTop);
            }
        });

        // Initialize bento for the default tab
        updateBento(currentTab);

        function normalizeText(text) {
            return (text || '').toString().toLowerCase();
        }

        function getQueryTokens(query) {
            return normalizeText(query).trim().split(/\s+/).filter(Boolean);
        }

        function matchesAllTokens(haystack, tokens) {
            if (!tokens || tokens.length === 0) {
                return true;
            }
            return tokens.every(function(token) {
                return haystack.indexOf(token) !== -1;
            });
        }

        // Apply filters (search + filter)
        function applyFilters() {
            const $cards = $('#ka-v3-tab-widgets .ka-v3-card');
            
            $cards.each(function() {
                const $card = $(this);
                const title = normalizeText($card.find('.ka-v3-card-title').text());
                const widgetId = normalizeText(($card.data('widget') || '').toString());
                const description = normalizeText($card.find('.ka-v3-card-desc').text());
                const $category = $card.closest('.ka-v3-category');
                const categoryId = normalizeText(($category.data('category') || '').toString());
                const categoryTitle = normalizeText($category.find('.ka-v3-category-title h3').first().text());
                const haystack = (title + ' ' + widgetId + ' ' + description + ' ' + categoryId + ' ' + categoryTitle).trim();
                const isEnabled = $card.data('enabled') == 1;

                let matchesSearch = matchesAllTokens(haystack, searchTokens);
                let matchesFilter = currentFilter === 'all' || 
                    (currentFilter === 'enabled' && isEnabled) || 
                    (currentFilter === 'disabled' && !isEnabled);

                if (matchesSearch && matchesFilter) {
                    $card.removeClass('ka-v3-hidden');
                } else {
                    $card.addClass('ka-v3-hidden');
                }
            });

            // Hide empty categories
            $('.ka-v3-category').each(function() {
                const $category = $(this);
                const hasVisible = $category.find('.ka-v3-card:not(.ka-v3-hidden)').length > 0;
                $category.toggleClass('ka-v3-hidden', !hasVisible);
            });
        }

        // Search
        $('#ka-v3-search').on('input', function() {
            searchTokens = getQueryTokens($(this).val());
            applyFilters();
        });

        // Filter buttons (widgets tab)
        $('.ka-v3-filters .ka-v3-filter-btn').on('click', function() {
            $('.ka-v3-filters .ka-v3-filter-btn').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('filter');
            applyFilters();
        });

        // Collapse / Expand categories
        const $collapseAllBtn = $('#ka-v3-collapse-toggle');

        function setCategoryCollapsed($category, collapsed) {
            $category.toggleClass('ka-v3-collapsed', !!collapsed);
            $category.find('.ka-v3-category-collapse').attr('aria-expanded', collapsed ? 'false' : 'true');
        }

        function updateCollapseAllButton() {
            const $cats = $('#ka-v3-tab-widgets .ka-v3-category');
            if (!$cats.length || !$collapseAllBtn.length) {
                return;
            }

            const allCollapsed = $cats.filter(':not(.ka-v3-collapsed)').length === 0;
            $collapseAllBtn.attr('data-state', allCollapsed ? 'collapsed' : 'expanded');
            const nextText = allCollapsed ? '<?php echo esc_js(esc_html__('Expand categories', 'king-addons')); ?>' : '<?php echo esc_js(esc_html__('Collapse categories', 'king-addons')); ?>';
            $collapseAllBtn.find('.ka-v3-collapse-text').text(nextText);
        }

        $(document).on('click', '.ka-v3-category-collapse', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const $category = $(this).closest('.ka-v3-category');
            const isCollapsed = $category.hasClass('ka-v3-collapsed');
            setCategoryCollapsed($category, !isCollapsed);
            updateCollapseAllButton();
        });

        $collapseAllBtn.on('click', function(e) {
            e.preventDefault();

            const $cats = $('#ka-v3-tab-widgets .ka-v3-category');
            if (!$cats.length) {
                return;
            }

            const anyExpanded = $cats.filter(':not(.ka-v3-collapsed)').length > 0;
            $cats.each(function() {
                setCategoryCollapsed($(this), anyExpanded);
            });
            updateCollapseAllButton();
        });

        // Initialize collapse button state
        updateCollapseAllButton();

        // Center footer relative to dashboard container (WP admin menu offsets content)
        function updateFooterCenter() {
            const $footer = $('.ka-v3-footer');
            const $wrap = $('.ka-v3-wrap');
            if (!$footer.length || !$wrap.length) {
                return;
            }
            const wrapRect = $wrap.get(0).getBoundingClientRect();
            const centerX = Math.round(wrapRect.left + (wrapRect.width / 2));
            $footer.get(0).style.left = centerX + 'px';
            $footer.get(0).style.transform = 'translateX(-50%)';
        }

        updateFooterCenter();
        $(window).on('resize', function() {
            updateFooterCenter();
        });

        // Category toggle
        $('.ka-v3-category-toggle-input').on('change', function() {
            const isEnabled = $(this).is(':checked');
            const $category = $(this).closest('.ka-v3-category');
            
            $category.find('.ka-v3-card').each(function() {
                const $card = $(this);
                const $checkbox = $card.find('input[type="checkbox"]');
                
                $checkbox.prop('checked', isEnabled);
                
                if (isEnabled) {
                    $card.removeClass('disabled').data('enabled', 1);
                } else {
                    $card.addClass('disabled').data('enabled', 0);
                }
            });
        });

        // Update category toggle state when individual widgets change
        $(document).on('change', '.ka-v3-card input[type="checkbox"]', function() {
            const $card = $(this).closest('.ka-v3-card');
            const $category = $card.closest('.ka-v3-category');
            
            if ($(this).is(':checked')) {
                $card.removeClass('disabled').data('enabled', 1);
            } else {
                $card.addClass('disabled').data('enabled', 0);
            }

            if ($category.length) {
                const total = $category.find('.ka-v3-card').length;
                const enabled = $category.find('.ka-v3-card input[type="checkbox"]:checked').length;
                $category.find('.ka-v3-category-toggle-input').prop('checked', enabled === total);
            }
        });

        // Enable/Disable All
        $('#ka-v3-enable-all').on('click', function() {
            const $activeTab = $('.ka-v3-tab-content.active');
            $activeTab.find('input[type="checkbox"]').prop('checked', true);
            $activeTab.find('.ka-v3-card').removeClass('disabled').data('enabled', 1);
            $activeTab.find('.ka-v3-category-toggle-input').prop('checked', true);
        });

        $('#ka-v3-disable-all').on('click', function() {
            const $activeTab = $('.ka-v3-tab-content.active');
            $activeTab.find('input[type="checkbox"]').prop('checked', false);
            $activeTab.find('.ka-v3-card').addClass('disabled').data('enabled', 0);
            $activeTab.find('.ka-v3-category-toggle-input').prop('checked', false);
        });

        // Trinity Backup Install/Activate (Small Promo Card)
        $('#ka-trinity-install, #ka-trinity-activate').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            
            if ($btn.hasClass('loading') || $btn.hasClass('success')) {
                return;
            }
            
            $btn.addClass('loading');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_addons_install_trinity_backup',
                    nonce: '<?php echo esc_js(wp_create_nonce('king_addons_install_plugin')); ?>',
                    plugin_action: action
                },
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        $btn.addClass('success');
                        $btn.find('.ka-v3-promo-btn-text').text('<?php echo esc_js(__('Installed!', 'king-addons')); ?>');
                        // Redirect to Trinity Backup page after 1 second
                        setTimeout(function() {
                            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=trinity-backup')); ?>';
                        }, 1000);
                    } else {
                        $btn.addClass('error');
                        $btn.find('.ka-v3-promo-btn-text').text(response.data || '<?php echo esc_js(__('Error', 'king-addons')); ?>');
                        setTimeout(function() {
                            $btn.removeClass('error');
                            $btn.find('.ka-v3-promo-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate', 'king-addons')); ?>');
                        }, 3000);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').addClass('error');
                    $btn.find('.ka-v3-promo-btn-text').text('<?php echo esc_js(__('Error', 'king-addons')); ?>');
                    setTimeout(function() {
                        $btn.removeClass('error');
                        $btn.find('.ka-v3-promo-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate', 'king-addons')); ?>');
                    }, 3000);
                }
            });
        });

        // Trinity Backup Large Banner Install/Activate
        $('#ka-trinity-banner-install, #ka-trinity-banner-activate').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            
            if ($btn.hasClass('loading') || $btn.hasClass('success')) {
                return;
            }
            
            $btn.addClass('loading');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_addons_install_trinity_backup',
                    nonce: '<?php echo esc_js(wp_create_nonce('king_addons_install_plugin')); ?>',
                    plugin_action: action
                },
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        $btn.addClass('success');
                        $btn.find('.ka-v3-trinity-banner-btn-text').text('<?php echo esc_js(__('Installed!', 'king-addons')); ?>');
                        // Redirect to Trinity Backup page after 1 second
                        setTimeout(function() {
                            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=trinity-backup')); ?>';
                        }, 1000);
                    } else {
                        $btn.addClass('error');
                        $btn.find('.ka-v3-trinity-banner-btn-text').text(response.data || '<?php echo esc_js(__('Error', 'king-addons')); ?>');
                        setTimeout(function() {
                            $btn.removeClass('error');
                            $btn.find('.ka-v3-trinity-banner-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free Plugin', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate Plugin', 'king-addons')); ?>');
                        }, 3000);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').addClass('error');
                    $btn.find('.ka-v3-trinity-banner-btn-text').text('<?php echo esc_js(__('Error', 'king-addons')); ?>');
                    setTimeout(function() {
                        $btn.removeClass('error');
                        $btn.find('.ka-v3-trinity-banner-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free Plugin', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate Plugin', 'king-addons')); ?>');
                    }, 3000);
                }
            });
        });

        // Trinity Backup Banner Close Button
        $('#ka-trinity-banner-close').on('click', function(e) {
            e.preventDefault();
            $('#ka-trinity-banner').fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Trinity Backup Slim Banner Install/Activate
        // Prevent closing tab during installation
        function preventTabClose(e) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
        
        $('#ka-trinity-slim-install, #ka-trinity-slim-activate').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const action = $btn.data('action');
            
            if ($btn.hasClass('loading') || $btn.hasClass('success')) {
                return;
            }
            
            $btn.addClass('loading');
            
            // Add beforeunload listener to prevent closing
            window.addEventListener('beforeunload', preventTabClose);
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'king_addons_install_trinity_backup',
                    nonce: '<?php echo esc_js(wp_create_nonce('king_addons_install_plugin')); ?>',
                    plugin_action: action
                },
                success: function(response) {
                    // Remove beforeunload listener
                    window.removeEventListener('beforeunload', preventTabClose);
                    
                    $btn.removeClass('loading');
                    if (response.success) {
                        $btn.addClass('success');
                        var successText = action === 'install' ? '<?php echo esc_js(__('Installed!', 'king-addons')); ?>' : '<?php echo esc_js(__('Activated!', 'king-addons')); ?>';
                        $btn.find('.ka-v3-trinity-slim-btn-text').text(successText);
                        $btn.find('.ka-v3-trinity-slim-btn-arrow').hide();
                        // Redirect to Trinity Backup page after 1 second
                        setTimeout(function() {
                            window.location.href = '<?php echo esc_url(admin_url('admin.php?page=trinity-backup')); ?>';
                        }, 1000);
                    } else {
                        $btn.addClass('error');
                        $btn.find('.ka-v3-trinity-slim-btn-text').text(response.data || '<?php echo esc_js(__('Error', 'king-addons')); ?>');
                        setTimeout(function() {
                            $btn.removeClass('error');
                            $btn.find('.ka-v3-trinity-slim-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate', 'king-addons')); ?>');
                        }, 3000);
                    }
                },
                error: function() {
                    // Remove beforeunload listener
                    window.removeEventListener('beforeunload', preventTabClose);
                    
                    $btn.removeClass('loading').addClass('error');
                    $btn.find('.ka-v3-trinity-slim-btn-text').text('<?php echo esc_js(__('Error', 'king-addons')); ?>');
                    setTimeout(function() {
                        $btn.removeClass('error');
                        $btn.find('.ka-v3-trinity-slim-btn-text').text(action === 'install' ? '<?php echo esc_js(__('Install Free', 'king-addons')); ?>' : '<?php echo esc_js(__('Activate', 'king-addons')); ?>');
                    }, 3000);
                }
            });
        });
    });
})(jQuery);
</script>
