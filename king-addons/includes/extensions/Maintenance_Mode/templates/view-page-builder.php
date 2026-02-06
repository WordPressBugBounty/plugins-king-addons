<?php
/**
 * Maintenance Mode page builder view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_source = $settings['template_source'] ?? 'built_in';
$template_id = $settings['template_id'] ?? 'minimal';
$page_id = (int) ($settings['page_id'] ?? 0);
$elementor_id = (int) ($settings['elementor_id'] ?? 0);
$mode = $settings['mode'] ?? 'coming_soon';
$pro_templates = $this->get_pro_templates();

if (!$is_pro && in_array($template_id, $pro_templates, true)) {
    $template_id = 'minimal';
}

$pages = get_pages([
    'sort_column' => 'post_title',
    'sort_order' => 'ASC',
    'post_status' => 'publish',
]);

$elementor_templates = [];
if (class_exists('\\Elementor\\Plugin')) {
    $elementor_templates = get_posts([
        'post_type' => 'elementor_library',
        'post_status' => 'publish',
        'posts_per_page' => 50,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}

$preview_url = wp_nonce_url(add_query_arg([
    'kng_maintenance_preview' => 1,
], home_url('/')), 'kng_maintenance_preview', '_kng_preview_nonce');
?>

<form method="post" action="options.php">
    <?php settings_fields('kng_maintenance_settings_group'); ?>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-layout purple"></span>
            <h2><?php esc_html_e('Page Source', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body kng-maintenance-source-grid">
            <label class="kng-maintenance-radio">
                <input type="radio" name="kng_maintenance_settings[template_source]" value="built_in" <?php checked($template_source, 'built_in'); ?>>
                <span><?php esc_html_e('Built-in Templates', 'king-addons'); ?></span>
            </label>
            <label class="kng-maintenance-radio">
                <input type="radio" name="kng_maintenance_settings[template_source]" value="page" <?php checked($template_source, 'page'); ?>>
                <span><?php esc_html_e('WordPress Page', 'king-addons'); ?></span>
            </label>
            <label class="kng-maintenance-radio <?php echo empty($elementor_templates) ? 'is-disabled' : ''; ?>">
                <input type="radio" name="kng_maintenance_settings[template_source]" value="elementor" <?php checked($template_source, 'elementor'); ?> <?php disabled(empty($elementor_templates)); ?>>
                <span><?php esc_html_e('Elementor Template', 'king-addons'); ?></span>
                <?php if (empty($elementor_templates)) : ?>
                    <small><?php esc_html_e('Elementor not detected', 'king-addons'); ?></small>
                <?php endif; ?>
            </label>
        </div>
    </div>

    <div class="ka-card kng-maintenance-section" data-section="built_in">
        <div class="ka-card-header">
            <span class="dashicons dashicons-art purple"></span>
            <h2><?php esc_html_e('Built-in Templates', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="kng-maintenance-template-grid">
                <?php foreach ($templates as $key => $label) :
                    $is_pro_template = in_array($key, $pro_templates, true);
                    $is_locked = $is_pro_template && !$is_pro;
                    ?>
                    <label class="kng-maintenance-template-card<?php echo $is_locked ? ' is-locked' : ''; ?>">
                        <input type="radio" name="kng_maintenance_settings[template_id]" value="<?php echo esc_attr($key); ?>" <?php checked($template_id, $key); ?> <?php disabled($is_locked); ?>>
                        <div class="kng-maintenance-template-preview kng-maintenance-template-<?php echo esc_attr($key); ?>">
                            <div class="kng-template-line"></div>
                            <div class="kng-template-line"></div>
                            <div class="kng-template-line"></div>
                        </div>
                        <strong><?php echo esc_html($label); ?></strong>
                        <?php if ($is_pro_template) : ?>
                            <span class="ka-pro-badge">PRO</span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="ka-card kng-maintenance-section" data-section="built_in">
        <div class="ka-card-header">
            <span class="dashicons dashicons-edit purple"></span>
            <h2><?php esc_html_e('Template Content', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <?php foreach ($templates as $key => $label) :
                $content = $this->get_template_content($key);
                ?>
                <div class="kng-maintenance-content-block" data-template="<?php echo esc_attr($key); ?>">
                    <div class="kng-maintenance-content-grid">
                        <div class="kng-field">
                            <label><?php esc_html_e('Badge text', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][badge]" value="<?php echo esc_attr($content['badge']); ?>" placeholder="<?php echo esc_attr($mode === 'maintenance' ? __('Maintenance Mode', 'king-addons') : __('Coming Soon', 'king-addons')); ?>">
                        </div>
                        <div class="kng-field">
                            <label><?php esc_html_e('Headline', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][headline]" value="<?php echo esc_attr($content['headline']); ?>">
                        </div>
                        <div class="kng-field">
                            <label><?php esc_html_e('Subhead', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][subhead]" value="<?php echo esc_attr($content['subhead']); ?>">
                        </div>
                        <div class="kng-field">
                            <label><?php esc_html_e('Launch label (optional)', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][launch_label]" value="<?php echo esc_attr($content['launch_label']); ?>" placeholder="<?php esc_attr_e('Estimated return: 2025-06-01', 'king-addons'); ?>">
                        </div>
                        <div class="kng-field">
                            <label><?php esc_html_e('Footer left', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][footer_left]" value="<?php echo esc_attr($content['footer_left']); ?>">
                        </div>
                        <div class="kng-field">
                            <label><?php esc_html_e('Footer right', 'king-addons'); ?></label>
                            <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][footer_right]" value="<?php echo esc_attr($content['footer_right']); ?>">
                        </div>
                    </div>

                    <?php if ($key === 'countdown') : ?>
                        <div class="kng-maintenance-content-grid kng-maintenance-content-grid-sm">
                            <div class="kng-field">
                                <label><?php esc_html_e('Days', 'king-addons'); ?></label>
                                <input type="number" min="0" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][countdown_days]" value="<?php echo esc_attr($content['countdown_days']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Hours', 'king-addons'); ?></label>
                                <input type="number" min="0" max="23" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][countdown_hours]" value="<?php echo esc_attr($content['countdown_hours']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Minutes', 'king-addons'); ?></label>
                                <input type="number" min="0" max="59" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][countdown_minutes]" value="<?php echo esc_attr($content['countdown_minutes']); ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($key === 'progress') : ?>
                        <div class="kng-maintenance-content-grid kng-maintenance-content-grid-sm">
                            <div class="kng-field">
                                <label><?php esc_html_e('Progress (%)', 'king-addons'); ?></label>
                                <input type="number" min="0" max="100" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][progress_percent]" value="<?php echo esc_attr($content['progress_percent']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Progress label', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][progress_label]" value="<?php echo esc_attr($content['progress_label']); ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($key, ['subscribe', 'product-launch'], true)) : ?>
                        <div class="kng-maintenance-content-grid kng-maintenance-content-grid-sm">
                            <div class="kng-field">
                                <label><?php esc_html_e('Email placeholder', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][form_placeholder]" value="<?php echo esc_attr($content['form_placeholder']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Button label', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][form_button]" value="<?php echo esc_attr($content['form_button']); ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($key === 'split') : ?>
                        <div class="kng-maintenance-content-grid">
                            <div class="kng-field">
                                <label><?php esc_html_e('Left title', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][split_title_a]" value="<?php echo esc_attr($content['split_title_a']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Left text', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][split_text_a]" value="<?php echo esc_attr($content['split_text_a']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Right title', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][split_title_b]" value="<?php echo esc_attr($content['split_title_b']); ?>">
                            </div>
                            <div class="kng-field">
                                <label><?php esc_html_e('Right text', 'king-addons'); ?></label>
                                <input type="text" name="kng_maintenance_settings[template_content][<?php echo esc_attr($key); ?>][split_text_b]" value="<?php echo esc_attr($content['split_text_b']); ?>">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ka-card kng-maintenance-section" data-section="page">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-page purple"></span>
            <h2><?php esc_html_e('Select WordPress Page', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <select name="kng_maintenance_settings[page_id]">
                <option value="0"><?php esc_html_e('Select a page', 'king-addons'); ?></option>
                <?php foreach ($pages as $page) : ?>
                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="kng-maintenance-note"><?php esc_html_e('Use an existing page as the maintenance view.', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-card kng-maintenance-section" data-section="elementor">
        <div class="ka-card-header">
            <span class="dashicons dashicons-welcome-widgets-menus purple"></span>
            <h2><?php esc_html_e('Select Elementor Template', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <select name="kng_maintenance_settings[elementor_id]" <?php disabled(empty($elementor_templates)); ?>>
                <option value="0"><?php esc_html_e('Select a template', 'king-addons'); ?></option>
                <?php foreach ($elementor_templates as $template) : ?>
                    <option value="<?php echo esc_attr($template->ID); ?>" <?php selected($elementor_id, $template->ID); ?>>
                        <?php echo esc_html($template->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="kng-maintenance-note"><?php esc_html_e('Elementor templates give you full design control.', 'king-addons'); ?></p>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-card-header">
            <span class="dashicons dashicons-admin-appearance purple"></span>
            <h2><?php esc_html_e('Appearance', 'king-addons'); ?></h2>
        </div>
        <div class="ka-card-body">
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Theme', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <select name="kng_maintenance_settings[theme]">
                        <option value="dark" <?php selected($settings['theme'] ?? 'dark', 'dark'); ?>><?php esc_html_e('Dark (Default)', 'king-addons'); ?></option>
                        <option value="light" <?php selected($settings['theme'] ?? 'dark', 'light'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                    </select>
                </div>
            </div>
            <div class="ka-row">
                <div class="ka-row-label"><?php esc_html_e('Shortcode', 'king-addons'); ?></div>
                <div class="ka-row-field">
                    <code>[kng_maintenance_page id="<?php echo esc_html($template_id); ?>"]</code>
                    <p class="kng-maintenance-note"><?php esc_html_e('Use shortcode to preview inside any page.', 'king-addons'); ?></p>
                </div>
            </div>
            <div class="kng-maintenance-actions">
                <a href="<?php echo esc_url($preview_url); ?>" target="_blank" class="ka-btn ka-btn-secondary">
                    <?php esc_html_e('Preview Page', 'king-addons'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="ka-card">
        <div class="ka-submit">
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php esc_html_e('Save Builder', 'king-addons'); ?>
            </button>
        </div>
    </div>
</form>
