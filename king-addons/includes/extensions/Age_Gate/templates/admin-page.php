<?php
/**
 * Admin page for Age Gate - V3 Premium style inspired Design.
 *
 * @package King_Addons
 */

use King_Addons\Age_Gate;

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue shared V3 styles
wp_enqueue_style(
    'king-addons-admin-v3',
    KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
    [],
    KING_ADDONS_VERSION
);

$pages = get_pages(['number' => 200]);
$page_options = [];
foreach ($pages as $page) {
    $page_options[$page->ID] = $page->post_title;
}

$post_types = get_post_types(['public' => true], 'objects');
$elementor_templates = get_posts([
    'post_type' => 'elementor_library',
    'posts_per_page' => 50,
]);

// Normalize options to prevent undefined index notices on fresh installs/older saved settings.
$options = (isset($options) && is_array($options)) ? $options : [];
$is_premium = isset($is_premium) ? (bool) $is_premium : false;

$options['general'] = wp_parse_args($options['general'] ?? [], [
    'enabled' => 0,
    'audience' => 'guests',
    'mode' => 'confirm',
    'min_age' => 18,
    'cookie_days' => 30,
]);

$options['display'] = wp_parse_args($options['display'] ?? [], [
    'scope' => 'site',
    'exclude_ids' => [],
    'mode' => 'global',
    'cpt_scope' => [],
    'archives' => 0,
]);
$options['display']['exclude_ids'] = array_map('intval', (array) $options['display']['exclude_ids']);
$options['display']['cpt_scope'] = array_values(array_map('strval', (array) $options['display']['cpt_scope']));

$options['design'] = wp_parse_args($options['design'] ?? [], [
    'template' => 'center-card',
    'overlay_color' => '#000000',
    'overlay_opacity' => 0.6,
    'card_background' => '#ffffff',
    'card_width' => 420,
    'title' => __('Age Verification', 'king-addons'),
    'subtitle' => __('You must be 18+ to enter.', 'king-addons'),
    'text_color' => '#111111',
    'button_yes' => __('Yes', 'king-addons'),
    'button_no' => __('No', 'king-addons'),
    'title_size' => 24,
    'body_size' => 16,
    'title_weight' => 700,
    'body_weight' => 400,
    'button_yes_color' => '#ffffff',
    'button_yes_bg' => '#6d28d9',
    'button_no_color' => '#111111',
    'button_no_bg' => '#e5e7eb',
    'button_yes_hover_bg' => '#5b21b6',
    'button_yes_hover_color' => '#ffffff',
    'button_no_hover_bg' => '#d1d5db',
    'button_no_hover_color' => '#111111',
    'animation' => 'fade',
    'elementor_template' => 0,
    'logo' => '',
    'background_image' => '',
]);
$options['design']['elementor_template'] = (int) $options['design']['elementor_template'];

$options['behaviour'] = wp_parse_args($options['behaviour'] ?? [], [
    'deny_action' => 'redirect_url',
    'deny_redirect_page' => 0,
    'deny_redirect_url' => 'https://google.com',
    'block_message' => __('Access denied.', 'king-addons'),
    'reset_on_rule_change' => 0,
    'repeat_mode' => 'days',
    'consent_checkbox' => 0,
]);
$options['behaviour']['deny_action'] = ($options['behaviour']['deny_action'] === 'redirect') ? 'redirect_page' : $options['behaviour']['deny_action'];
$options['behaviour']['deny_redirect_page'] = (int) $options['behaviour']['deny_redirect_page'];
$options['behaviour']['deny_redirect_url'] = is_string($options['behaviour']['deny_redirect_url']) ? trim($options['behaviour']['deny_redirect_url']) : 'https://google.com';

$options['dob'] = wp_parse_args($options['dob'] ?? [], [
    'format' => 'dmy',
    'max_age' => 120,
    'error_invalid' => __('Please enter a valid date.', 'king-addons'),
    'error_denied' => __('You do not meet the minimum age requirement.', 'king-addons'),
]);
$options['dob']['max_age'] = (int) $options['dob']['max_age'];

