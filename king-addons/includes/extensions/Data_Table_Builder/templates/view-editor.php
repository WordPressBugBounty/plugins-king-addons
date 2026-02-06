<?php
/**
 * Data Table Builder editor view.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$table_id = isset($_GET['table_id']) ? absint($_GET['table_id']) : 0;
$table = $table_id ? get_post($table_id) : null;
$is_edit = $table && $table->post_type === \King_Addons\Data_Table_Builder::POST_TYPE;

$data = $is_edit ? get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_DATA, true) : [];
$config = $is_edit ? get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_CONFIG, true) : [];
$style = $is_edit ? get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_STYLE, true) : [];
$filters = $is_edit ? get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_FILTERS, true) : [];
$responsive = $is_edit ? get_post_meta($table->ID, \King_Addons\Data_Table_Builder::META_RESPONSIVE, true) : [];

if (!is_array($data)) {
    $data = [];
}

if (!is_array($filters)) {
    $filters = [];
}

if (empty($data['columns'])) {
    $data['columns'] = [
        ['label' => __('Column 1', 'king-addons'), 'type' => 'text', 'sortable' => true, 'hide_mobile' => false, 'align' => 'left'],
        ['label' => __('Column 2', 'king-addons'), 'type' => 'text', 'sortable' => true, 'hide_mobile' => false, 'align' => 'left'],
        ['label' => __('Column 3', 'king-addons'), 'type' => 'text', 'sortable' => true, 'hide_mobile' => false, 'align' => 'left'],
    ];
}

if (empty($data['rows'])) {
    $rows = [];
    for ($i = 0; $i < 5; $i++) {
        $row = [];
        foreach ($data['columns'] as $column) {
            $row[] = [
                'value' => '',
                'tooltip' => '',
                'rowspan' => 1,
                'colspan' => 1,
            ];
        }
        $rows[] = $row;
    }
    $data['rows'] = $rows;
}

$default_config = [
    'search' => true,
    'sorting' => true,
    'pagination' => true,
    'rows_per_page' => (int) ($settings['default_rows_per_page'] ?? 10),
];

$default_style = [
    'preset' => $settings['default_preset'] ?? 'minimal',
    'theme' => $settings['default_theme'] ?? 'dark',
    'zebra' => true,
];

$default_responsive = [
    'scroll_x' => true,
    'hide_columns' => [],
];

$config = wp_parse_args(is_array($config) ? $config : [], $default_config);
$style = wp_parse_args(is_array($style) ? $style : [], $default_style);
$responsive = wp_parse_args(is_array($responsive) ? $responsive : [], $default_responsive);

$state = [
    'columns' => $data['columns'],
    'rows' => $data['rows'],
    'config' => $config,
    'style' => $style,
    'responsive' => $responsive,
    'filters' => $filters,
];

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

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="kng-table-form">
    <input type="hidden" name="action" value="kng_table_save">
    <input type="hidden" name="table_id" value="<?php echo esc_attr($table_id); ?>">
    <?php wp_nonce_field('kng_table_save'); ?>

    <input type="hidden" name="kng_table_data" id="kng-table-data" value="">
    <input type="hidden" name="kng_table_config" id="kng-table-config" value="">
    <input type="hidden" name="kng_table_style" id="kng-table-style" value="">
    <input type="hidden" name="kng_table_filters" id="kng-table-filters" value="<?php echo esc_attr(wp_json_encode($filters)); ?>">
    <input type="hidden" name="kng_table_responsive" id="kng-table-responsive" value="">

    <div class="kng-table-editor-actions">
        <div class="kng-table-editor-actions-left">
            <input type="text" name="title" value="<?php echo esc_attr($is_edit ? $table->post_title : ''); ?>" placeholder="<?php esc_attr_e('Table name', 'king-addons'); ?>" class="kng-table-title-input">
        </div>
        <div class="kng-table-editor-actions-right">
            <?php if ($is_edit) : ?>
                <a href="<?php echo esc_url(add_query_arg(['page' => 'king-addons-table-builder', 'view' => 'tables'], admin_url('admin.php'))); ?>" class="ka-btn ka-btn-secondary ka-btn-sm">
                    <?php esc_html_e('Back to list', 'king-addons'); ?>
                </a>
            <?php endif; ?>
            <button type="submit" class="ka-btn ka-btn-primary">
                <?php echo $is_edit ? esc_html__('Save Changes', 'king-addons') : esc_html__('Create Table', 'king-addons'); ?>
            </button>
        </div>
    </div>

    <div class="kng-table-editor" data-state="<?php echo esc_attr(wp_json_encode($state)); ?>">
        <aside class="kng-table-sidebar">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-list-view purple"></span>
                    <h2><?php esc_html_e('Structure', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="kng-column-list"></div>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-add-column">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Add Column', 'king-addons'); ?>
                    </button>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-column">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Remove Column', 'king-addons'); ?>
                    </button>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-editor-table purple"></span>
                    <h2><?php esc_html_e('Rows', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-add-row">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Add Row', 'king-addons'); ?>
                    </button>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-remove-row">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Remove Row', 'king-addons'); ?>
                    </button>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-search purple"></span>
                    <h2><?php esc_html_e('Features', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <label class="ka-toggle">
                        <input type="checkbox" data-config="search" <?php checked(!empty($config['search'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Search', 'king-addons'); ?></span>
                    </label>
                    <label class="ka-toggle">
                        <input type="checkbox" data-config="sorting" <?php checked(!empty($config['sorting'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Sorting', 'king-addons'); ?></span>
                    </label>
                    <label class="ka-toggle">
                        <input type="checkbox" data-config="pagination" <?php checked(!empty($config['pagination'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Pagination', 'king-addons'); ?></span>
                    </label>
                    <div class="kng-field">
                        <label><?php esc_html_e('Rows per page', 'king-addons'); ?></label>
                        <input type="number" min="5" max="100" data-config="rows_per_page" value="<?php echo esc_attr($config['rows_per_page']); ?>">
                    </div>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-art purple"></span>
                    <h2><?php esc_html_e('Style', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="kng-field">
                        <label><?php esc_html_e('Preset', 'king-addons'); ?></label>
                        <select data-style="preset">
                            <?php foreach ($presets as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($style['preset'], $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="kng-field">
                        <label><?php esc_html_e('Theme', 'king-addons'); ?></label>
                        <select data-style="theme">
                            <option value="dark" <?php selected($style['theme'], 'dark'); ?>><?php esc_html_e('Dark', 'king-addons'); ?></option>
                            <option value="light" <?php selected($style['theme'], 'light'); ?>><?php esc_html_e('Light', 'king-addons'); ?></option>
                        </select>
                    </div>
                    <label class="ka-toggle">
                        <input type="checkbox" data-style="zebra" <?php checked(!empty($style['zebra'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Zebra rows', 'king-addons'); ?></span>
                    </label>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-smartphone purple"></span>
                    <h2><?php esc_html_e('Responsive', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <label class="ka-toggle">
                        <input type="checkbox" data-responsive="scroll_x" <?php checked(!empty($responsive['scroll_x'])); ?> />
                        <span class="ka-toggle-slider"></span>
                        <span class="ka-toggle-label"><?php esc_html_e('Horizontal scroll on mobile', 'king-addons'); ?></span>
                    </label>
                    <p class="ka-row-desc"><?php esc_html_e('Hide columns on mobile in the Structure panel.', 'king-addons'); ?></p>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-lock pink"></span>
                    <h2><?php esc_html_e('Advanced', 'king-addons'); ?></h2>
                    <?php if (!$is_pro) : ?><span class="ka-pro-badge">PRO</span><?php endif; ?>
                </div>
                <div class="ka-card-body">
                    <p><?php esc_html_e('Conditional formatting, formulas, and advanced filters are available in Pro.', 'king-addons'); ?></p>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" disabled>
                        <?php esc_html_e('Unlock Pro Features', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </aside>

        <section class="kng-table-main">
            <div class="kng-table-grid-wrap">
                <table class="kng-table-grid" id="kng-table-grid">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="kng-table-import">
                <div class="kng-table-import-header">
                    <h3><?php esc_html_e('Paste CSV / TSV', 'king-addons'); ?></h3>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-paste-clipboard">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e('Paste from Clipboard', 'king-addons'); ?>
                    </button>
                </div>
                <textarea class="kng-table-import-text" rows="4" placeholder="Name,Price,Stock"></textarea>
                <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-apply-import">
                    <?php esc_html_e('Apply Data', 'king-addons'); ?>
                </button>
            </div>
        </section>

        <aside class="kng-table-inspector">
            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-feedback purple"></span>
                    <h2><?php esc_html_e('Cell Inspector', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <div class="kng-field">
                        <label><?php esc_html_e('Tooltip', 'king-addons'); ?></label>
                        <input type="text" class="kng-cell-tooltip" placeholder="<?php esc_attr_e('Tooltip text', 'king-addons'); ?>">
                    </div>
                    <div class="kng-field">
                        <label><?php esc_html_e('Row span', 'king-addons'); ?></label>
                        <input type="number" min="1" max="10" class="kng-cell-rowspan" value="1">
                    </div>
                    <div class="kng-field">
                        <label><?php esc_html_e('Col span', 'king-addons'); ?></label>
                        <input type="number" min="1" max="10" class="kng-cell-colspan" value="1">
                    </div>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm kng-apply-span">
                        <?php esc_html_e('Apply Span', 'king-addons'); ?>
                    </button>
                </div>
            </div>

            <div class="ka-card">
                <div class="ka-card-header">
                    <span class="dashicons dashicons-admin-tools purple"></span>
                    <h2><?php esc_html_e('Quick Actions', 'king-addons'); ?></h2>
                </div>
                <div class="ka-card-body">
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" disabled>
                        <?php esc_html_e('Merge cells (Pro)', 'king-addons'); ?>
                    </button>
                    <button type="button" class="ka-btn ka-btn-secondary ka-btn-sm" disabled>
                        <?php esc_html_e('Formula helper (Pro)', 'king-addons'); ?>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</form>
