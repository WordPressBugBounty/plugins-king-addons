<?php
/**
 * Pricing Table Builder Extension.
 *
 * Modern pricing table builder with billing toggle, presets, and Elementor integration.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Pricing Table Builder class.
 */
final class Pricing_Table_Builder
{
    /**
     * Option name for settings.
     */
    private const OPTION_NAME = 'king_addons_pricing_table_options';

    /**
     * Post type name.
     */
    public const POST_TYPE = 'kng_pricing_table';

    /**
     * Meta key for config.
     */
    public const META_CONFIG = '_kng_pt_config';

    /**
     * Meta key for version.
     */
    public const META_VERSION = '_kng_pt_version';

    /**
     * Current schema version.
     */
    public const SCHEMA_VERSION = 1;

    /**
     * REST API namespace.
     */
    public const API_NAMESPACE = 'king-addons/v1';

    /**
     * Singleton instance.
     *
     * @var Pricing_Table_Builder|null
     */
    private static ?Pricing_Table_Builder $instance = null;

    /**
     * Cached options.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Gets singleton instance.
     *
     * @return Pricing_Table_Builder
     */
    public static function instance(): Pricing_Table_Builder
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = $this->get_options();

        // Init
        add_action('init', [$this, 'register_post_type']);

        // Admin
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_king_addons_pt_save', [$this, 'handle_save_table']);
        add_action('admin_post_king_addons_pt_delete', [$this, 'handle_delete_table']);
        add_action('admin_post_king_addons_pt_duplicate', [$this, 'handle_duplicate_table']);

        // Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_shortcode('kng_pricing_table', [$this, 'render_shortcode']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX
        add_action('wp_ajax_kng_pt_preview', [$this, 'ajax_preview']);
        add_action('wp_ajax_kng_pt_get_tables', [$this, 'ajax_get_tables']);
    }

    /**
     * Gets default options.
     *
     * @return array<string, mixed>
     */
    public function get_default_options(): array
    {
        return [
            'enabled' => true,
            'default_preset' => 'free_modern_cards',
            'default_currency' => '$',
            'default_currency_position' => 'before',
        ];
    }

    /**
     * Gets current options.
     *
     * @return array<string, mixed>
     */
    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_options());
    }

    /**
     * Registers the pricing table CPT.
     *
     * @return void
     */
    public function register_post_type(): void
    {
        $labels = [
            'name' => __('Pricing Tables', 'king-addons'),
            'singular_name' => __('Pricing Table', 'king-addons'),
            'add_new' => __('Add New', 'king-addons'),
            'add_new_item' => __('Add New Pricing Table', 'king-addons'),
            'edit_item' => __('Edit Pricing Table', 'king-addons'),
            'new_item' => __('New Pricing Table', 'king-addons'),
            'view_item' => __('View Pricing Table', 'king-addons'),
            'search_items' => __('Search Pricing Tables', 'king-addons'),
            'not_found' => __('No pricing tables found', 'king-addons'),
            'not_found_in_trash' => __('No pricing tables found in Trash', 'king-addons'),
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

    /**
     * Adds admin menu.
     *
     * @return void
     */
    public function add_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            __('Pricing Tables', 'king-addons'),
            __('Pricing Tables', 'king-addons'),
            'manage_options',
            'king-addons-pricing-tables',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueues admin assets.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Check for our pricing tables page
        if ($hook !== 'king-addons_page_king-addons-pricing-tables') {
            return;
        }

        // Core admin styles
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Extension specific styles (includes all V3 base styles)
        wp_enqueue_style(
            'king-addons-pt-admin',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/admin.css',
            [],
            KING_ADDONS_VERSION
        );

        // Frontend styles for preview
        wp_enqueue_style(
            'king-addons-pt-frontend',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-pt-admin',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/admin.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-sortable'],
            KING_ADDONS_VERSION,
            true
        );

        // Frontend JS for preview toggle functionality
        wp_enqueue_script(
            'king-addons-pt-frontend',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/frontend.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-pt-admin', 'kngPTAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(self::API_NAMESPACE . '/pricing-table/'),
            'nonce' => wp_create_nonce('kng_pt_admin'),
            'presets' => $this->get_presets(),
            'i18n' => [
                'confirmDelete' => __('Are you sure you want to delete this pricing table?', 'king-addons'),
                'confirmDeletePlan' => __('Are you sure you want to delete this plan?', 'king-addons'),
                'planName' => __('Plan', 'king-addons'),
                'featureName' => __('Feature', 'king-addons'),
                'copied' => __('Copied!', 'king-addons'),
                'saving' => __('Saving...', 'king-addons'),
                'saved' => __('Saved!', 'king-addons'),
            ],
        ]);
    }

    /**
     * Enqueues frontend assets.
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void
    {
        // Only enqueue if needed (shortcode or widget used)
        // Assets will be enqueued in render methods
    }

    /**
     * Enqueues frontend assets when needed.
     *
     * @return void
     */
    private function enqueue_frontend_assets_if_needed(): void
    {
        static $enqueued = false;

        if ($enqueued) {
            return;
        }

        wp_enqueue_style(
            'king-addons-pt-frontend',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-pt-frontend',
            KING_ADDONS_URL . 'includes/extensions/Pricing_Table_Builder/assets/frontend.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        $enqueued = true;
    }

    /**
     * Renders admin page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $table_id = isset($_GET['table_id']) ? intval($_GET['table_id']) : 0;

        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_editor_page($table_id);
                break;
            default:
                $this->render_list_page();
                break;
        }
    }

    /**
     * Renders list page.
     *
     * @return void
     */
    private function render_list_page(): void
    {
        $tables = get_posts([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft'],
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        include __DIR__ . '/templates/admin-list.php';
    }

    /**
     * Renders editor page.
     *
     * @param int $table_id Table ID.
     * @return void
     */
    private function render_editor_page(int $table_id): void
    {
        $table = null;
        $config = $this->get_default_table_config();

        if ($table_id > 0) {
            $table = get_post($table_id);
            if ($table && $table->post_type === self::POST_TYPE) {
                $saved_config = get_post_meta($table_id, self::META_CONFIG, true);
                if (!empty($saved_config)) {
                    $config = wp_parse_args(json_decode($saved_config, true), $config);
                }
            }
        }

        $presets = $this->get_presets();
        $is_premium = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

        include __DIR__ . '/templates/admin-editor.php';
    }

    /**
     * Gets default table config.
     *
     * @return array<string, mixed>
     */
    public function get_default_table_config(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'table' => [
                'name' => '',
                'status' => 'draft',
                'layout' => [
                    'mode' => 'cards',
                    'columns_desktop' => 3,
                    'columns_tablet' => 2,
                    'columns_mobile' => 1,
                    'card_equal_height' => true,
                    'gap' => 24,
                    'max_width' => 1200,
                    'alignment' => 'center',
                ],
            ],
            'billing' => [
                'enabled' => true,
                'type' => 'segmented',
                'periods' => [
                    [
                        'key' => 'monthly',
                        'label' => 'Monthly',
                        'suffix' => '/mo',
                        'is_default' => false,
                    ],
                    [
                        'key' => 'annual',
                        'label' => 'Annual',
                        'suffix' => '/yr',
                        'is_default' => true,
                        'badge' => [
                            'enabled' => true,
                            'text' => 'Save 16%',
                        ],
                    ],
                ],
                'currency' => '$',
                'currency_position' => 'before',
                'decimals' => 0,
            ],
            'plans' => [
                $this->get_default_plan('basic', 'Basic', 'For individuals', 29, 290, 1),
                $this->get_default_plan('pro', 'Pro', 'For teams', 79, 790, 2, true, 'Popular'),
                $this->get_default_plan('enterprise', 'Enterprise', 'For organizations', 199, 1990, 3),
            ],
            'features' => [
                'mode' => 'per_plan',
                'show_icons' => true,
            ],
            'style' => [
                'preset_id' => 'free_modern_cards',
                'tokens' => [],
                'overrides' => [
                    'custom_css_class' => '',
                ],
            ],
            'advanced' => [
                'hide_toggle' => false,
                'force_period' => '',
                'disable_animations' => false,
            ],
        ];
    }

    /**
     * Gets default plan config.
     *
     * @param string $id Plan ID.
     * @param string $name Plan name.
     * @param string $subtitle Plan subtitle.
     * @param int $monthly Monthly price.
     * @param int $annual Annual price.
     * @param int $order Order.
     * @param bool $highlight Highlight.
     * @param string $badge Badge text.
     * @return array<string, mixed>
     */
    private function get_default_plan(
        string $id,
        string $name,
        string $subtitle,
        int $monthly,
        int $annual,
        int $order,
        bool $highlight = false,
        string $badge = ''
    ): array {
        return [
            'id' => 'plan_' . $id,
            'order' => $order,
            'name' => $name,
            'subtitle' => $subtitle,
            'description' => '',
            'badge' => [
                'enabled' => !empty($badge),
                'text' => $badge,
                'style' => 'pill',
            ],
            'highlight' => [
                'enabled' => $highlight,
                'style' => 'border',
            ],
            'pricing' => [
                'monthly' => [
                    'price' => (string)$monthly,
                    'note' => 'billed monthly',
                ],
                'annual' => [
                    'price' => (string)$annual,
                    'note' => 'billed annually',
                ],
            ],
            'cta' => [
                'enabled' => true,
                'text' => 'Get Started',
                'url' => '#',
                'target' => '_self',
                'style' => $highlight ? 'primary' : 'secondary',
            ],
            'features' => [
                ['text' => 'Feature 1', 'state' => 'enabled'],
                ['text' => 'Feature 2', 'state' => 'enabled'],
                ['text' => 'Feature 3', 'state' => $highlight ? 'enabled' : 'disabled'],
            ],
        ];
    }

    /**
     * Handles save table action.
     *
     * @return void
     */
    public function handle_save_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'king-addons'));
        }

        check_admin_referer('kng_pt_save', 'kng_pt_nonce');

        $table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
        $config_json = isset($_POST['config']) ? wp_unslash($_POST['config']) : '';

        if (empty($config_json)) {
            wp_die(__('Invalid config', 'king-addons'));
        }

        $config = json_decode($config_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON config', 'king-addons'));
        }

        $table_name = sanitize_text_field($config['table']['name'] ?? 'Untitled');
        $table_status = ($config['table']['status'] ?? 'draft') === 'publish' ? 'publish' : 'draft';

        $post_data = [
            'post_title' => $table_name,
            'post_status' => $table_status,
            'post_type' => self::POST_TYPE,
        ];

        if ($table_id > 0) {
            $post_data['ID'] = $table_id;
            wp_update_post($post_data);
        } else {
            $table_id = wp_insert_post($post_data);
        }

        if (is_wp_error($table_id)) {
            wp_die($table_id->get_error_message());
        }

        update_post_meta($table_id, self::META_CONFIG, wp_json_encode($config));
        update_post_meta($table_id, self::META_VERSION, self::SCHEMA_VERSION);

        wp_safe_redirect(admin_url('admin.php?page=king-addons-pricing-tables&action=edit&table_id=' . $table_id . '&saved=1'));
        exit;
    }

    /**
     * Handles delete table action.
     *
     * @return void
     */
    public function handle_delete_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'king-addons'));
        }

        $table_id = isset($_GET['table_id']) ? intval($_GET['table_id']) : 0;
        check_admin_referer('kng_pt_delete_' . $table_id);

        if ($table_id > 0) {
            wp_delete_post($table_id, true);
        }

        wp_safe_redirect(admin_url('admin.php?page=king-addons-pricing-tables&deleted=1'));
        exit;
    }

    /**
     * Handles duplicate table action.
     *
     * @return void
     */
    public function handle_duplicate_table(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'king-addons'));
        }

        $table_id = isset($_GET['table_id']) ? intval($_GET['table_id']) : 0;
        check_admin_referer('kng_pt_duplicate_' . $table_id);

        if ($table_id > 0) {
            $original = get_post($table_id);
            if ($original && $original->post_type === self::POST_TYPE) {
                $new_id = wp_insert_post([
                    'post_title' => sprintf(__('Copy of %s', 'king-addons'), $original->post_title),
                    'post_status' => 'draft',
                    'post_type' => self::POST_TYPE,
                ]);

                if (!is_wp_error($new_id)) {
                    $config = get_post_meta($table_id, self::META_CONFIG, true);
                    update_post_meta($new_id, self::META_CONFIG, $config);
                    update_post_meta($new_id, self::META_VERSION, self::SCHEMA_VERSION);

                    wp_safe_redirect(admin_url('admin.php?page=king-addons-pricing-tables&action=edit&table_id=' . $new_id));
                    exit;
                }
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=king-addons-pricing-tables'));
        exit;
    }

    /**
     * Registers REST routes.
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        register_rest_route(self::API_NAMESPACE, '/pricing-table/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_preview'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route(self::API_NAMESPACE, '/pricing-table/list', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_tables'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::API_NAMESPACE, '/pricing-table/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_table'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST preview endpoint.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_preview(\WP_REST_Request $request): \WP_REST_Response
    {
        $config = $request->get_json_params();
        $html = $this->render_table($config, true);

        return new \WP_REST_Response([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * REST get tables endpoint.
     *
     * @return \WP_REST_Response
     */
    public function rest_get_tables(): \WP_REST_Response
    {
        $tables = get_posts([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $result = [];
        foreach ($tables as $table) {
            $result[] = [
                'id' => $table->ID,
                'title' => $table->post_title,
            ];
        }

        return new \WP_REST_Response($result);
    }

    /**
     * REST get single table endpoint.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function rest_get_table(\WP_REST_Request $request): \WP_REST_Response
    {
        $table_id = (int)$request->get_param('id');
        $table = get_post($table_id);

        if (!$table || $table->post_type !== self::POST_TYPE) {
            return new \WP_REST_Response(['error' => 'Not found'], 404);
        }

        $config = get_post_meta($table_id, self::META_CONFIG, true);

        return new \WP_REST_Response([
            'id' => $table_id,
            'title' => $table->post_title,
            'config' => json_decode($config, true),
        ]);
    }

    /**
     * AJAX preview handler.
     *
     * @return void
     */
    public function ajax_preview(): void
    {
        check_ajax_referer('kng_pt_admin', 'nonce');

        $config_json = isset($_POST['config']) ? wp_unslash($_POST['config']) : '';
        $config = json_decode($config_json, true);

        if (!$config) {
            wp_send_json_error('Invalid config');
        }

        $html = $this->render_table($config, true);
        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX get tables handler.
     *
     * @return void
     */
    public function ajax_get_tables(): void
    {
        $tables = get_posts([
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $result = [];
        foreach ($tables as $table) {
            $result[] = [
                'id' => $table->ID,
                'title' => $table->post_title,
            ];
        }

        wp_send_json_success($result);
    }

    /**
     * Renders shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'id' => 0,
            'period' => '',
            'hide_toggle' => '',
        ], $atts, 'kng_pricing_table');

        $table_id = intval($atts['id']);
        if ($table_id <= 0) {
            return '';
        }

        $table = get_post($table_id);
        if (!$table || $table->post_type !== self::POST_TYPE || $table->post_status !== 'publish') {
            return '';
        }

        $config_json = get_post_meta($table_id, self::META_CONFIG, true);
        $config = json_decode($config_json, true);

        if (!$config) {
            return '';
        }

        // Apply shortcode overrides
        if (!empty($atts['period'])) {
            $config['advanced']['force_period'] = sanitize_text_field($atts['period']);
        }
        if ($atts['hide_toggle'] === '1') {
            $config['advanced']['hide_toggle'] = true;
        }

        $this->enqueue_frontend_assets_if_needed();

        return $this->render_table($config);
    }

    /**
     * Renders pricing table HTML.
     *
     * @param array $config Table config.
     * @param bool $is_preview Whether this is a preview render.
     * @return string
     */
    public function render_table(array $config, bool $is_preview = false): string
    {
        $preset = $this->get_preset($config['style']['preset_id'] ?? 'free_modern_cards');
        $tokens = array_merge($preset['tokens'] ?? [], $config['style']['tokens'] ?? []);

        // Generate CSS variables
        $css_vars = $this->generate_css_variables($tokens);

        // Get billing settings
        $billing = $config['billing'] ?? [];
        $billing_enabled = !empty($billing['enabled']) && empty($config['advanced']['hide_toggle']);
        $periods = $billing['periods'] ?? [];
        $default_period = '';
        foreach ($periods as $period) {
            if (!empty($period['is_default'])) {
                $default_period = $period['key'];
                break;
            }
        }
        if (empty($default_period) && !empty($periods)) {
            $default_period = $periods[0]['key'];
        }

        // Force period override
        if (!empty($config['advanced']['force_period'])) {
            $default_period = $config['advanced']['force_period'];
        }

        // Layout settings
        $layout = $config['table']['layout'] ?? [];
        $columns_desktop = $layout['columns_desktop'] ?? 3;
        $columns_tablet = $layout['columns_tablet'] ?? 2;
        $columns_mobile = $layout['columns_mobile'] ?? 1;
        $gap = $layout['gap'] ?? 24;
        $max_width = $layout['max_width'] ?? 1200;
        $alignment = $layout['alignment'] ?? 'center';

        // Plans
        $plans = $config['plans'] ?? [];
        usort($plans, function ($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // Currency
        $currency = $billing['currency'] ?? '$';
        $currency_position = $billing['currency_position'] ?? 'before';

        ob_start();
        ?>
        <div class="kng-pt-wrapper kng-pt-preset-<?php echo esc_attr($config['style']['preset_id'] ?? 'default'); ?> <?php echo esc_attr($config['style']['overrides']['custom_css_class'] ?? ''); ?>"
             style="<?php echo esc_attr($css_vars); ?> --kng-pt-columns-desktop: <?php echo esc_attr($columns_desktop); ?>; --kng-pt-columns-tablet: <?php echo esc_attr($columns_tablet); ?>; --kng-pt-columns-mobile: <?php echo esc_attr($columns_mobile); ?>; --kng-pt-gap: <?php echo esc_attr($gap); ?>px; --kng-pt-max-width: <?php echo esc_attr($max_width); ?>px;"
             data-period="<?php echo esc_attr($default_period); ?>"
             data-billing="<?php echo $billing_enabled ? '1' : '0'; ?>">
            
            <div class="kng-pt-container" style="max-width: var(--kng-pt-max-width); margin: 0 auto; text-align: <?php echo esc_attr($alignment); ?>;">
                
                <?php if ($billing_enabled && count($periods) > 1): ?>
                <!-- Billing Toggle -->
                <div class="kng-pt-toggle-wrapper">
                    <div class="kng-pt-toggle" role="radiogroup" aria-label="<?php esc_attr_e('Billing period', 'king-addons'); ?>">
                        <?php foreach ($periods as $index => $period): ?>
                        <button type="button" 
                                class="kng-pt-toggle-btn <?php echo $period['key'] === $default_period ? 'is-active' : ''; ?>"
                                role="radio"
                                aria-checked="<?php echo $period['key'] === $default_period ? 'true' : 'false'; ?>"
                                data-period="<?php echo esc_attr($period['key']); ?>">
                            <?php echo esc_html($period['label']); ?>
                            <?php if (!empty($period['badge']['enabled']) && !empty($period['badge']['text'])): ?>
                            <span class="kng-pt-toggle-badge"><?php echo esc_html($period['badge']['text']); ?></span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Plans Grid -->
                <div class="kng-pt-grid">
                    <?php foreach ($plans as $plan): ?>
                    <?php echo $this->render_plan_card($plan, $config, $default_period); ?>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders a single plan card.
     *
     * @param array $plan Plan config.
     * @param array $config Full table config.
     * @param string $active_period Active period key.
     * @return string
     */
    private function render_plan_card(array $plan, array $config, string $active_period): string
    {
        $billing = $config['billing'] ?? [];
        $currency = $billing['currency'] ?? '$';
        $currency_position = $billing['currency_position'] ?? 'before';
        $billing_enabled = !empty($billing['enabled']);
        $periods = $billing['periods'] ?? [];

        $is_highlighted = !empty($plan['highlight']['enabled']);
        $badge_enabled = !empty($plan['badge']['enabled']);
        $cta = $plan['cta'] ?? [];
        $features = $plan['features'] ?? [];

        ob_start();
        ?>
        <div class="kng-pt-card <?php echo $is_highlighted ? 'kng-pt-card--highlighted' : ''; ?>">
            
            <?php if ($badge_enabled && !empty($plan['badge']['text'])): ?>
            <div class="kng-pt-card-badge kng-pt-badge--<?php echo esc_attr($plan['badge']['style'] ?? 'pill'); ?>">
                <?php echo esc_html($plan['badge']['text']); ?>
            </div>
            <?php endif; ?>

            <div class="kng-pt-card-header">
                <h3 class="kng-pt-card-name"><?php echo esc_html($plan['name']); ?></h3>
                <?php if (!empty($plan['subtitle'])): ?>
                <p class="kng-pt-card-subtitle"><?php echo esc_html($plan['subtitle']); ?></p>
                <?php endif; ?>
            </div>

            <div class="kng-pt-card-pricing">
                <?php if ($billing_enabled): ?>
                    <?php foreach ($periods as $period): 
                        $period_key = $period['key'];
                        $price_data = $plan['pricing'][$period_key] ?? [];
                        $price = $price_data['price'] ?? '0';
                        $note = $price_data['note'] ?? '';
                        $suffix = $period['suffix'] ?? '';
                    ?>
                    <div class="kng-pt-price-group" data-period="<?php echo esc_attr($period_key); ?>" <?php echo $period_key !== $active_period ? 'style="display: none;"' : ''; ?>>
                        <div class="kng-pt-price">
                            <?php if ($currency_position === 'before'): ?>
                            <span class="kng-pt-price-currency"><?php echo esc_html($currency); ?></span>
                            <?php endif; ?>
                            <span class="kng-pt-price-value"><?php echo esc_html($price); ?></span>
                            <?php if ($currency_position === 'after'): ?>
                            <span class="kng-pt-price-currency"><?php echo esc_html($currency); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($suffix)): ?>
                            <span class="kng-pt-price-suffix"><?php echo esc_html($suffix); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($note)): ?>
                        <div class="kng-pt-price-note"><?php echo esc_html($note); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php 
                    $price_data = $plan['pricing']['default'] ?? $plan['pricing']['monthly'] ?? [];
                    $price = $price_data['price'] ?? '0';
                    $note = $price_data['note'] ?? '';
                    ?>
                    <div class="kng-pt-price-group">
                        <div class="kng-pt-price">
                            <?php if ($currency_position === 'before'): ?>
                            <span class="kng-pt-price-currency"><?php echo esc_html($currency); ?></span>
                            <?php endif; ?>
                            <span class="kng-pt-price-value"><?php echo esc_html($price); ?></span>
                            <?php if ($currency_position === 'after'): ?>
                            <span class="kng-pt-price-currency"><?php echo esc_html($currency); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($note)): ?>
                        <div class="kng-pt-price-note"><?php echo esc_html($note); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($cta['enabled'])): ?>
            <div class="kng-pt-card-cta">
                <a href="<?php echo esc_url($cta['url'] ?? '#'); ?>" 
                   class="kng-pt-btn kng-pt-btn--<?php echo esc_attr($cta['style'] ?? 'primary'); ?>"
                   <?php echo ($cta['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo esc_html($cta['text'] ?? __('Get Started', 'king-addons')); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if (!empty($features) && !empty($config['features']['show_icons'])): ?>
            <div class="kng-pt-card-features">
                <ul class="kng-pt-features-list">
                    <?php foreach ($features as $feature): ?>
                    <li class="kng-pt-feature kng-pt-feature--<?php echo esc_attr($feature['state'] ?? 'enabled'); ?>">
                        <span class="kng-pt-feature-icon">
                            <?php if (($feature['state'] ?? 'enabled') === 'enabled'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <?php endif; ?>
                        </span>
                        <span class="kng-pt-feature-text"><?php echo esc_html($feature['text']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($plan['fine_print'])): ?>
            <div class="kng-pt-card-fine-print">
                <?php echo esc_html($plan['fine_print']); ?>
            </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Generates CSS variables from tokens.
     *
     * @param array $tokens Style tokens.
     * @return string
     */
    private function generate_css_variables(array $tokens): string
    {
        $vars = [];

        // Colors
        $colors = $tokens['colors'] ?? [];
        if (!empty($colors['bg'])) $vars[] = '--kng-pt-bg:' . $colors['bg'];
        if (!empty($colors['card_bg'])) $vars[] = '--kng-pt-card-bg:' . $colors['card_bg'];
        if (!empty($colors['card_border'])) $vars[] = '--kng-pt-card-border:' . $colors['card_border'];
        if (!empty($colors['text'])) $vars[] = '--kng-pt-text:' . $colors['text'];
        if (!empty($colors['muted'])) $vars[] = '--kng-pt-muted:' . $colors['muted'];
        if (!empty($colors['accent'])) $vars[] = '--kng-pt-accent:' . $colors['accent'];
        if (!empty($colors['accent_text'])) $vars[] = '--kng-pt-accent-text:' . $colors['accent_text'];
        if (!empty($colors['badge_bg'])) $vars[] = '--kng-pt-badge-bg:' . $colors['badge_bg'];
        if (!empty($colors['badge_text'])) $vars[] = '--kng-pt-badge-text:' . $colors['badge_text'];
        if (!empty($colors['highlight'])) $vars[] = '--kng-pt-highlight:' . $colors['highlight'];

        // Typography
        $typo = $tokens['typography'] ?? [];
        if (!empty($typo['title_size'])) $vars[] = '--kng-pt-title-size:' . $typo['title_size'] . 'px';
        if (!empty($typo['price_size'])) $vars[] = '--kng-pt-price-size:' . $typo['price_size'] . 'px';
        if (!empty($typo['body_size'])) $vars[] = '--kng-pt-body-size:' . $typo['body_size'] . 'px';

        // Layout
        if (!empty($tokens['radius'])) $vars[] = '--kng-pt-radius:' . $tokens['radius'] . 'px';
        if (!empty($tokens['border_width'])) $vars[] = '--kng-pt-border-width:' . $tokens['border_width'] . 'px';

        // Shadow
        $shadow_map = [
            'none' => 'none',
            'sm' => '0 2px 8px rgba(0,0,0,0.06)',
            'md' => '0 4px 20px rgba(0,0,0,0.08)',
            'lg' => '0 8px 40px rgba(0,0,0,0.12)',
        ];
        if (!empty($tokens['shadow']) && isset($shadow_map[$tokens['shadow']])) {
            $vars[] = '--kng-pt-shadow:' . $shadow_map[$tokens['shadow']];
        }

        return implode(';', $vars);
    }

    /**
     * Gets all presets.
     *
     * @return array<string, array>
     */
    public function get_presets(): array
    {
        return include __DIR__ . '/presets/presets.php';
    }

    /**
     * Gets a single preset by ID.
     *
     * @param string $preset_id Preset ID.
     * @return array
     */
    public function get_preset(string $preset_id): array
    {
        $presets = $this->get_presets();
        return $presets[$preset_id] ?? $presets['free_modern_cards'] ?? [];
    }

    /**
     * Renders table for Elementor widget.
     *
     * @param int $table_id Table ID.
     * @param array $overrides Override settings.
     * @return string
     */
    public function render_for_elementor(int $table_id, array $overrides = []): string
    {
        if ($table_id <= 0) {
            return '<p style="text-align:center;color:#999;">' . __('Please select a pricing table', 'king-addons') . '</p>';
        }

        $table = get_post($table_id);
        if (!$table || $table->post_type !== self::POST_TYPE) {
            return '<p style="text-align:center;color:#999;">' . __('Pricing table not found', 'king-addons') . '</p>';
        }

        $config_json = get_post_meta($table_id, self::META_CONFIG, true);
        $config = json_decode($config_json, true);

        if (!$config) {
            return '<p style="text-align:center;color:#999;">' . __('Invalid pricing table config', 'king-addons') . '</p>';
        }

        // Apply overrides
        if (!empty($overrides['force_period'])) {
            $config['advanced']['force_period'] = $overrides['force_period'];
        }
        if (!empty($overrides['hide_toggle'])) {
            $config['advanced']['hide_toggle'] = true;
        }
        if (!empty($overrides['columns_desktop'])) {
            $config['table']['layout']['columns_desktop'] = (int)$overrides['columns_desktop'];
        }
        if (!empty($overrides['columns_tablet'])) {
            $config['table']['layout']['columns_tablet'] = (int)$overrides['columns_tablet'];
        }
        if (!empty($overrides['columns_mobile'])) {
            $config['table']['layout']['columns_mobile'] = (int)$overrides['columns_mobile'];
        }
        if (!empty($overrides['max_width'])) {
            $config['table']['layout']['max_width'] = (int)$overrides['max_width'];
        }
        if (!empty($overrides['alignment'])) {
            $config['table']['layout']['alignment'] = $overrides['alignment'];
        }

        $this->enqueue_frontend_assets_if_needed();

        return $this->render_table($config);
    }
}
