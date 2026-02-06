<?php
/**
 * Cookie / Consent Bar admin page - V3 Premium style inspired Design.
 *
 * @var array<string, mixed> $options
 * @var bool $is_premium
 * @package King_Addons
 */

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

// Enqueue WP color picker
wp_enqueue_style('wp-color-picker');
wp_enqueue_script('wp-color-picker');

$notices = [];
if (isset($_GET['updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $notices[] = esc_html__('Settings saved.', 'king-addons');
}
if (isset($_GET['imported'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $notices[] = esc_html__('Settings imported.', 'king-addons');
}
if (isset($_GET['logs_cleared'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $notices[] = esc_html__('Logs cleared.', 'king-addons');
}

$categories = $options['categories'];
$empty_slots = $is_premium ? 2 : 1;
for ($i = 0; $i < $empty_slots; $i++) {
    $categories[] = [
        'key' => '',
        'label' => '',
        'description' => '',
        'state' => 'off',
        'display' => true,
    ];
}

$script_rules = $options['scripts'];
if (count($script_rules) < 3) {
    $script_rules = array_merge($script_rules, array_fill(0, 3 - count($script_rules), [
        'handle' => '',
        'category' => 'analytics',
        'mode' => 'block',
    ]));
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
            <div class="ka-admin-header-icon">
                <span class="dashicons dashicons-shield"></span>
            </div>
            <div>
                <h1 class="ka-admin-title"><?php esc_html_e('Cookie Consent', 'king-addons'); ?></h1>
                <p class="ka-admin-subtitle"><?php esc_html_e('GDPR & CCPA compliant consent management', 'king-addons'); ?></p>
            </div>
        </div>
        <div class="ka-admin-header-actions">
            <span class="ka-status <?php echo $options['enabled'] ? 'ka-status-enabled' : 'ka-status-disabled'; ?>">
                <span class="ka-status-dot"></span>
                <?php echo $options['enabled'] ? esc_html__('Enabled', 'king-addons') : esc_html__('Disabled', 'king-addons'); ?>
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

    <!-- Notices -->
    <?php foreach ($notices as $notice): ?>
        <div class="ka-card" style="background: rgba(52, 199, 89, 0.1); border: 1px solid rgba(52, 199, 89, 0.3); margin-bottom: 20px;">
            <div class="ka-card-body" style="padding: 16px 24px; display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-yes-alt" style="color: #34c759;"></span>
                <span style="color: #34c759; font-weight: 500;"><?php echo esc_html($notice); ?></span>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Tabs -->
    <div class="ka-tabs">
        <button type="button" class="ka-tab active" data-tab="general"><?php esc_html_e('General', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="content"><?php esc_html_e('Content', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="categories"><?php esc_html_e('Categories', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="design"><?php esc_html_e('Design', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="scripts"><?php esc_html_e('Scripts', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="behavior"><?php esc_html_e('Behavior', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'king-addons'); ?></button>
        <button type="button" class="ka-tab" data-tab="logs">
            <?php esc_html_e('Logs', 'king-addons'); ?>
            <?php if (!$is_premium): ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </button>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('king_addons_cookie_consent_save'); ?>
        <input type="hidden" name="action" value="king_addons_cookie_consent_save">

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
                                <input type="checkbox" name="enabled" value="1" <?php checked($options['enabled']); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Show consent banner', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Mode', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="mode">
                                <option value="gdpr" <?php selected($options['mode'], 'gdpr'); ?>><?php esc_html_e('GDPR (EU)', 'king-addons'); ?></option>
                                <option value="ccpa" <?php selected($options['mode'], 'ccpa'); ?>><?php esc_html_e('CCPA (California)', 'king-addons'); ?></option>
                                <option value="simple" <?php selected($options['mode'], 'simple'); ?>><?php esc_html_e('Simple banner', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Region', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="region_targeting" id="kng-cookie-region-targeting" <?php disabled(!$is_premium && $options['region_targeting'] !== 'all'); ?>>
                                <option value="all" <?php selected($options['region_targeting'], 'all'); ?>><?php esc_html_e('All visitors', 'king-addons'); ?></option>
                                <option value="eu" <?php selected($options['region_targeting'], 'eu'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('EU only', 'king-addons'); ?></option>
                                <option value="us" <?php selected($options['region_targeting'], 'us'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('US only', 'king-addons'); ?></option>
                                <option value="custom" <?php selected($options['region_targeting'], 'custom'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('Custom', 'king-addons'); ?></option>
                            </select>
                            <?php if (!$is_premium): ?>
                                <p class="ka-row-desc"><?php esc_html_e('Pro: Geo-targeting with cached lookup', 'king-addons'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($is_premium): ?>
                    <div class="ka-row" id="kng-cookie-custom-regions-row" style="<?php echo ($options['region_targeting'] ?? 'all') === 'custom' ? '' : 'display:none;'; ?>">
                        <div class="ka-row-label"><?php esc_html_e('Custom regions', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <textarea name="custom_regions" rows="2" placeholder="eu, us, CA, AU" style="width: 100%; max-width: 520px;"><?php echo esc_textarea($options['custom_regions'] ?? ''); ?></textarea>
                            <p class="ka-row-desc"><?php esc_html_e('Comma-separated list. Use “eu” for EU, “us” for United States, and ISO-2 codes for other countries (e.g. CA, AU).', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Consent Lifetime', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="consent_lifetime">
                                <option value="30" <?php selected($options['consent_lifetime'], '30'); ?>><?php esc_html_e('1 month', 'king-addons'); ?></option>
                                <option value="90" <?php selected($options['consent_lifetime'], '90'); ?>><?php esc_html_e('3 months', 'king-addons'); ?></option>
                                <option value="180" <?php selected($options['consent_lifetime'], '180'); ?>><?php esc_html_e('6 months', 'king-addons'); ?></option>
                                <option value="365" <?php selected($options['consent_lifetime'], '365'); ?>><?php esc_html_e('1 year', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Policy Version', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="policy_version" value="<?php echo esc_attr($options['policy_version']); ?>" style="max-width: 100px;">
                            <p class="ka-row-desc"><?php esc_html_e('Increase to re-show banner after policy update', 'king-addons'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var select = document.getElementById('kng-cookie-region-targeting');
            var row = document.getElementById('kng-cookie-custom-regions-row');
            if (!select || !row) return;
            var toggle = function() {
                row.style.display = (select.value === 'custom') ? '' : 'none';
            };
            select.addEventListener('change', toggle);
            toggle();
        });
        </script>

        <!-- Content Tab -->
        <div class="ka-tab-content" data-tab="content">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-edit"></span>
                    <h2><?php esc_html_e('Banner Content', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Template', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="template">
                                <option value="gdpr_minimal" <?php selected($options['template'], 'gdpr_minimal'); ?>><?php esc_html_e('GDPR Minimal', 'king-addons'); ?></option>
                                <option value="ccpa_minimal" <?php selected($options['template'], 'ccpa_minimal'); ?>><?php esc_html_e('CCPA Minimal', 'king-addons'); ?></option>
                                <option value="custom" <?php selected($options['template'], 'custom'); ?>><?php esc_html_e('Custom', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Title', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="content[title]" value="<?php echo esc_attr($options['content']['title']); ?>">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Message', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <textarea name="content[message]" rows="3" style="max-width: 100%;"><?php echo esc_textarea($options['content']['message']); ?></textarea>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Privacy Link', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('URL', 'king-addons'); ?></label>
                                    <input type="url" name="content[privacy_url]" value="<?php echo esc_attr($options['content']['privacy_url']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Label', 'king-addons'); ?></label>
                                    <input type="text" name="content[privacy_label]" value="<?php echo esc_attr($options['content']['privacy_label']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cookie Policy', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('URL', 'king-addons'); ?></label>
                                    <input type="url" name="content[cookie_url]" value="<?php echo esc_attr($options['content']['cookie_url']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Label', 'king-addons'); ?></label>
                                    <input type="text" name="content[cookie_label]" value="<?php echo esc_attr($options['content']['cookie_label']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Button Text', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2">
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Accept', 'king-addons'); ?></label>
                                    <input type="text" name="buttons[accept]" value="<?php echo esc_attr($options['buttons']['accept']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Reject', 'king-addons'); ?></label>
                                    <input type="text" name="buttons[reject]" value="<?php echo esc_attr($options['buttons']['reject']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Settings', 'king-addons'); ?></label>
                                    <input type="text" name="buttons[settings]" value="<?php echo esc_attr($options['buttons']['settings']); ?>">
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php esc_html_e('Save', 'king-addons'); ?></label>
                                    <input type="text" name="buttons[save]" value="<?php echo esc_attr($options['buttons']['save']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div class="ka-tab-content" data-tab="categories">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-category"></span>
                    <h2><?php esc_html_e('Cookie Categories', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <p class="ka-row-desc" style="margin: 0 0 20px;"><?php esc_html_e('Define consent categories. "Necessary" is always required.', 'king-addons'); ?></p>
                    <table class="ka-table">
                        <thead>
                            <tr>
                                <th style="width: 15%;"><?php esc_html_e('Key', 'king-addons'); ?></th>
                                <th style="width: 20%;"><?php esc_html_e('Label', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Description', 'king-addons'); ?></th>
                                <th style="width: 15%;"><?php esc_html_e('Default', 'king-addons'); ?></th>
                                <th style="width: 10%;"><?php esc_html_e('Show', 'king-addons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td><input type="text" name="categories[<?php echo esc_attr($index); ?>][key]" value="<?php echo esc_attr($category['key']); ?>" <?php disabled($category['key'] === 'necessary'); ?>></td>
                                    <td><input type="text" name="categories[<?php echo esc_attr($index); ?>][label]" value="<?php echo esc_attr($category['label']); ?>"></td>
                                    <td><textarea name="categories[<?php echo esc_attr($index); ?>][description]" rows="2"><?php echo esc_textarea($category['description']); ?></textarea></td>
                                    <td>
                                        <select name="categories[<?php echo esc_attr($index); ?>][state]" <?php disabled($category['key'] === 'necessary'); ?>>
                                            <option value="required" <?php selected($category['state'], 'required'); ?>><?php esc_html_e('Required', 'king-addons'); ?></option>
                                            <option value="on" <?php selected($category['state'], 'on'); ?>><?php esc_html_e('On', 'king-addons'); ?></option>
                                            <option value="off" <?php selected($category['state'], 'off'); ?>><?php esc_html_e('Off', 'king-addons'); ?></option>
                                        </select>
                                    </td>
                                    <td style="text-align: center;"><input type="checkbox" name="categories[<?php echo esc_attr($index); ?>][display]" value="1" <?php checked($category['display']); ?>></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                        <div class="ka-row-label"><?php esc_html_e('Layout', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="design[layout]">
                                <option value="bottom-bar" <?php selected($options['design']['layout'], 'bottom-bar'); ?>><?php esc_html_e('Bottom bar', 'king-addons'); ?></option>
                                <option value="top-bar" <?php selected($options['design']['layout'], 'top-bar'); ?>><?php esc_html_e('Top bar', 'king-addons'); ?></option>
                                <option value="bottom-left" <?php selected($options['design']['layout'], 'bottom-left'); ?>><?php esc_html_e('Bottom left', 'king-addons'); ?></option>
                                <option value="bottom-right" <?php selected($options['design']['layout'], 'bottom-right'); ?>><?php esc_html_e('Bottom right', 'king-addons'); ?></option>
                                <option value="modal" <?php selected($options['design']['layout'], 'modal'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('Modal', 'king-addons'); ?><?php if (!$is_premium): ?> (Pro)<?php endif; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Preset', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="design[preset]">
                                <option value="light" <?php selected($options['design']['preset'], 'light'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                                <option value="dark" <?php selected($options['design']['preset'], 'dark'); ?>><?php esc_html_e('Dark', 'king-addons'); ?></option>
                                <option value="minimal" <?php selected($options['design']['preset'], 'minimal'); ?>><?php esc_html_e('Minimal', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Colors', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <div class="ka-grid-2" style="gap: 16px;">
                                <?php foreach ($options['design']['colors'] as $color_key => $color_value): ?>
                                    <div>
                                        <label style="font-size: 12px; color: #86868b; display: block; margin-bottom: 6px;"><?php echo esc_html(ucwords(str_replace('_', ' ', $color_key))); ?></label>
                                        <input type="text" class="ka-color-picker" name="design[colors][<?php echo esc_attr($color_key); ?>]" value="<?php echo esc_attr($color_value); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Border Radius', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="number" min="0" name="design[border_radius]" value="<?php echo esc_attr($options['design']['border_radius']); ?>" style="max-width: 100px;">
                            <span style="color: #86868b; margin-left: 8px;">px</span>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Shadow', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="design[shadow]" value="1" <?php checked($options['design']['shadow']); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Enable shadow', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Animation', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="design[animation]">
                                <option value="fade" <?php selected($options['design']['animation'], 'fade'); ?>><?php esc_html_e('Fade', 'king-addons'); ?></option>
                                <option value="slide-up" <?php selected($options['design']['animation'], 'slide-up'); ?>><?php esc_html_e('Slide up', 'king-addons'); ?></option>
                                <option value="slide-down" <?php selected($options['design']['animation'], 'slide-down'); ?>><?php esc_html_e('Slide down', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts Tab -->
        <div class="ka-tab-content" data-tab="scripts">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-editor-code"></span>
                    <h2><?php esc_html_e('Script Blocking', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <p class="ka-row-desc" style="margin: 0 0 12px;"><?php esc_html_e('Control scripts by WordPress script handle. Free: up to 3 rules.', 'king-addons'); ?></p>
                    <p class="ka-row-desc" style="margin: 0 0 20px;">
                        <strong><?php esc_html_e('Mode:', 'king-addons'); ?></strong>
                        <?php esc_html_e('“Block” keeps the script tag in place but prevents execution until consent. “Allow” removes the script URL to avoid downloading it at all until consent (recommended for third-party scripts).', 'king-addons'); ?>
                    </p>
                    <table class="ka-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Script Handle', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Category', 'king-addons'); ?></th>
                                <th><?php esc_html_e('Mode', 'king-addons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $row_index = 0; ?>
                            <?php foreach ($script_rules as $rule): ?>
                                <?php if (!$is_premium && $row_index >= 3) { break; } ?>
                                <tr>
                                    <td><input type="text" name="scripts[<?php echo esc_attr($row_index); ?>][handle]" value="<?php echo esc_attr($rule['handle']); ?>" placeholder="google-analytics"></td>
                                    <td>
                                        <select name="scripts[<?php echo esc_attr($row_index); ?>][category]">
                                            <option value="analytics" <?php selected($rule['category'], 'analytics'); ?>><?php esc_html_e('Analytics', 'king-addons'); ?></option>
                                            <option value="marketing" <?php selected($rule['category'], 'marketing'); ?>><?php esc_html_e('Marketing', 'king-addons'); ?></option>
                                            <option value="other" <?php selected($rule['category'], 'other'); ?>><?php esc_html_e('Other', 'king-addons'); ?></option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="scripts[<?php echo esc_attr($row_index); ?>][mode]">
                                            <option value="block" <?php selected($rule['mode'], 'block'); ?>><?php esc_html_e('Block until accepted', 'king-addons'); ?></option>
                                            <option value="allow" <?php selected($rule['mode'], 'allow'); ?>><?php esc_html_e('Load if accepted', 'king-addons'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <?php $row_index++; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Behavior Tab -->
        <div class="ka-tab-content" data-tab="behavior">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-page"></span>
                    <h2><?php esc_html_e('Behavior Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Show On', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="behavior[show_on]" <?php disabled(!$is_premium && $options['behavior']['show_on'] !== 'all'); ?>>
                                <option value="all" <?php selected($options['behavior']['show_on'], 'all'); ?>><?php esc_html_e('All pages', 'king-addons'); ?></option>
                                <option value="include" <?php selected($options['behavior']['show_on'], 'include'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('Only specific pages', 'king-addons'); ?><?php if (!$is_premium): ?> (Pro)<?php endif; ?></option>
                                <option value="exclude" <?php selected($options['behavior']['show_on'], 'exclude'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('All except', 'king-addons'); ?><?php if (!$is_premium): ?> (Pro)<?php endif; ?></option>
                            </select>
                            <?php if ($is_premium): ?>
                                <p class="ka-row-desc"><?php esc_html_e('Comma-separated page IDs', 'king-addons'); ?></p>
                                <textarea name="behavior[include_pages]" rows="2" style="max-width: 300px; margin-top: 8px;"><?php echo esc_textarea($options['behavior']['include_pages']); ?></textarea>
                                <textarea name="behavior[exclude_pages]" rows="2" style="max-width: 300px; margin-top: 4px;"><?php echo esc_textarea($options['behavior']['exclude_pages']); ?></textarea>
                            <?php else: ?>
                                <input type="hidden" name="behavior[include_pages]" value="<?php echo esc_attr($options['behavior']['include_pages']); ?>">
                                <input type="hidden" name="behavior[exclude_pages]" value="<?php echo esc_attr($options['behavior']['exclude_pages']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Resurface', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="behavior[resurface]">
                                <option value="version" <?php selected($options['behavior']['resurface'], 'version'); ?>><?php esc_html_e('On policy version change', 'king-addons'); ?></option>
                                <option value="never" <?php selected($options['behavior']['resurface'], 'never'); ?>><?php esc_html_e('Never', 'king-addons'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Consent Triggers', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle" style="margin-bottom: 16px;">
                                <input type="checkbox" name="behavior[scroll_consent]" value="1" <?php checked($options['behavior']['scroll_consent']); ?> <?php disabled(!$is_premium); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Scroll = consent', 'king-addons'); ?><?php if (!$is_premium): ?> <span class="ka-pro-badge">PRO</span><?php endif; ?></span>
                            </label>
                            <br>
                            <label class="ka-toggle">
                                <input type="checkbox" name="behavior[click_consent]" value="1" <?php checked($options['behavior']['click_consent']); ?> <?php disabled(!$is_premium); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Click outside = consent', 'king-addons'); ?><?php if (!$is_premium): ?> <span class="ka-pro-badge">PRO</span><?php endif; ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="ka-tab-content" data-tab="advanced">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <h2><?php esc_html_e('Advanced Settings', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cookie Name', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="advanced[cookie_name]" value="<?php echo esc_attr($options['advanced']['cookie_name']); ?>" style="max-width: 200px;">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cookie Path', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="advanced[cookie_path]" value="<?php echo esc_attr($options['advanced']['cookie_path']); ?>" style="max-width: 100px;">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('SameSite', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="advanced[same_site]">
                                <option value="Lax" <?php selected($options['advanced']['same_site'], 'Lax'); ?>>Lax</option>
                                <option value="Strict" <?php selected($options['advanced']['same_site'], 'Strict'); ?>>Strict</option>
                                <option value="None" <?php selected($options['advanced']['same_site'], 'None'); ?>>None</option>
                            </select>
                            <p class="ka-row-desc"><?php esc_html_e('Use “None” only when you need consent stored in cross-site contexts. Note: browsers require Secure when SameSite=None.', 'king-addons'); ?></p>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Secure Only', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="advanced[secure]" value="1" <?php checked($options['advanced']['secure']); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('HTTPS only', 'king-addons'); ?></span>
                            </label>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Cookie Domain', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="text" name="advanced[cookie_domain]" value="<?php echo esc_attr($options['advanced']['cookie_domain']); ?>" placeholder="example.com" style="max-width: 200px;">
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Storage', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <select name="advanced[storage]">
                                <option value="cookie" <?php selected($options['advanced']['storage'], 'cookie'); ?>><?php esc_html_e('Cookie', 'king-addons'); ?></option>
                                <option value="local" <?php selected($options['advanced']['storage'], 'local'); ?> <?php disabled(!$is_premium); ?>><?php esc_html_e('Local Storage', 'king-addons'); ?><?php if (!$is_premium): ?> (Pro)<?php endif; ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Data Attributes', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <label class="ka-toggle">
                                <input type="checkbox" name="data_attribute_support" value="1" <?php checked($options['data_attribute_support']); ?> <?php disabled(!$is_premium); ?>>
                                <span class="ka-toggle-slider"></span>
                                <span class="ka-toggle-label"><?php esc_html_e('Enable data-attribute activation', 'king-addons'); ?><?php if (!$is_premium): ?> <span class="ka-pro-badge">PRO</span><?php endif; ?></span>
                            </label>
                            <p class="ka-row-desc" style="margin-top: 10px;">
                                <?php esc_html_e('When enabled, you can delay loading embeds/assets until consent by putting the real URL into a data attribute, plus setting the consent category.', 'king-addons'); ?>
                            </p>
                            <p class="ka-row-desc" style="margin-top: 6px;">
                                <strong><?php esc_html_e('Supported attributes:', 'king-addons'); ?></strong>
                                <?php echo esc_html('data-ka-cookie-src, data-ka-cookie-srcset, data-ka-cookie-href, data-ka-cookie-poster, data-ka-cookie-show'); ?>
                            </p>
                            <p class="ka-row-desc" style="margin-top: 6px;">
                                <strong><?php esc_html_e('Example:', 'king-addons'); ?></strong>
                                <?php echo esc_html('<iframe data-ka-cookie-category="marketing" data-ka-cookie-src="https://example.com/embed" data-ka-cookie-show hidden></iframe>'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($is_premium): ?>
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-migrate"></span>
                    <h2><?php esc_html_e('Import / Export', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Export', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" form="king-addons-cookie-consent-export"><?php esc_html_e('Download JSON', 'king-addons'); ?></button>
                        </div>
                    </div>
                    <div class="ka-row">
                        <div class="ka-row-label"><?php esc_html_e('Import', 'king-addons'); ?></div>
                        <div class="ka-row-field">
                            <input type="file" name="king_addons_cookie_import" form="king-addons-cookie-consent-import" accept="application/json" style="margin-bottom: 12px;">
                            <br>
                            <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" form="king-addons-cookie-consent-import"><?php esc_html_e('Import JSON', 'king-addons'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Logs Tab -->
        <div class="ka-tab-content" data-tab="logs">
            <?php if (!$is_premium): ?>
                <div class="ka-pro-notice">
                    <h2><?php esc_html_e('Consent Analytics', 'king-addons'); ?></h2>
                    <p><?php esc_html_e('Upgrade to Pro to track consent rates and manage logs.', 'king-addons'); ?></p>
                    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink"><?php esc_html_e('Upgrade Now', 'king-addons'); ?></a>
                </div>
            <?php else: ?>
                <div class="ka-card">
                    <div class="ka-card-header">
                        <span class="dashicons dashicons-chart-bar pink"></span>
                        <h2><?php esc_html_e('Consent Analytics', 'king-addons'); ?></h2>
                    </div>
                    <div class="ka-card-body">
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Enable Logs', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <label class="ka-toggle">
                                    <input type="checkbox" name="logs[enabled]" value="1" <?php checked($options['logs']['enabled']); ?>>
                                    <span class="ka-toggle-slider"></span>
                                    <span class="ka-toggle-label"><?php esc_html_e('Record consent events', 'king-addons'); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Retention', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <input type="number" name="logs[retention]" value="<?php echo esc_attr($options['logs']['retention']); ?>" min="30" style="max-width: 100px;">
                                <span style="color: #86868b; margin-left: 8px;"><?php esc_html_e('days', 'king-addons'); ?></span>
                            </div>
                        </div>

                        <?php
                        global $wpdb;
                        $table = $wpdb->prefix . \King_Addons\Cookie_Consent::LOG_TABLE;
                        $totals = ['accept' => 0, 'reject' => 0, 'custom' => 0];
                        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
                            $rows = $wpdb->get_results("SELECT action, COUNT(*) as total FROM {$table} GROUP BY action", ARRAY_A);
                            foreach ((array) $rows as $row) {
                                $totals[$row['action']] = (int) $row['total'];
                            }
                        }
                        ?>

                        <div class="ka-stats-grid" style="margin-top: 20px;">
                            <div class="ka-stat-card">
                                <div class="ka-stat-label"><?php esc_html_e('Accepted', 'king-addons'); ?></div>
                                <div class="ka-stat-value good"><?php echo esc_html($totals['accept']); ?></div>
                            </div>
                            <div class="ka-stat-card">
                                <div class="ka-stat-label"><?php esc_html_e('Rejected', 'king-addons'); ?></div>
                                <div class="ka-stat-value"><?php echo esc_html($totals['reject']); ?></div>
                            </div>
                            <div class="ka-stat-card">
                                <div class="ka-stat-label"><?php esc_html_e('Customized', 'king-addons'); ?></div>
                                <div class="ka-stat-value"><?php echo esc_html($totals['custom']); ?></div>
                            </div>
                        </div>

                        <div class="ka-row">
                            <div class="ka-row-label"><?php esc_html_e('Clear Logs', 'king-addons'); ?></div>
                            <div class="ka-row-field">
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <select name="retention" form="king-addons-cookie-consent-clear-logs" style="max-width: 200px;">
                                        <option value="30"><?php esc_html_e('Older than 30 days', 'king-addons'); ?></option>
                                        <option value="90"><?php esc_html_e('Older than 90 days', 'king-addons'); ?></option>
                                        <option value="365"><?php esc_html_e('Older than 1 year', 'king-addons'); ?></option>
                                        <option value="all"><?php esc_html_e('All logs', 'king-addons'); ?></option>
                                    </select>
                                    <button type="submit" class="ka-btn ka-btn-secondary ka-btn-sm" form="king-addons-cookie-consent-clear-logs"><?php esc_html_e('Clear', 'king-addons'); ?></button>
                                </div>
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

    <?php if ($is_premium): ?>
        <form id="king-addons-cookie-consent-export" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: none;">
            <?php wp_nonce_field('king_addons_cookie_consent_export'); ?>
            <input type="hidden" name="action" value="king_addons_cookie_consent_export">
        </form>
        <form id="king-addons-cookie-consent-import" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: none;">
            <?php wp_nonce_field('king_addons_cookie_consent_import'); ?>
            <input type="hidden" name="action" value="king_addons_cookie_consent_import">
        </form>
        <form id="king-addons-cookie-consent-clear-logs" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: none;">
            <?php wp_nonce_field('king_addons_cookie_consent_clear_logs'); ?>
            <input type="hidden" name="action" value="king_addons_cookie_consent_clear_logs">
        </form>
    <?php endif; ?>
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

    // Color pickers
    $(document).ready(function() {
        if ($.fn.wpColorPicker) {
            $('.ka-color-picker').wpColorPicker();
        }
    });
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