$options['geo'] = wp_parse_args($options['geo'] ?? [], [
    'enabled' => 0,
    'default_age' => 18,
    'map' => [],
]);
$options['geo']['default_age'] = (int) $options['geo']['default_age'];
if (is_string($options['geo']['map'])) {
    $map = [];
    foreach (preg_split('/\r\n|\r|\n/', $options['geo']['map']) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '=') === false) {
            continue;
        }
        [$code, $age] = array_map('trim', explode('=', $line, 2));
        if ($code === '') {
            continue;
        }
        $map[strtoupper($code)] = (int) $age;
    }
    $options['geo']['map'] = $map;
}
if (!is_array($options['geo']['map'])) {
    $options['geo']['map'] = [];
}

// Theme mode is per-user
$theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
$allowed_theme_modes = ['dark', 'light', 'auto'];
if (!in_array($theme_mode, $allowed_theme_modes, true)) {
    $theme_mode = 'dark';
}
?>
<script>
(function() {
    const mode = '<?php echo esc_js($theme_mode); ?>';
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    const isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
    document.documentElement.classList.toggle('ka-v3-dark', isDark);
    document.body && document.body.classList.add('ka-admin-v3');
    document.body && document.body.classList.toggle('ka-v3-dark', isDark);
})();
</script>

