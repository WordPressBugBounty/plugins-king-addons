<?php
/**
 * Site Preloader Rules Tab - Redesigned for better UX.
 *
 * @package King_Addons
 * @since 1.0.0
 *
 * @var array $settings Current settings.
 * @var bool  $is_pro   Whether Pro version is active.
 * @var array $presets  Available presets.
 * @var array $rules    Current display rules.
 */

if (!defined('ABSPATH')) {
    exit;
}

$max_free_rules = 3;
$can_add_rule = $is_pro || count($rules) < $max_free_rules;

// Get all pages for dropdown
$all_pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_title']);

// Get all post types
$post_types = get_post_types(['public' => true], 'objects');
unset($post_types['attachment']);

// Condition labels  
$condition_labels = [
    'specific_pages' => __('Specific pages', 'king-addons'),
    'post_type' => __('Post type', 'king-addons'),
    'front_page' => __('Front page (Home)', 'king-addons'),
    'blog_page' => __('Blog page', 'king-addons'),
    'all_posts' => __('All posts', 'king-addons'),
    'all_pages' => __('All pages', 'king-addons'),
    'archive' => __('Archive pages', 'king-addons'),
    'search' => __('Search results', 'king-addons'),
    '404' => __('404 page', 'king-addons'),
    'url_contains' => __('URL contains', 'king-addons'),
    'url_equals' => __('URL equals', 'king-addons'),
];
?>

