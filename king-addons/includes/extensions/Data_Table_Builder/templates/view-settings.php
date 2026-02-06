<?php
/**
 * Data Table Builder settings view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$presets = [
    'minimal' => __('Minimal', 'king-addons'),
    'glass' => __('Glass', 'king-addons'),
    'contrast' => __('Contrast', 'king-addons'),
    'soft-gray' => __('Soft Gray', 'king-addons'),
    'modern-lines' => __('Modern Lines', 'king-addons'),
    'card-table' => __('Card Table', 'king-addons'),
    'dark-mode' => __('Dark Mode', 'king-addons'),
    'highlight-header' => __('Highlighted Header', 'king-addons'),
    'pricing' => __('Pricing Comparison', 'king-addons'),
    'feature-matrix' => __('Feature Matrix', 'king-addons'),
];
?>

<form method="post" action="options.php">
    <?php settings_fields('king_addons_table_builder'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-settings purple"></span>
            <h2><?php esc_html_e('General Settings', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Enable Table Builder', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" name="king_addons_table_builder_options[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Allow table creation', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Default Preset', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="king_addons_table_builder_options[default_preset]">
                        <?php foreach ($presets as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['default_preset'] ?? '', $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Default Theme', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="king_addons_table_builder_options[default_theme]">
                        <option value="dark" <?php selected($settings['default_theme'] ?? '', 'dark'); ?>><?php esc_html_e('Dark', 'king-addons'); ?></option>
                        <option value="light" <?php selected($settings['default_theme'] ?? '', 'light'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Rows per Page', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <input type="number" name="king_addons_table_builder_options[default_rows_per_page]" value="<?php echo esc_attr($settings['default_rows_per_page'] ?? 10); ?>" min="5" max="100">
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-lock pink"></span>
            <h2><?php esc_html_e('Advanced Settings', 'king-addons'); ?></h2>
            <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Role Access', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select disabled>
                        <option><?php esc_html_e('Administrator only', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Analytics Tracking', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <label class="ka-toggle">
                        <input type="checkbox" disabled />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Track table usage', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Settings', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