<div class="ka-admin-wrap">
    <!-- Header -->
    <div class="ka-admin-header">
        <div class="ka-admin-header-left">
            <div class="ka-admin-header-icon purple">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Age Gate', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('Age verification overlay for restricted content', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo !empty($options['general']['enabled']) ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo !empty($options['general']['enabled']) ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
            </span>
            <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup" aria-label="Theme" data-active="<?php echo esc_attr($theme_mode); ?>">
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

    <?php settings_errors('king_addons_age_gate'); ?>

    <!-- Tabs -->
    <div class="ka-tabs">
        <button type="button" class="ka-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="display"><?php esc_html_e('Display Rules', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="design"><?php esc_html_e('Design', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="behaviour"><?php esc_html_e('Behaviour', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="pro">
            <?php esc_html_e('Pro Features', 'king-addons'); ?>
            <?php if (!$is_premium): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </button>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('king_addons_age_gate'); ?>

        <!-- General Tab -->
        <div class="ka-tab-content active" data-tab="general">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Enable', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_age_gate_options[general][enabled]" value="1" <?php checked(!empty($options['general']['enabled'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Turn on age verification', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Audience', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[general][audience]">
                                <option value="guests" <?php selected($options['general']['audience'], 'guests'); ?>><?php esc_html_e('Guests only', 'king-addons'); ?></option>
                                <option value="all" <?php selected($options['general']['audience'], 'all'); ?>><?php esc_html_e('Guests and logged-in users', 'king-addons'); ?></option>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Show gate to logged-in users too?', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Verification Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-radio-group">
                                <label class="ka-radio-item">
                                    <input type="radio" name="king_addons_age_gate_options[general][mode]" value="confirm" <?php checked($options['general']['mode'], 'confirm'); ?>>
                                    <?php esc_html_e('Simple confirmation (Yes/No)', 'king-addons'); ?>
                                </label>
                                <label class="ka-radio-item">
                                    <input type="radio" name="king_addons_age_gate_options[general][mode]" value="minimum" <?php checked($options['general']['mode'], 'minimum'); ?>>
                                    <?php esc_html_e('Minimum age text', 'king-addons'); ?>
                                </label>
                                <label class="ka-radio-item" style="<?php echo $is_premium ? '' : 'opacity: 0.6;'; ?>">
                                    <input type="radio" name="king_addons_age_gate_options[general][mode]" value="dob" <?php checked($options['general']['mode'], 'dob'); ?> <?php disabled(!$is_premium); ?>>
                                    <?php esc_html_e('Date of birth', 'king-addons'); ?>
                                    <?php if (!$is_premium): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Minimum Age', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" min="0" name="king_addons_age_gate_options[general][min_age]" value="<?php echo esc_attr($options['general']['min_age']); ?>" style="max-width: 100px;">
                            <span style="color: #86868b; margin-left: 8px;"><?php esc_html_e('years', 'king-addons'); ?></span>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cookie Lifetime', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" min="0" name="king_addons_age_gate_options[general][cookie_days]" value="<?php echo esc_attr($options['general']['cookie_days']); ?>" style="max-width: 100px;">
                            <span style="color: #86868b; margin-left: 8px;"><?php esc_html_e('days (0 = session)', 'king-addons'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Tab -->
        <div class="ka-tab-content" data-tab="display">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-visibility"></span>
                    <h2><?php esc_html_e('Display Rules', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Scope', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[display][scope]">
                                <option value="site" <?php selected($options['display']['scope'], 'site'); ?>><?php esc_html_e('Entire site', 'king-addons'); ?></option>
                                <option value="posts" <?php selected($options['display']['scope'], 'posts'); ?>><?php esc_html_e('All posts', 'king-addons'); ?></option>
                                <option value="pages" <?php selected($options['display']['scope'], 'pages'); ?>><?php esc_html_e('All pages', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Exclude Pages', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[display][exclude_ids][]" multiple size="5" style="max-width: 100%; min-height: 120px;">
                                <?php foreach ($page_options as $id => $title): ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected(in_array($id, $options['display']['exclude_ids'], true)); ?>>
                                        <?php echo esc_html($title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Hold Ctrl/Cmd to select multiple', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Display Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[display][mode]" <?php disabled(!$is_premium); ?>>
                                <option value="global" <?php selected($options['display']['mode'], 'global'); ?>><?php esc_html_e('Global (use scope)', 'king-addons'); ?></option>
                                <option value="custom" <?php selected($options['display']['mode'], 'custom'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('Custom rules', 'king-addons'); ?></option>
                            </select>
                            <?php if (!$is_premium): ?>
                                <p class="ka-row-desc"><?php esc_html_e('Custom rules available in Pro', 'king-addons'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($is_premium): ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Custom Post Types', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[display][cpt_scope][]" multiple size="4" style="max-width: 100%; min-height: 100px;">
                                <?php foreach ($post_types as $slug => $pt): ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $options['display']['cpt_scope'], true)); ?>>
                                        <?php echo esc_html($pt->labels->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Archives', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_age_gate_options[display][archives]" value="1" <?php checked(!empty($options['display']['archives'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show on archives', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Design Tab -->
        <div class="ka-tab-content" data-tab="design">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-art pink"></span>
                    <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Template', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[design][template]">
                                <option value="center-card" <?php selected($options['design']['template'], 'center-card'); ?>><?php esc_html_e('Centered card', 'king-addons'); ?></option>
                                <option value="bottom-card" <?php selected($options['design']['template'], 'bottom-card'); ?>><?php esc_html_e('Bottom card', 'king-addons'); ?></option>
                                <option value="top-card" <?php selected($options['design']['template'], 'top-card'); ?>><?php esc_html_e('Top card', 'king-addons'); ?></option>
                                <option value="side-left" <?php selected($options['design']['template'], 'side-left'); ?>><?php esc_html_e('Side panel (left)', 'king-addons'); ?></option>
                                <option value="side-right" <?php selected($options['design']['template'], 'side-right'); ?>><?php esc_html_e('Side panel (right)', 'king-addons'); ?></option>
                                <option value="fullscreen" <?php selected($options['design']['template'], 'fullscreen'); ?>><?php esc_html_e('Fullscreen modal', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Overlay', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Color', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][overlay_color]" value="<?php echo esc_attr($options['design']['overlay_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Opacity', 'king-addons'); ?></label>
                                    <input type="number" step="0.05" min="0" max="1" name="king_addons_age_gate_options[design][overlay_opacity]" value="<?php echo esc_attr($options['design']['overlay_opacity']); ?>" style="max-width: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Card', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Background', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][card_background]" value="<?php echo esc_attr($options['design']['card_background']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Width (px)', 'king-addons'); ?></label>
                                    <input type="number" min="280" name="king_addons_age_gate_options[design][card_width]" value="<?php echo esc_attr($options['design']['card_width']); ?>" style="max-width: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Text Content', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_age_gate_options[design][title]" value="<?php echo esc_attr($options['design']['title']); ?>" placeholder="<?php esc_attr_e('Title', 'king-addons'); ?>" style="margin-bottom: 12px;">
                            <input type="text" name="king_addons_age_gate_options[design][subtitle]" value="<?php echo esc_attr($options['design']['subtitle']); ?>" placeholder="<?php esc_attr_e('Subtitle', 'king-addons'); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Text Color', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="color" name="king_addons_age_gate_options[design][text_color]" value="<?php echo esc_attr($options['design']['text_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Button Labels', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Yes', 'king-addons'); ?></label>
                                    <input type="text" name="king_addons_age_gate_options[design][button_yes]" value="<?php echo esc_attr($options['design']['button_yes']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('No', 'king-addons'); ?></label>
                                    <input type="text" name="king_addons_age_gate_options[design][button_no]" value="<?php echo esc_attr($options['design']['button_no']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Typography', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2" style="gap: 16px;">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Title Size', 'king-addons'); ?></label>
                                    <input type="number" min="10" name="king_addons_age_gate_options[design][title_size]" value="<?php echo esc_attr($options['design']['title_size']); ?>" style="max-width: 100px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Body Size', 'king-addons'); ?></label>
                                    <input type="number" min="10" name="king_addons_age_gate_options[design][body_size]" value="<?php echo esc_attr($options['design']['body_size']); ?>" style="max-width: 100px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Title Weight', 'king-addons'); ?></label>
                                    <input type="number" min="100" step="100" name="king_addons_age_gate_options[design][title_weight]" value="<?php echo esc_attr($options['design']['title_weight']); ?>" style="max-width: 100px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Body Weight', 'king-addons'); ?></label>
                                    <input type="number" min="100" step="100" name="king_addons_age_gate_options[design][body_weight]" value="<?php echo esc_attr($options['design']['body_weight']); ?>" style="max-width: 100px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Button Styles', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2" style="gap: 16px;">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Yes Text', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_yes_color]" value="<?php echo esc_attr($options['design']['button_yes_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Yes BG', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_yes_bg]" value="<?php echo esc_attr($options['design']['button_yes_bg']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('No Text', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_no_color]" value="<?php echo esc_attr($options['design']['button_no_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('No BG', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_no_bg]" value="<?php echo esc_attr($options['design']['button_no_bg']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($is_premium): ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Hover Styles', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2" style="gap: 16px;">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Yes Hover BG', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_yes_hover_bg]" value="<?php echo esc_attr($options['design']['button_yes_hover_bg']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Yes Hover Text', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_yes_hover_color]" value="<?php echo esc_attr($options['design']['button_yes_hover_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('No Hover BG', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_no_hover_bg]" value="<?php echo esc_attr($options['design']['button_no_hover_bg']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('No Hover Text', 'king-addons'); ?></label>
                                    <input type="color" name="king_addons_age_gate_options[design][button_no_hover_color]" value="<?php echo esc_attr($options['design']['button_no_hover_color']); ?>" style="width: 60px; height: 40px; padding: 2px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Animation', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[design][animation]">
                                <option value="none" <?php selected($options['design']['animation'], 'none'); ?>><?php esc_html_e('None', 'king-addons'); ?></option>
                                <option value="fade" <?php selected($options['design']['animation'], 'fade'); ?>><?php esc_html_e('Fade', 'king-addons'); ?></option>
                                <option value="slide-up" <?php selected($options['design']['animation'], 'slide-up'); ?>><?php esc_html_e('Slide up', 'king-addons'); ?></option>
                                <option value="zoom" <?php selected($options['design']['animation'], 'zoom'); ?>><?php esc_html_e('Zoom', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Elementor Template', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[design][elementor_template]">
                                <option value="0"><?php esc_html_e('— None —', 'king-addons'); ?></option>
                                <?php foreach ($elementor_templates as $tmpl): ?>
                                    <option value="<?php echo esc_attr($tmpl->ID); ?>" <?php selected((int)$options['design']['elementor_template'], (int)$tmpl->ID); ?>>
                                        <?php echo esc_html($tmpl->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Brand Assets', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="url" name="king_addons_age_gate_options[design][logo]" value="<?php echo esc_attr($options['design']['logo']); ?>" placeholder="<?php esc_attr_e('Logo URL', 'king-addons'); ?>" style="margin-bottom: 12px;">
                            <input type="url" name="king_addons_age_gate_options[design][background_image]" value="<?php echo esc_attr($options['design']['background_image']); ?>" placeholder="<?php esc_attr_e('Background image URL', 'king-addons'); ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Behaviour Tab -->
        <div class="ka-tab-content" data-tab="behaviour">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-controls-play"></span>
                    <h2><?php esc_html_e('Behaviour Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('On Denial', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[behaviour][deny_action]">
                                <option value="redirect_page" <?php selected($options['behaviour']['deny_action'], 'redirect_page'); ?>><?php esc_html_e('Redirect to page', 'king-addons'); ?></option>
                                <option value="redirect_url" <?php selected($options['behaviour']['deny_action'], 'redirect_url'); ?>><?php esc_html_e('Redirect to URL', 'king-addons'); ?></option>
                                <option value="block" <?php selected($options['behaviour']['deny_action'], 'block'); ?>><?php esc_html_e('Show blocked message', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row" id="ka-age-gate-deny-page-row">
                        <div class="ka-row-label"><?php esc_html_e('Redirect Page', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[behaviour][deny_redirect_page]">
                                <option value="0"><?php esc_html_e('— Select page —', 'king-addons'); ?></option>
                                <?php foreach ($page_options as $id => $title): ?>
                                    <option value="<?php echo esc_attr($id); ?>" <?php selected((int)$options['behaviour']['deny_redirect_page'], (int)$id); ?>>
                                        <?php echo esc_html($title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('If a page is selected, it will be used for redirect.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row" id="ka-age-gate-deny-url-row">
                        <div class="ka-row-label"><?php esc_html_e('Redirect URL', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="url" name="king_addons_age_gate_options[behaviour][deny_redirect_url]" value="<?php echo esc_attr($options['behaviour']['deny_redirect_url']); ?>" placeholder="https://google.com">
                            <p class="ka-row-desc"><?php esc_html_e('Used only when no redirect page is selected.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Block Message', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="king_addons_age_gate_options[behaviour][block_message]" value="<?php echo esc_attr($options['behaviour']['block_message']); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Reset on Change', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_age_gate_options[behaviour][reset_on_rule_change]" value="1" <?php checked(!empty($options['behaviour']['reset_on_rule_change'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Invalidate decisions on save', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <?php if ($is_premium): ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Repeat Prompt', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="king_addons_age_gate_options[behaviour][repeat_mode]">
                                <option value="days" <?php selected($options['behaviour']['repeat_mode'], 'days'); ?>><?php esc_html_e('Remember N days', 'king-addons'); ?></option>
                                <option value="session" <?php selected($options['behaviour']['repeat_mode'], 'session'); ?>><?php esc_html_e('Every session', 'king-addons'); ?></option>
                                <option value="once" <?php selected($options['behaviour']['repeat_mode'], 'once'); ?>><?php esc_html_e('Forever', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Consent Checkbox', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="king_addons_age_gate_options[behaviour][consent_checkbox]" value="1" <?php checked(!empty($options['behaviour']['consent_checkbox'])); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Require consent checkbox', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pro Tab -->
        <div class="ka-tab-content" data-tab="pro">
            <?php if (!$is_premium): ?>
                <div class="ka-pro-notice">
                    <h2><?php esc_html_e('Pro Features', 'king-addons'); ?></h2>
                    <p><?php esc_html_e('Upgrade to Pro for DOB validation, geo rules, and advanced features.', 'king-addons'); ?></p>
                    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink"><?php esc_html_e('Upgrade Now', 'king-addons'); ?></a>
                </div>
            <?php else: ?>
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h2><?php esc_html_e('Date of Birth Validation', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Date Format', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <select name="king_addons_age_gate_options[dob][format]">
                                    <option value="dmy" <?php selected($options['dob']['format'], 'dmy'); ?>>DD/MM/YYYY</option>
                                    <option value="mdy" <?php selected($options['dob']['format'], 'mdy'); ?>>MM/DD/YYYY</option>
                                    <option value="ymd" <?php selected($options['dob']['format'], 'ymd'); ?>>YYYY/MM/DD</option>
                                </select>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Max Age', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" min="10" name="king_addons_age_gate_options[dob][max_age]" value="<?php echo esc_attr($options['dob']['max_age']); ?>" style="max-width: 100px;">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Error Messages', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="text" name="king_addons_age_gate_options[dob][error_invalid]" value="<?php echo esc_attr($options['dob']['error_invalid']); ?>" placeholder="<?php esc_attr_e('Invalid date message', 'king-addons'); ?>" style="margin-bottom: 12px;">
                                <input type="text" name="king_addons_age_gate_options[dob][error_denied]" value="<?php echo esc_attr($options['dob']['error_denied']); ?>" placeholder="<?php esc_attr_e('Under age message', 'king-addons'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-admin-site"></span>
                        <h2><?php esc_html_e('Geo Rules', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Enable', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="king_addons_age_gate_options[geo][enabled]" value="1" <?php checked(!empty($options['geo']['enabled'])); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Country-based minimum age', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Default Age', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" min="0" name="king_addons_age_gate_options[geo][default_age]" value="<?php echo esc_attr($options['geo']['default_age']); ?>" style="max-width: 100px;">
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Country Map', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <textarea name="king_addons_age_gate_options[geo][map]" rows="5" placeholder="US=21&#10;UK=18&#10;DE=16" style="max-width: 300px;"><?php
                                    foreach ($options['geo']['map'] as $code => $age) {
                                        echo esc_html($code . '=' . $age . "\n");
                                    }
                                ?></textarea>
                                <p class="ka-row-desc"><?php esc_html_e('One per line: CODE=AGE', 'king-addons'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submit -->
        <div class="ka-card">
            <div class="ka-submit">
                <button type="submit" class="ka-btn ka-btn-primary"><?php esc_html_e('Save Settings', 'king-addons'); ?></button>
            </div>
        </div>
    </form>
</div>

<script>
(function($) {
    'use strict';

    // Tab navigation
    const tabs = document.querySelectorAll('.ka-tab');
    const contents = document.querySelectorAll('.ka-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.querySelector(`.ka-tab-content[data-tab="${target}"]`).classList.add('active');
        });
    });

    // Behaviour: toggle redirect destination fields.
    (function() {
        const select = document.querySelector('select[name="king_addons_age_gate_options[behaviour][deny_action]"]');
        const pageRow = document.getElementById('ka-age-gate-deny-page-row');
        const urlRow = document.getElementById('ka-age-gate-deny-url-row');

        function sync() {
            if (!select) {
                return;
            }
            const value = (select.value || '').toString();
            if (pageRow) {
                pageRow.style.display = value === 'redirect_page' ? '' : 'none';
            }
            if (urlRow) {
                urlRow.style.display = value === 'redirect_url' ? '' : 'none';
            }
        }

        if (select) {
            select.addEventListener('change', sync);
            sync();
        }
    })();
})(jQuery);

// Theme segmented control (dashboard-style)
(function() {
    const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const nonce = '<?php echo esc_js(wp_create_nonce('king_addons_dashboard_ui')); ?>';
    const segment = document.getElementById('ka-v3-theme-segment');
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    let mode = (segment && segment.getAttribute('data-active') ? segment.getAttribute('data-active') : 'dark').toString();
    let mqlHandler = null;

    function saveUISetting(key, value) {
        const body = new URLSearchParams();
        body.set('action', 'king_addons_save_dashboard_ui');
        body.set('nonce', nonce);
        body.set('key', key);
        body.set('value', value);
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
    }

    function updateSegment(activeMode) {
        if (!segment) {
            return;
        }
        segment.setAttribute('data-active', activeMode);
        segment.querySelectorAll('.ka-v3-segmented-btn').forEach((btn) => {
            const theme = btn.getAttribute('data-theme');
            btn.setAttribute('aria-pressed', theme === activeMode ? 'true' : 'false');
        });
    }

    function applyTheme(isDark) {
        document.body.classList.toggle('ka-v3-dark', isDark);
        document.documentElement.classList.toggle('ka-v3-dark', isDark);
    }

    function setThemeMode(nextMode, save) {
        mode = nextMode;
        updateSegment(nextMode);

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

    if (segment) {
        segment.addEventListener('click', function(e) {
            const btn = e.target.closest('.ka-v3-segmented-btn');
            if (!btn) return;
            e.preventDefault();
            setThemeMode((btn.getAttribute('data-theme') || 'dark').toString(), true);
        });
    }

    if (segment) {
        setThemeMode(mode, false);
    }
})();
</script>
