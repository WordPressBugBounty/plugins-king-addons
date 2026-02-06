<?php
/**
 * Data Table Builder Extension.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

final class Data_Table_Builder
{
    private const OPTION_NAME = 'king_addons_table_builder_options';

    public const POST_TYPE = 'kng_table';
    public const META_DATA = '_kng_table_data';
    public const META_CONFIG = '_kng_table_config';
    public const META_STYLE = '_kng_table_style';
    public const META_FILTERS = '_kng_table_filters';
    public const META_RESPONSIVE = '_kng_table_responsive';
    public const META_VERSION = '_kng_table_schema_version';

    public const SCHEMA_VERSION = 1;

    private static ?Data_Table_Builder $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    public static function instance(): Data_Table_Builder
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->options = $this->get_options();

        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'maybe_migrate_tables']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('admin_post_kng_table_save', [$this, 'handle_save_table']);
        add_action('admin_post_kng_table_delete', [$this, 'handle_delete_table']);
        add_action('admin_post_kng_table_duplicate', [$this, 'handle_duplicate_table']);
        add_action('admin_post_kng_table_export', [$this, 'handle_export_table']);
        add_action('admin_post_kng_table_import', [$this, 'handle_import_table']);

        add_shortcode('kng_table', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    public function get_default_options(): array
    {
        return [
            'enabled' => true,
            'default_preset' => 'minimal',
            'default_theme' => 'dark',
            'default_rows_per_page' => 10,
        ];
    }

    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_options());
    }

    public function register_post_type(): void
    {
        $labels = [
            'name' => __('Tables', 'king-addons'),
            'singular_name' => __('Table', 'king-addons'),
            'add_new' => __('Add New', 'king-addons'),
            'add_new_item' => __('Add New Table', 'king-addons'),
            'edit_item' => __('Edit Table', 'king-addons'),
            'new_item' => __('New Table', 'king-addons'),
            'view_item' => __('View Table', 'king-addons'),
            'search_items' => __('Search Tables', 'king-addons'),
            'not_found' => __('No tables found', 'king-addons'),
            'not_found_in_trash' => __('No tables found in Trash', 'king-addons'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => ['title'],
            'has_archive' => false,
            'rewrite' => false,
        ]);
    }

    public function maybe_migrate_tables(): void
    {
        $tables = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => self::META_VERSION,
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => self::META_VERSION,
                    'value' => self::SCHEMA_VERSION,
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ],
            ],
        ]);

        foreach ($tables as $table_id) {
            update_post_meta($table_id, self::META_VERSION, self::SCHEMA_VERSION);
            $data = get_post_meta($table_id, self::META_DATA, true);
            if (is_array($data) && empty($data['schema_version'])) {
                $data['schema_version'] = self::SCHEMA_VERSION;
                update_post_meta($table_id, self::META_DATA, $data);
            }
        }
    }

    public function add_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            __('Table Builder', 'king-addons'),
            __('Table Builder', 'king-addons'),
            'manage_options',
            'king-addons-table-builder',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'king_addons_table_builder',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_options(),
            ]
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-table-builder') {
            return;
        }

        $shared_css = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_version = file_exists($shared_path) ? filemtime($shared_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-admin-v3', $shared_css, [], $shared_version);

        $admin_css = KING_ADDONS_URL . 'includes/extensions/Data_Table_Builder/assets/admin.css';
        $admin_path = KING_ADDONS_PATH . 'includes/extensions/Data_Table_Builder/assets/admin.css';
        $admin_version = file_exists($admin_path) ? filemtime($admin_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-table-builder-admin', $admin_css, ['king-addons-admin-v3'], $admin_version);

        $admin_js = KING_ADDONS_URL . 'includes/extensions/Data_Table_Builder/assets/admin.js';
        $admin_js_path = KING_ADDONS_PATH . 'includes/extensions/Data_Table_Builder/assets/admin.js';
        $admin_js_version = file_exists($admin_js_path) ? filemtime($admin_js_path) : KING_ADDONS_VERSION;
        wp_enqueue_script('king-addons-table-builder-admin', $admin_js, ['jquery'], $admin_js_version, true);

        wp_localize_script('king-addons-table-builder-admin', 'KNGTableBuilder', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'themeNonce' => wp_create_nonce('king_addons_dashboard_ui'),
            'maxRows' => 200,
            'maxCols' => 20,
            'isPro' => function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only(),
        ]);
    }

    public function enqueue_frontend_assets(): void
    {
        // Assets are loaded on demand.
    }

    private function enqueue_frontend_assets_if_needed(): void
    {
        static $enqueued = false;

        if ($enqueued) {
            return;
        }

        wp_enqueue_style(
            'king-addons-table-builder-frontend',
            KING_ADDONS_URL . 'includes/extensions/Data_Table_Builder/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-table-builder-frontend',
            KING_ADDONS_URL . 'includes/extensions/Data_Table_Builder/assets/frontend.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        $enqueued = true;
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
        $is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
        $settings = $this->get_options();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function handle_save_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_table_save');

        $table_id = isset($_POST['table_id']) ? absint($_POST['table_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';

        $post_args = [
            'post_title' => $title !== '' ? $title : __('Untitled Table', 'king-addons'),
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ];

        if ($table_id > 0) {
            $post_args['ID'] = $table_id;
        }

        $result = wp_insert_post($post_args, true);
        if (is_wp_error($result)) {
            $this->redirect_with_message('add', 'error_save', $table_id);
        }

        $table_id = (int) $result;

        $data = $this->sanitize_table_data(isset($_POST['kng_table_data']) ? wp_unslash($_POST['kng_table_data']) : '');
        $config = $this->sanitize_table_config(isset($_POST['kng_table_config']) ? wp_unslash($_POST['kng_table_config']) : '');
        $style = $this->sanitize_table_style(isset($_POST['kng_table_style']) ? wp_unslash($_POST['kng_table_style']) : '');
        $filters = $this->sanitize_table_filters(isset($_POST['kng_table_filters']) ? wp_unslash($_POST['kng_table_filters']) : '');
        $responsive = $this->sanitize_table_responsive(isset($_POST['kng_table_responsive']) ? wp_unslash($_POST['kng_table_responsive']) : '');

        update_post_meta($table_id, self::META_DATA, $data);
        update_post_meta($table_id, self::META_CONFIG, $config);
        update_post_meta($table_id, self::META_STYLE, $style);
        update_post_meta($table_id, self::META_FILTERS, $filters);
        update_post_meta($table_id, self::META_RESPONSIVE, $responsive);
        update_post_meta($table_id, self::META_VERSION, self::SCHEMA_VERSION);

        $this->redirect_with_message('add', 'saved', $table_id);
    }

    public function handle_delete_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $table_id = isset($_GET['table_id']) ? absint($_GET['table_id']) : 0;
        check_admin_referer('kng_table_delete_' . $table_id);

        if ($table_id > 0) {
            wp_delete_post($table_id, true);
        }

        $this->redirect_with_message('tables', 'deleted');
    }

    public function handle_duplicate_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $table_id = isset($_GET['table_id']) ? absint($_GET['table_id']) : 0;
        check_admin_referer('kng_table_duplicate_' . $table_id);

        $table = get_post($table_id);
        if (!$table || $table->post_type !== self::POST_TYPE) {
            $this->redirect_with_message('tables', 'error_not_found');
        }

        $new_id = wp_insert_post([
            'post_title' => $table->post_title . ' (Copy)',
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($new_id)) {
            $this->redirect_with_message('tables', 'error_save');
        }

        update_post_meta($new_id, self::META_DATA, get_post_meta($table_id, self::META_DATA, true));
        update_post_meta($new_id, self::META_CONFIG, get_post_meta($table_id, self::META_CONFIG, true));
        update_post_meta($new_id, self::META_STYLE, get_post_meta($table_id, self::META_STYLE, true));
        update_post_meta($new_id, self::META_FILTERS, get_post_meta($table_id, self::META_FILTERS, true));
        update_post_meta($new_id, self::META_RESPONSIVE, get_post_meta($table_id, self::META_RESPONSIVE, true));
        update_post_meta($new_id, self::META_VERSION, self::SCHEMA_VERSION);

        $this->redirect_with_message('tables', 'duplicated');
    }

    public function handle_export_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $table_id = isset($_GET['table_id']) ? absint($_GET['table_id']) : 0;
        check_admin_referer('kng_table_export_' . $table_id);

        $table = get_post($table_id);
        if (!$table || $table->post_type !== self::POST_TYPE) {
            $this->redirect_with_message('tables', 'error_not_found');
        }

        $data = get_post_meta($table_id, self::META_DATA, true);
        if (!is_array($data)) {
            $data = [];
        }

        $rows = $data['rows'] ?? [];
        $columns = $data['columns'] ?? [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=table-' . $table_id . '-' . gmdate('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        $header = [];
        foreach ($columns as $column) {
            $header[] = $this->escape_csv_value($column['label'] ?? '');
        }
        if (!empty($header)) {
            fputcsv($output, $header);
        }

        foreach ($rows as $row) {
            $line = [];
            foreach ($row as $cell) {
                $value = is_array($cell) ? (string) ($cell['value'] ?? '') : (string) $cell;
                $line[] = $this->escape_csv_value($value);
            }
            fputcsv($output, $line);
        }

        fclose($output);
        exit;
    }

    public function handle_import_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_table_import');

        if (empty($_FILES['import_file']) || !isset($_FILES['import_file']['tmp_name'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $file = $_FILES['import_file'];
        if (!empty($file['error'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $header = array_shift($rows);
        $columns = [];
        $is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
        $max_cols = $is_pro ? count($header) : min(20, count($header));
        foreach (array_slice($header, 0, $max_cols) as $label) {
            $columns[] = [
                'label' => sanitize_text_field((string) $label),
                'type' => 'text',
                'sortable' => true,
                'hide_mobile' => false,
                'align' => 'left',
            ];
        }

        $data_rows = [];
        $rows = $is_pro ? $rows : array_slice($rows, 0, 200);
        foreach ($rows as $row) {
            $cells = [];
            foreach (array_slice($row, 0, $max_cols) as $cell) {
                $cells[] = [
                    'value' => sanitize_text_field((string) $cell),
                    'tooltip' => '',
                    'rowspan' => 1,
                    'colspan' => 1,
                ];
            }
            $data_rows[] = $cells;
        }

        $data = [
            'columns' => $columns,
            'rows' => $data_rows,
            'schema_version' => self::SCHEMA_VERSION,
        ];

        $table_id = wp_insert_post([
            'post_title' => __('Imported Table', 'king-addons'),
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($table_id)) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        update_post_meta($table_id, self::META_DATA, $data);
        update_post_meta($table_id, self::META_CONFIG, $this->get_default_table_config());
        update_post_meta($table_id, self::META_STYLE, $this->get_default_table_style());
        update_post_meta($table_id, self::META_FILTERS, []);
        update_post_meta($table_id, self::META_RESPONSIVE, $this->get_default_responsive());
        update_post_meta($table_id, self::META_VERSION, self::SCHEMA_VERSION);

        $this->redirect_with_message('import-export', 'imported');
    }

    public function render_shortcode($atts): string
    {
        $atts = shortcode_atts([
            'id' => '',
            'slug' => '',
            'theme' => '',
            'search' => '',
            'pagination' => '',
            'sort' => '',
            'class' => '',
        ], $atts, 'kng_table');

        $table = null;
        if (!empty($atts['id'])) {
            $table = get_post(absint($atts['id']));
        } elseif (!empty($atts['slug'])) {
            $query = new \WP_Query([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'name' => sanitize_title_with_dashes($atts['slug']),
                'posts_per_page' => 1,
            ]);
            if (!empty($query->posts)) {
                $table = $query->posts[0];
            }
        }

        if (!$table || $table->post_type !== self::POST_TYPE) {
            return '';
        }

        $data = get_post_meta($table->ID, self::META_DATA, true);
        $config = get_post_meta($table->ID, self::META_CONFIG, true);
        $style = get_post_meta($table->ID, self::META_STYLE, true);
        $responsive = get_post_meta($table->ID, self::META_RESPONSIVE, true);

        if (!is_array($data)) {
            $data = [];
        }
        if (!is_array($config)) {
            $config = $this->get_default_table_config();
        }
        if (!is_array($style)) {
            $style = $this->get_default_table_style();
        }
        if (!is_array($responsive)) {
            $responsive = $this->get_default_responsive();
        }

        $config = $this->apply_shortcode_overrides($config, $atts);
        if (!empty($atts['theme'])) {
            $style['theme'] = sanitize_key($atts['theme']);
        }

        $this->enqueue_frontend_assets_if_needed();

        return $this->render_table_markup($table, $data, $config, $style, $responsive, $atts['class']);
    }

    private function render_table_markup($table, array $data, array $config, array $style, array $responsive, string $extra_class): string
    {
        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];

        if (empty($columns)) {
            $columns = [];
            $max_cols = 0;
            foreach ($rows as $row) {
                $max_cols = max($max_cols, count($row));
            }
            for ($i = 0; $i < $max_cols; $i++) {
                $columns[] = [
                    'label' => sprintf(__('Column %d', 'king-addons'), $i + 1),
                    'type' => 'text',
                    'sortable' => true,
                    'hide_mobile' => false,
                    'align' => 'left',
                ];
            }
        }

        $table_id = 'kng-table-' . $table->ID;
        $preset = sanitize_key($style['preset'] ?? $this->options['default_preset']);
        $theme = sanitize_key($style['theme'] ?? $this->options['default_theme']);
        $scroll_x = !empty($responsive['scroll_x']);

        $classes = [
            'kng-table-builder',
            'kng-table-preset-' . $preset,
            'kng-table-theme-' . ($theme === 'light' ? 'light' : 'dark'),
            $scroll_x ? 'kng-table-scroll-x' : 'kng-table-no-scroll',
        ];
        $extra_class = trim($extra_class);
        if ($extra_class !== '') {
            foreach (preg_split('/\\s+/', $extra_class) as $class_name) {
                $classes[] = sanitize_html_class($class_name);
            }
        }

        $config_data = [
            'search' => !empty($config['search']),
            'sorting' => !empty($config['sorting']),
            'pagination' => !empty($config['pagination']),
            'rowsPerPage' => (int) ($config['rows_per_page'] ?? 10),
            'showToolbar' => true,
        ];

        $hide_cols = isset($responsive['hide_columns']) && is_array($responsive['hide_columns']) ? $responsive['hide_columns'] : [];

        ob_start();
        ?>
        <div id="<?php echo esc_attr($table_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-config="<?php echo esc_attr(wp_json_encode($config_data)); ?>">
            <div class="kng-table-toolbar">
                <div class="kng-table-title">
                    <span><?php echo esc_html($table->post_title); ?></span>
                </div>
                <?php if (!empty($config['search'])) : ?>
                    <div class="kng-table-search">
                        <input type="search" placeholder="<?php esc_attr_e('Search table...', 'king-addons'); ?>" aria-label="<?php esc_attr_e('Search table', 'king-addons'); ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div class="kng-table-wrapper">
                <table class="kng-table<?php echo !empty($style['zebra']) ? ' zebra' : ''; ?>" role="table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $index => $column) :
                                $label = $column['label'] ?? '';
                                $type = $column['type'] ?? 'text';
                                $align = $column['align'] ?? 'left';
                                ?>
                                <th data-col="<?php echo esc_attr($index); ?>" data-type="<?php echo esc_attr($type); ?>" class="kng-table-align-<?php echo esc_attr($align); ?>">
                                    <span><?php echo esc_html($label); ?></span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $skip_map = [];
                        foreach ($rows as $row_index => $row) :
                            $col_index = 0;
                            ?>
                            <tr>
                                <?php
                                while ($col_index < count($columns)) :
                                    if (!empty($skip_map[$col_index])) {
                                        $skip_map[$col_index]--;
                                        $col_index++;
                                        continue;
                                    }

                                    $cell = $row[$col_index] ?? [];
                                    $cell_data = is_array($cell) ? $cell : ['value' => (string) $cell];
                                    $value = $cell_data['value'] ?? '';
                                    $tooltip = $cell_data['tooltip'] ?? '';
                                    $rowspan = max(1, (int) ($cell_data['rowspan'] ?? 1));
                                    $colspan = max(1, (int) ($cell_data['colspan'] ?? 1));

                                    if ($rowspan > 1) {
                                        for ($span = 0; $span < $colspan; $span++) {
                                            $skip_map[$col_index + $span] = max((int) ($skip_map[$col_index + $span] ?? 0), $rowspan - 1);
                                        }
                                    }
                                    ?>
                                    <td class="kng-table-align-<?php echo esc_attr($columns[$col_index]['align'] ?? 'left'); ?>"<?php if ($tooltip !== '') : ?> data-tooltip="<?php echo esc_attr($tooltip); ?>"<?php endif; ?><?php if ($rowspan > 1) : ?> rowspan="<?php echo esc_attr($rowspan); ?>"<?php endif; ?><?php if ($colspan > 1) : ?> colspan="<?php echo esc_attr($colspan); ?>"<?php endif; ?>>
                                        <?php echo esc_html($value); ?>
                                    </td>
                                    <?php
                                    $col_index += $colspan;
                                endwhile;
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($config['pagination'])) : ?>
                <div class="kng-table-pagination" aria-label="<?php esc_attr_e('Table pagination', 'king-addons'); ?>"></div>
            <?php endif; ?>
        </div>
        <?php

        if (!empty($hide_cols)) :
            $hide_cols = array_map('absint', $hide_cols);
            $hide_cols = array_filter($hide_cols, function ($value) {
                return $value >= 0;
            });
            $hide_cols = array_values(array_unique($hide_cols));
            if (!empty($hide_cols)) :
                ?>
                <style>
                    @media (max-width: 768px) {
                        <?php foreach ($hide_cols as $col_index) :
                            $nth = $col_index + 1;
                            ?>
                            #<?php echo esc_attr($table_id); ?> th:nth-child(<?php echo esc_attr($nth); ?>),
                            #<?php echo esc_attr($table_id); ?> td:nth-child(<?php echo esc_attr($nth); ?>) {
                                display: none;
                            }
                        <?php endforeach; ?>
                    }
                </style>
                <?php
            endif;
        endif;

        return ob_get_clean();
    }

    private function get_default_table_config(): array
    {
        return [
            'search' => true,
            'sorting' => true,
            'pagination' => true,
            'rows_per_page' => (int) $this->options['default_rows_per_page'],
        ];
    }

    private function get_default_table_style(): array
    {
        return [
            'preset' => $this->options['default_preset'],
            'theme' => $this->options['default_theme'],
            'zebra' => true,
        ];
    }

    private function get_default_responsive(): array
    {
        return [
            'scroll_x' => true,
            'hide_columns' => [],
        ];
    }

    private function sanitize_table_data(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [
                'columns' => [],
                'rows' => [],
                'schema_version' => self::SCHEMA_VERSION,
            ];
        }

        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];

        $columns = is_array($columns) ? $columns : [];
        $rows = is_array($rows) ? $rows : [];

        $is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

        $max_cols = $is_pro ? count($columns) : min(20, count($columns));
        $columns = $is_pro ? $columns : array_slice($columns, 0, $max_cols);

        $sanitized_columns = [];
        foreach ($columns as $column) {
            $sanitized_columns[] = [
                'label' => sanitize_text_field($column['label'] ?? ''),
                'type' => in_array($column['type'] ?? 'text', ['text', 'number', 'link'], true) ? $column['type'] : 'text',
                'sortable' => !empty($column['sortable']),
                'hide_mobile' => !empty($column['hide_mobile']),
                'align' => in_array($column['align'] ?? 'left', ['left', 'center', 'right'], true) ? $column['align'] : 'left',
            ];
        }

        $sanitized_rows = [];
        $rows = $is_pro ? $rows : array_slice($rows, 0, 200);
        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];
            $row = array_slice($row, 0, $max_cols);
            $cells = [];
            foreach ($row as $cell) {
                $value = is_array($cell) ? ($cell['value'] ?? '') : $cell;
                $cells[] = [
                    'value' => sanitize_text_field((string) $value),
                    'tooltip' => sanitize_text_field((string) ($cell['tooltip'] ?? '')),
                    'rowspan' => max(1, min(10, absint($cell['rowspan'] ?? 1))),
                    'colspan' => max(1, min(10, absint($cell['colspan'] ?? 1))),
                ];
            }

            while (count($cells) < $max_cols) {
                $cells[] = [
                    'value' => '',
                    'tooltip' => '',
                    'rowspan' => 1,
                    'colspan' => 1,
                ];
            }

            $sanitized_rows[] = $cells;
        }

        return [
            'columns' => $sanitized_columns,
            'rows' => $sanitized_rows,
            'schema_version' => self::SCHEMA_VERSION,
        ];
    }

    private function sanitize_table_config(string $json): array
    {
        $config = json_decode($json, true);
        if (!is_array($config)) {
            $config = [];
        }

        return [
            'search' => !empty($config['search']),
            'sorting' => !empty($config['sorting']),
            'pagination' => !empty($config['pagination']),
            'rows_per_page' => max(5, min(100, absint($config['rows_per_page'] ?? $this->options['default_rows_per_page']))),
        ];
    }

    private function sanitize_table_style(string $json): array
    {
        $style = json_decode($json, true);
        if (!is_array($style)) {
            $style = [];
        }

        $preset = sanitize_key($style['preset'] ?? $this->options['default_preset']);
        $theme = sanitize_key($style['theme'] ?? $this->options['default_theme']);

        return [
            'preset' => $preset,
            'theme' => $theme === 'light' ? 'light' : 'dark',
            'zebra' => !empty($style['zebra']),
        ];
    }

    public function sanitize_settings(array $settings): array
    {
        $defaults = $this->get_default_options();

        $preset = sanitize_key($settings['default_preset'] ?? $defaults['default_preset']);
        $theme = sanitize_key($settings['default_theme'] ?? $defaults['default_theme']);

        return [
            'enabled' => !empty($settings['enabled']),
            'default_preset' => $preset !== '' ? $preset : $defaults['default_preset'],
            'default_theme' => $theme === 'light' ? 'light' : 'dark',
            'default_rows_per_page' => max(5, min(100, absint($settings['default_rows_per_page'] ?? $defaults['default_rows_per_page']))),
        ];
    }

    private function sanitize_table_filters(string $json): array
    {
        $filters = json_decode($json, true);
        return is_array($filters) ? $filters : [];
    }

    private function sanitize_table_responsive(string $json): array
    {
        $responsive = json_decode($json, true);
        if (!is_array($responsive)) {
            $responsive = [];
        }

        $hide_columns = $responsive['hide_columns'] ?? [];
        if (!is_array($hide_columns)) {
            $hide_columns = [];
        }

        $hide_columns = array_values(array_unique(array_map('absint', $hide_columns)));

        return [
            'scroll_x' => !empty($responsive['scroll_x']),
            'hide_columns' => array_values($hide_columns),
        ];
    }

    private function apply_shortcode_overrides(array $config, array $atts): array
    {
        if ($atts['search'] !== '') {
            $config['search'] = filter_var($atts['search'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($atts['pagination'] !== '') {
            $config['pagination'] = filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($atts['sort'] !== '') {
            $config['sorting'] = filter_var($atts['sort'], FILTER_VALIDATE_BOOLEAN);
        }

        return $config;
    }

    private function escape_csv_value(string $value): string
    {
        if (preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    private function redirect_with_message(string $view, string $message, int $table_id = 0): void
    {
        $args = [
            'page' => 'king-addons-table-builder',
            'view' => $view,
            'message' => $message,
        ];

        if ($table_id > 0) {
            $args['table_id'] = $table_id;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