<div class="ka-card">
    <div class="ka-card-header">
        <span class="dashicons dashicons-filter"></span>
        <h2><?php esc_html_e('Display Rules', 'king-addons'); ?></h2>
        <div class="ka-card-header-actions">
            <?php if ($can_add_rule): ?>
            <button type="button" class="ka-btn ka-btn-primary ka-btn-sm" id="ka-add-rule-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Rule', 'king-addons'); ?>
            </button>
            <?php else: ?>
            <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" disabled>
                <span class="dashicons dashicons-lock"></span>
                <?php esc_html_e('Upgrade for more rules', 'king-addons'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="ka-card-body">
        <p class="ka-preloader-rules-intro">
            <?php esc_html_e('Control where the preloader appears. Rules are checked in order — first matching rule wins.', 'king-addons'); ?>
        </p>

        <div class="ka-preloader-rules-list" id="ka-rules-list">
            <?php if (empty($rules)): ?>
            <div class="ka-empty" id="ka-rules-empty">
                <span class="dashicons dashicons-filter"></span>
                <p><?php esc_html_e('No display rules yet.', 'king-addons'); ?></p>
                <p class="ka-empty-hint"><?php esc_html_e('By default, preloader shows on all pages. Add rules to customize this behavior.', 'king-addons'); ?></p>
            </div>
            <?php else: ?>
                <?php foreach ($rules as $index => $rule): 
                    $action = $rule['action'] ?? 'show';
                    $condition = $rule['condition'] ?? 'specific_pages';
                    $condition_value = $rule['condition_value'] ?? '';
                    $template = $rule['template'] ?? '';
                    $selected_pages = $rule['pages'] ?? [];
                    
                    // Build readable description
                    $desc_condition = $condition_labels[$condition] ?? $condition;
                    
                    // For pages, show page titles
                    $desc_value = $condition_value;
                    if ($condition === 'specific_pages' && !empty($selected_pages)) {
                        $page_titles = [];
                        foreach ($selected_pages as $page_id) {
                            $page = get_post($page_id);
                            if ($page) {
                                $page_titles[] = $page->post_title;
                            }
                        }
                        $desc_value = implode(', ', array_slice($page_titles, 0, 3));
                        if (count($page_titles) > 3) {
                            $desc_value .= ' +' . (count($page_titles) - 3) . ' more';
                        }
                    }
                ?>
                <div class="ka-preloader-rule-item <?php echo empty($rule['enabled']) ? 'ka-rule-disabled' : ''; ?>" data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                    <div class="ka-preloader-rule-item__header">
                        <label class="ka-toggle ka-toggle-sm">
                            <input type="checkbox" class="ka-rule-enabled" <?php checked(!empty($rule['enabled'])); ?> />
                            <span class="ka-toggle-slider"></span>
                        </label>
                        <div class="ka-preloader-rule-item__summary">
                            <span class="ka-rule-action ka-rule-action--<?php echo esc_attr($action); ?>">
                                <?php 
                                if ($action === 'show') {
                                    echo '<span class="dashicons dashicons-visibility"></span> ' . esc_html__('Show', 'king-addons');
                                } elseif ($action === 'hide') {
                                    echo '<span class="dashicons dashicons-hidden"></span> ' . esc_html__('Hide', 'king-addons');
                                } else {
                                    echo '<span class="dashicons dashicons-admin-customizer"></span> ' . esc_html__('Override', 'king-addons');
                                }
                                ?>
                            </span>
                            <span class="ka-rule-on"><?php esc_html_e('on', 'king-addons'); ?></span>
                            <span class="ka-rule-condition"><?php echo esc_html($desc_condition); ?></span>
                            <?php if ($desc_value): ?>
                            <span class="ka-rule-value"><?php echo esc_html($desc_value); ?></span>
                            <?php endif; ?>
                            <?php if ($action === 'override' && $template): ?>
                            <span class="ka-rule-template-badge">→ <?php echo esc_html($presets[$template]['title'] ?? $template); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ka-preloader-rule-item__actions">
                            <button type="button" class="ka-btn-icon ka-rule-edit-btn" title="<?php esc_attr_e('Edit', 'king-addons'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="ka-btn-icon ka-rule-delete-btn" title="<?php esc_attr_e('Delete', 'king-addons'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="ka-preloader-rule-item__details" style="display: none;">
                        <div class="ka-rule-form">
                            <!-- Action -->
                            <div class="ka-rule-form-row">
                                <label class="ka-rule-form-label"><?php esc_html_e('Action', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <div class="ka-btn-group ka-rule-action-btns">
                                        <button type="button" class="ka-btn ka-btn-sm <?php echo $action === 'show' ? 'active' : ''; ?>" data-action="show">
                                            <span class="dashicons dashicons-visibility"></span>
                                            <?php esc_html_e('Show', 'king-addons'); ?>
                                        </button>
                                        <button type="button" class="ka-btn ka-btn-sm <?php echo $action === 'hide' ? 'active' : ''; ?>" data-action="hide">
                                            <span class="dashicons dashicons-hidden"></span>
                                            <?php esc_html_e('Hide', 'king-addons'); ?>
                                        </button>
                                        <button type="button" class="ka-btn ka-btn-sm <?php echo $action === 'override' ? 'active' : ''; ?>" data-action="override">
                                            <span class="dashicons dashicons-admin-customizer"></span>
                                            <?php esc_html_e('Override', 'king-addons'); ?>
                                        </button>
                                    </div>
                                    <input type="hidden" class="ka-rule-action" value="<?php echo esc_attr($action); ?>" />
                                </div>
                            </div>
                            
                            <!-- Template (only for override action) -->
                            <div class="ka-rule-form-row ka-rule-template-row" style="<?php echo $action !== 'override' ? 'display:none;' : ''; ?>">
                                <label class="ka-rule-form-label"><?php esc_html_e('Template', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <select class="ka-rule-template">
                                        <?php foreach ($presets as $preset_id => $preset): 
                                            if (!empty($preset['pro']) && !$is_pro) continue;
                                        ?>
                                        <option value="<?php echo esc_attr($preset_id); ?>" <?php selected($template, $preset_id); ?>>
                                            <?php echo esc_html($preset['title']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Condition -->
                            <div class="ka-rule-form-row">
                                <label class="ka-rule-form-label"><?php esc_html_e('Where', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <select class="ka-rule-condition">
                                        <optgroup label="<?php esc_attr_e('Pages', 'king-addons'); ?>">
                                            <option value="specific_pages" <?php selected($condition, 'specific_pages'); ?>><?php esc_html_e('Specific pages', 'king-addons'); ?></option>
                                            <option value="all_pages" <?php selected($condition, 'all_pages'); ?>><?php esc_html_e('All pages', 'king-addons'); ?></option>
                                            <option value="front_page" <?php selected($condition, 'front_page'); ?>><?php esc_html_e('Front page (Home)', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php esc_attr_e('Posts', 'king-addons'); ?>">
                                            <option value="all_posts" <?php selected($condition, 'all_posts'); ?>><?php esc_html_e('All posts', 'king-addons'); ?></option>
                                            <option value="blog_page" <?php selected($condition, 'blog_page'); ?>><?php esc_html_e('Blog page', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php esc_attr_e('Other', 'king-addons'); ?>">
                                            <option value="post_type" <?php selected($condition, 'post_type'); ?>><?php esc_html_e('Custom post type', 'king-addons'); ?></option>
                                            <option value="archive" <?php selected($condition, 'archive'); ?>><?php esc_html_e('Archive pages', 'king-addons'); ?></option>
                                            <option value="search" <?php selected($condition, 'search'); ?>><?php esc_html_e('Search results', 'king-addons'); ?></option>
                                            <option value="404" <?php selected($condition, '404'); ?>><?php esc_html_e('404 page', 'king-addons'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php esc_attr_e('URL', 'king-addons'); ?>">
                                            <option value="url_contains" <?php selected($condition, 'url_contains'); ?>><?php esc_html_e('URL contains', 'king-addons'); ?></option>
                                            <option value="url_equals" <?php selected($condition, 'url_equals'); ?>><?php esc_html_e('URL equals', 'king-addons'); ?></option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Specific Pages selector -->
                            <div class="ka-rule-form-row ka-rule-pages-row" style="<?php echo $condition !== 'specific_pages' ? 'display:none;' : ''; ?>">
                                <label class="ka-rule-form-label"><?php esc_html_e('Select Pages', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <select class="ka-rule-pages" multiple="multiple">
                                        <?php foreach ($all_pages as $page): ?>
                                        <option value="<?php echo esc_attr($page->ID); ?>" <?php echo in_array($page->ID, $selected_pages) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($page->post_title); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="ka-rule-form-hint"><?php esc_html_e('Hold Ctrl/Cmd to select multiple pages', 'king-addons'); ?></p>
                                </div>
                            </div>
                            
                            <!-- Post Type selector -->
                            <div class="ka-rule-form-row ka-rule-posttype-row" style="<?php echo $condition !== 'post_type' ? 'display:none;' : ''; ?>">
                                <label class="ka-rule-form-label"><?php esc_html_e('Post Type', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <select class="ka-rule-posttype">
                                        <?php foreach ($post_types as $pt): ?>
                                        <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($condition_value, $pt->name); ?>>
                                            <?php echo esc_html($pt->labels->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- URL Value input -->
                            <div class="ka-rule-form-row ka-rule-url-row" style="<?php echo !in_array($condition, ['url_contains', 'url_equals']) ? 'display:none;' : ''; ?>">
                                <label class="ka-rule-form-label"><?php esc_html_e('URL Value', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <input type="text" class="ka-rule-url-value" value="<?php echo esc_attr($condition_value); ?>" placeholder="<?php esc_attr_e('e.g., /shop/ or checkout', 'king-addons'); ?>" />
                                </div>
                            </div>
                            
                            <!-- Override colors (only for override action) -->
                            <div class="ka-rule-form-row ka-rule-colors-toggle-row" style="<?php echo $action !== 'override' ? 'display:none;' : ''; ?>">
                                <label class="ka-rule-form-label"><?php esc_html_e('Override Colors', 'king-addons'); ?></label>
                                <div class="ka-rule-form-field">
                                    <label class="ka-toggle">
                                        <input type="checkbox" class="ka-rule-override-colors" <?php checked(!empty($rule['override_colors'])); ?> />
                                        <span class="ka-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ka-rule-form-row ka-rule-colors-row" style="<?php echo (empty($rule['override_colors']) || $action !== 'override') ? 'display:none;' : ''; ?>">
                                <div class="ka-rule-colors-grid">
                                    <div class="ka-rule-color-field">
                                        <label><?php esc_html_e('Background', 'king-addons'); ?></label>
                                        <input type="text" class="ka-color-picker ka-rule-bg-color" value="<?php echo esc_attr($rule['bg_color'] ?? '#ffffff'); ?>" data-default-color="#ffffff" data-alpha-enabled="true" />
                                    </div>
                                    <div class="ka-rule-color-field">
                                        <label><?php esc_html_e('Accent', 'king-addons'); ?></label>
                                        <input type="text" class="ka-color-picker ka-rule-accent-color" value="<?php echo esc_attr($rule['accent_color'] ?? '#0071e3'); ?>" data-default-color="#0071e3" data-alpha-enabled="true" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="ka-rule-form-actions">
                                <button type="button" class="ka-btn ka-btn-secondary ka-rule-cancel-btn"><?php esc_html_e('Cancel', 'king-addons'); ?></button>
                                <button type="button" class="ka-btn ka-btn-primary ka-rule-save-btn"><?php esc_html_e('Save Rule', 'king-addons'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- /.ka-preloader-rules-list -->
    </div>
</div>

<!-- Rule Template -->
<template id="ka-rule-template">
    <div class="ka-preloader-rule-item" data-rule-id="">
        <div class="ka-preloader-rule-item__header">
            <label class="ka-toggle ka-toggle-sm">
                <input type="checkbox" class="ka-rule-enabled" checked />
                <span class="ka-toggle-slider"></span>
            </label>
            <div class="ka-preloader-rule-item__summary">
                <span class="ka-rule-action ka-rule-action--show">
                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Show', 'king-addons'); ?>
                </span>
                <span class="ka-rule-on"><?php esc_html_e('on', 'king-addons'); ?></span>
                <span class="ka-rule-condition"><?php esc_html_e('Specific pages', 'king-addons'); ?></span>
                <span class="ka-rule-value"></span>
            </div>
            <div class="ka-preloader-rule-item__actions">
                <button type="button" class="ka-btn-icon ka-rule-edit-btn" title="<?php esc_attr_e('Edit', 'king-addons'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button type="button" class="ka-btn-icon ka-rule-delete-btn" title="<?php esc_attr_e('Delete', 'king-addons'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <div class="ka-preloader-rule-item__details">
            <div class="ka-rule-form">
                <div class="ka-rule-form-row">
                    <label class="ka-rule-form-label"><?php esc_html_e('Action', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <div class="ka-btn-group ka-rule-action-btns">
                            <button type="button" class="ka-btn ka-btn-sm active" data-action="show">
                                <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Show', 'king-addons'); ?>
                            </button>
                            <button type="button" class="ka-btn ka-btn-sm" data-action="hide">
                                <span class="dashicons dashicons-hidden"></span> <?php esc_html_e('Hide', 'king-addons'); ?>
                            </button>
                            <button type="button" class="ka-btn ka-btn-sm" data-action="override">
                                <span class="dashicons dashicons-admin-customizer"></span> <?php esc_html_e('Override', 'king-addons'); ?>
                            </button>
                        </div>
                        <input type="hidden" class="ka-rule-action" value="show" />
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-template-row" style="display:none;">
                    <label class="ka-rule-form-label"><?php esc_html_e('Template', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <select class="ka-rule-template">
                            <?php foreach ($presets as $preset_id => $preset): if (!empty($preset['pro']) && !$is_pro) continue; ?>
                            <option value="<?php echo esc_attr($preset_id); ?>"><?php echo esc_html($preset['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ka-rule-form-row">
                    <label class="ka-rule-form-label"><?php esc_html_e('Where', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <select class="ka-rule-condition">
                            <optgroup label="<?php esc_attr_e('Pages', 'king-addons'); ?>">
                                <option value="specific_pages"><?php esc_html_e('Specific pages', 'king-addons'); ?></option>
                                <option value="all_pages"><?php esc_html_e('All pages', 'king-addons'); ?></option>
                                <option value="front_page"><?php esc_html_e('Front page (Home)', 'king-addons'); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e('Posts', 'king-addons'); ?>">
                                <option value="all_posts"><?php esc_html_e('All posts', 'king-addons'); ?></option>
                                <option value="blog_page"><?php esc_html_e('Blog page', 'king-addons'); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e('Other', 'king-addons'); ?>">
                                <option value="post_type"><?php esc_html_e('Custom post type', 'king-addons'); ?></option>
                                <option value="archive"><?php esc_html_e('Archive pages', 'king-addons'); ?></option>
                                <option value="search"><?php esc_html_e('Search results', 'king-addons'); ?></option>
                                <option value="404"><?php esc_html_e('404 page', 'king-addons'); ?></option>
                            </optgroup>
                            <optgroup label="<?php esc_attr_e('URL', 'king-addons'); ?>">
                                <option value="url_contains"><?php esc_html_e('URL contains', 'king-addons'); ?></option>
                                <option value="url_equals"><?php esc_html_e('URL equals', 'king-addons'); ?></option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-pages-row">
                    <label class="ka-rule-form-label"><?php esc_html_e('Select Pages', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <select class="ka-rule-pages" multiple="multiple">
                            <?php foreach ($all_pages as $page): ?>
                            <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="ka-rule-form-hint"><?php esc_html_e('Hold Ctrl/Cmd to select multiple', 'king-addons'); ?></p>
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-posttype-row" style="display:none;">
                    <label class="ka-rule-form-label"><?php esc_html_e('Post Type', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <select class="ka-rule-posttype">
                            <?php foreach ($post_types as $pt): ?>
                            <option value="<?php echo esc_attr($pt->name); ?>"><?php echo esc_html($pt->labels->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-url-row" style="display:none;">
                    <label class="ka-rule-form-label"><?php esc_html_e('URL Value', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <input type="text" class="ka-rule-url-value" placeholder="<?php esc_attr_e('e.g., /shop/', 'king-addons'); ?>" />
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-colors-toggle-row" style="display:none;">
                    <label class="ka-rule-form-label"><?php esc_html_e('Override Colors', 'king-addons'); ?></label>
                    <div class="ka-rule-form-field">
                        <label class="ka-toggle"><input type="checkbox" class="ka-rule-override-colors" /><span class="ka-toggle-slider"></span></label>
                    </div>
                </div>
                <div class="ka-rule-form-row ka-rule-colors-row" style="display:none;">
                    <div class="ka-rule-colors-grid">
                        <div class="ka-rule-color-field"><label><?php esc_html_e('Background', 'king-addons'); ?></label><input type="text" class="ka-color-picker ka-rule-bg-color" value="#ffffff" data-default-color="#ffffff" data-alpha-enabled="true" /></div>
                        <div class="ka-rule-color-field"><label><?php esc_html_e('Accent', 'king-addons'); ?></label><input type="text" class="ka-color-picker ka-rule-accent-color" value="#0071e3" data-default-color="#0071e3" data-alpha-enabled="true" /></div>
                    </div>
                </div>
                <div class="ka-rule-form-actions">
                    <button type="button" class="ka-btn ka-btn-secondary ka-rule-cancel-btn"><?php esc_html_e('Cancel', 'king-addons'); ?></button>
                    <button type="button" class="ka-btn ka-btn-primary ka-rule-save-btn"><?php esc_html_e('Save Rule', 'king-addons'); ?></button>
                </div>
            </div>
        </div>
    </div>
</template>

<?php if (!$is_pro && count($rules) >= $max_free_rules): ?>
<div class="ka-upgrade-card">
    <h3><?php esc_html_e('Need More Rules?', 'king-addons'); ?></h3>
    <p><?php esc_html_e('Upgrade to Pro for unlimited display rules', 'king-addons'); ?></p>
    <a href="https://kingaddons.com/pricing/" target="_blank" class="ka-btn ka-btn-pink">
        <span class="dashicons dashicons-star-filled"></span>
        <?php esc_html_e('Upgrade to Pro', 'king-addons'); ?>
    </a>
</div>
<?php endif; ?>
