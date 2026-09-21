<?php
/**
 * Faceted filters feature bootstrap.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Plugin;

require_once __DIR__ . '/class-faceted-query-builder.php';

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles faceted filters initialization, assets, and AJAX endpoint.
 */
class Faceted_Filters
{
    /**
     * AJAX action name.
     */
    public const AJAX_ACTION = 'king_addons_faceted_filters';

    /**
     * Wrapper render attribute key shared across supported grid widgets.
     */
    private const WRAPPER_HANDLE = 'ka-filters-wrapper';

    /**
     * Post id from the current AJAX request, used when re-rendering widgets.
     */
    private int $ajax_post_id = 0;

    /**
     * Archive term from the current AJAX request (category/tag archives).
     *
     * @var array{taxonomy?: string, term_id?: int}
     */
    private array $ajax_archive = [];

    /**
     * Cache version option name for invalidation.
     */
    private const CACHE_VERSION_OPTION = 'ka_ff_cache_version';
    /**
     * Supported widget names.
     *
     * @var array<int, string>
     */
    private array $supported_widgets = [
        'king-addons-grid',
        'king-addons-dynamic-posts-grid',
        'king-addons-woocommerce-grid',
        'king-addons-quick-product-grid',
        'king-addons-magazine-grid',
        'king-addons-media-grid',
        'woo_products_grid',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_assets']);
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [$this, 'handle_ajax_request']);
        add_action('elementor/frontend/widget/before_render', [$this, 'inject_grid_attributes'], 5);

        add_action('elementor/element/after_section_end', [$this, 'maybe_register_widget_controls'], 10, 3);

        // Cache invalidation hooks.
        add_action('save_post', [$this, 'bump_cache_version']);
        add_action('deleted_post', [$this, 'bump_cache_version']);
        add_action('clean_post_cache', [$this, 'bump_cache_version']);
        add_action('set_object_terms', [$this, 'bump_cache_version']);
        add_action('created_term', [$this, 'bump_cache_version']);
        add_action('edited_term', [$this, 'bump_cache_version']);
        add_action('delete_term', [$this, 'bump_cache_version']);
    }

    /**
     * Enqueue frontend assets and provide localized data.
     *
     * @return void
     */
    public function enqueue_assets(): void
    {
        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-faceted-filters-style',
            KING_ADDONS_URL . 'includes/features/Faceted_Filters/style.css',
            [],
            KING_ADDONS_VERSION
        );

        $script_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-faceted-filters-script';

        wp_enqueue_script(
            $script_handle,
            KING_ADDONS_URL . 'includes/features/Faceted_Filters/script.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script(
            $script_handle,
            'KingAddonsFacetedFilters',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'action' => self::AJAX_ACTION,
                'nonce' => wp_create_nonce(self::AJAX_ACTION),
                'i18n' => [
                    'price' => esc_html__('Price', 'king-addons'),
                    'search' => esc_html__('Search', 'king-addons'),
                    'page' => esc_html__('Page', 'king-addons'),
                    'sort' => esc_html__('Sort', 'king-addons'),
                    'sku' => esc_html__('SKU', 'king-addons'),
                    'category' => esc_html__('Category', 'king-addons'),
                    'tag' => esc_html__('Tag', 'king-addons'),
                    'filters' => esc_html__('Filters', 'king-addons'),
                    'apply' => esc_html__('Apply', 'king-addons'),
                    'showResults' => esc_html__('Show results', 'king-addons'),
                    'close' => esc_html__('Close', 'king-addons'),
                ],
            ]
        );
    }

    /**
     * Register Shop Filters controls on supported grid widgets.
     *
     * Grid widgets inherit Advanced Layout from Elementor's `common` widget, so
     * `_section_style` never fires on the grid itself. Hook any of the grid's
     * own sections and add a TAB_ADVANCED section once.
     *
     * @param mixed                $element    Elementor element.
     * @param string               $section_id Section id that just ended.
     * @param array<string, mixed> $args       Section args.
     *
     * @return void
     */
    public function maybe_register_widget_controls($element, $section_id, $args = []): void
    {
        if (!$element instanceof Element_Base) {
            return;
        }

        if (!in_array($element->get_name(), $this->supported_widgets, true)) {
            return;
        }

        $controls = $element->get_controls();
        if (isset($controls['ka_filters_enable'])) {
            return;
        }

        $this->register_widget_controls($element, is_array($args) ? $args : []);
    }

    /**
     * Add Faceted Filters controls to supported widgets.
     *
     * @param Element_Base $element Elementor element.
     * @param array<mixed> $args    Elementor args.
     *
     * @return void
     */
    public function register_widget_controls(Element_Base $element, array $args): void
    {
        $element->start_controls_section(
            'ka_faceted_filters_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Shop Filters', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        $element->add_control(
            'ka_filters_enable',
            [
                'label' => esc_html__('Enable Shop Filters', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $element->add_control(
            'ka_filters_query_id',
            [
                'label' => esc_html__('Shop Filters ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('shop_grid_1', 'king-addons'),
                'description' => esc_html__('Use the same ID on every filter widget linked to this grid.', 'king-addons'),
                'condition' => [
                    'ka_filters_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'ka_filters_ajax',
            [
                'label' => esc_html__('Update without reloading', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'ka_filters_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'ka_filters_persist_url',
            [
                'label' => sprintf(__('Keep filters in the URL %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'ka_filters_enable' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($element, Controls_Manager::RAW_HTML, 'shop-filters', 'ka_filters_persist_url', 'yes');

        $element->add_control(
            'ka_filters_apply',
            [
                'label' => sprintf(__('When to update the grid %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'instant',
                'options' => [
                    'instant' => esc_html__('As soon as a filter changes', 'king-addons'),
                    'button' => esc_html__('When Apply is clicked', 'king-addons'),
                ],
                'condition' => [
                    'ka_filters_enable' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($element, Controls_Manager::RAW_HTML, 'shop-filters', 'ka_filters_apply', 'button');

        $element->add_control(
            'ka_filters_drawer',
            [
                'label' => sprintf(__('Mobile filter drawer %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('On small screens, move the linked filter widgets into a panel opened by a Filters button.', 'king-addons'),
                'condition' => [
                    'ka_filters_enable' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($element, Controls_Manager::RAW_HTML, 'shop-filters', 'ka_filters_drawer', 'yes');

        $element->end_controls_section();
    }

    /**
     * Inject data attributes into grid wrapper when filters are enabled.
     *
     * @param Element_Base $element Elementor element instance.
     *
     * @return void
     */
    public function inject_grid_attributes(Element_Base $element): void
    {
        if (!in_array($element->get_name(), $this->supported_widgets, true)) {
            return;
        }

        $settings = $element->get_settings_for_display();

        if (($settings['ka_filters_enable'] ?? '') !== 'yes') {
            return;
        }

        $query_id = isset($settings['ka_filters_query_id']) ? sanitize_title((string) $settings['ka_filters_query_id']) : '';
        if ('' === $query_id) {
            return;
        }

        $attrs = [
            'data-ka-filters' => '1',
            'data-ka-query-id' => $query_id,
            'data-ka-widget-id' => $element->get_id(),
            'data-ka-post-id' => (string) $this->resolve_document_id(),
            'data-ka-ajax-url' => admin_url('admin-ajax.php'),
            'data-ka-ajax-action' => self::AJAX_ACTION,
            'data-ka-nonce' => wp_create_nonce(self::AJAX_ACTION),
            'data-ka-ajax' => ($settings['ka_filters_ajax'] ?? 'yes') === 'yes' ? '1' : '0',
            'data-ka-persist-url' => (
                ($settings['ka_filters_persist_url'] ?? '') === 'yes'
                && function_exists('king_addons_can_use_pro')
                && king_addons_can_use_pro()
            ) ? '1' : '0',
            'data-ka-apply' => (
                ($settings['ka_filters_apply'] ?? 'instant') === 'button'
                && function_exists('king_addons_can_use_pro')
                && king_addons_can_use_pro()
            ) ? 'button' : 'instant',
            'data-ka-drawer' => (
                ($settings['ka_filters_drawer'] ?? '') === 'yes'
                && function_exists('king_addons_can_use_pro')
                && king_addons_can_use_pro()
            ) ? '1' : '0',
        ];

        $archive = $this->get_current_archive_term();
        if ($archive) {
            $attrs['data-ka-archive-taxonomy'] = $archive['taxonomy'];
            $attrs['data-ka-archive-term'] = (string) $archive['term_id'];
        }

        $element->add_render_attribute(self::WRAPPER_HANDLE, $attrs);
    }

    /**
     * Elementor document id that contains the grid (theme-builder template, not the shop page).
     *
     * @return int
     */
    private function resolve_document_id(): int
    {
        if ($this->ajax_post_id > 0) {
            return $this->ajax_post_id;
        }

        $current = Plugin::$instance->documents->get_current();
        if ($current) {
            $id = (int) $current->get_main_id();
            if ($id > 0) {
                return $id;
            }
        }

        $template_id = (int) apply_filters('king_addons/woo_builder/current_template_id', 0, 'product_archive');
        if ($template_id > 0) {
            return $template_id;
        }

        return (int) get_the_ID();
    }

    /**
     * Current product category/tag when rendering a Woo archive.
     *
     * @return array{taxonomy: string, term_id: int}|null
     */
    private function get_current_archive_term(): ?array
    {
        if (!empty($this->ajax_archive['taxonomy']) && !empty($this->ajax_archive['term_id'])) {
            return [
                'taxonomy' => (string) $this->ajax_archive['taxonomy'],
                'term_id' => (int) $this->ajax_archive['term_id'],
            ];
        }

        if (!function_exists('is_product_taxonomy') || !is_product_taxonomy()) {
            return null;
        }

        $term = get_queried_object();
        if (!$term || empty($term->taxonomy) || empty($term->term_id)) {
            return null;
        }

        return [
            'taxonomy' => sanitize_key((string) $term->taxonomy),
            'term_id' => (int) $term->term_id,
        ];
    }

    /**
     * Handle AJAX refresh requests.
     *
     * @return void
     */
    public function handle_ajax_request(): void
    {
        check_ajax_referer(self::AJAX_ACTION, 'nonce');

        $payload = isset($_POST['payload']) ? wp_unslash((string) $_POST['payload']) : '';
        $data = json_decode($payload, true);

        if (!is_array($data)) {
            wp_send_json_error(['message' => esc_html__('Invalid payload.', 'king-addons')]);
        }

        if (empty($data['query_id']) || empty($data['widget_id']) || empty($data['post_id'])) {
            wp_send_json_error(['message' => esc_html__('Required data is missing.', 'king-addons')]);
        }

        $this->ajax_post_id = (int) $data['post_id'];
        $archive = is_array($data['archive'] ?? null) ? $data['archive'] : [];
        $this->ajax_archive = [
            'taxonomy' => isset($archive['taxonomy']) ? sanitize_key((string) $archive['taxonomy']) : '',
            'term_id' => isset($archive['term_id']) ? (int) $archive['term_id'] : 0,
        ];
        $query_id = sanitize_title((string) $data['query_id']);
        $variable_price = $this->sanitize_variable_price_mode($data['variable_price'] ?? '');
        if ('' === $variable_price) {
            $variable_price = $this->collect_variable_price_mode($this->ajax_post_id, $query_id);
        }
        $data['variable_price'] = $variable_price;

        $cache_key = 'ka_ff_' . $this->get_cache_version() . '_' . md5(wp_json_encode($data));
        $use_cache = empty($_POST['no_cache']);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if (!empty($cached)) {
                wp_send_json_success($cached);
            }
        }

        $widget_data = $this->get_widget_data((int) $data['post_id'], (string) $data['widget_id']);

        if (null === $widget_data) {
            wp_send_json_error(['message' => esc_html__('Widget not found.', 'king-addons')]);
        }

        $filter_state = [
            'page' => isset($data['page']) ? (int) $data['page'] : 1,
            'filters' => is_array($data['filters'] ?? null) ? $data['filters'] : [],
            'variable_price' => $variable_price,
        ];

        $grid_settings = isset($widget_data['settings']) && is_array($widget_data['settings']) ? $widget_data['settings'] : [];
        $grid_settings['widgetType'] = $widget_data['widgetType'] ?? '';
        $base_args = $this->build_base_args($widget_data);

        $query_builder = new Faceted_Query_Builder($grid_settings, $filter_state, $base_args);
        $query_args = $query_builder->build_query_args();

        $meta = $this->prepare_query_meta($query_args, $query_id);
        $counts = $this->build_counts($query_builder, $grid_settings, $query_args);
        $html = $this->render_widget($widget_data, $query_args);

        $response = [
            'html' => $html,
            'pagination' => $meta['pagination'] ?? '',
            'total' => $meta['total'] ?? 0,
            'total_pages' => $meta['total_pages'] ?? 0,
            'current_page' => $filter_state['page'] ?? 1,
            'counts' => $counts,
            'message' => esc_html__('Shop Filters updated.', 'king-addons'),
        ];

        if ($use_cache) {
            $ttl = (int) apply_filters('king_addons/faceted_filters/cache_ttl', MINUTE_IN_SECONDS);
            set_transient($cache_key, $response, max(30, $ttl));
        }

        wp_send_json_success($response);
    }

    /**
     * Get cache version for transient namespace.
     *
     * @return string
     */
    private function get_cache_version(): string
    {
        $version = get_option(self::CACHE_VERSION_OPTION, '1');
        return is_scalar($version) ? (string) $version : '1';
    }

    /**
     * Bump cache version to invalidate all transient caches.
     *
     * @return void
     */
    public function bump_cache_version(): void
    {
        $current = (int) get_option(self::CACHE_VERSION_OPTION, 1);
        update_option(self::CACHE_VERSION_OPTION, (string) ($current + 1));
    }

    /**
     * Build baseline query args from widget settings.
     *
     * @param array<string, mixed> $widget_data Widget data array.
     *
     * @return array<string, mixed>
     */
    private function build_base_args(array $widget_data): array
    {
        $settings = $widget_data['settings'] ?? [];
        $widget_type = $widget_data['widgetType'] ?? '';

        // Quick Product Grid (free/pro)
        if ('king-addons-quick-product-grid' === $widget_type) {
            $per_page = !empty($settings['kng_products_per_page']) ? (int) $settings['kng_products_per_page'] : 6;
            $per_page = min($per_page, 12);

            $args = [
                'post_type' => 'product',
                'posts_per_page' => $per_page,
                'order' => $settings['kng_order'] ?? 'DESC',
                'orderby' => $this->map_product_orderby($settings['kng_orderby'] ?? 'date'),
                'post_status' => 'publish',
                'paged' => !empty($settings['paged']) ? (int) $settings['paged'] : 1,
                'meta_query' => [],
                'tax_query' => [],
            ];

            return $args;
        }

        // Woo Products Grid (archive)
        if ('woo_products_grid' === $widget_type) {
            $per_page = !empty($settings['per_page']) ? (int) $settings['per_page'] : (!empty($settings['layout_posts_per_page']) ? (int) $settings['layout_posts_per_page'] : (int) get_option('posts_per_page'));
            $paged = !empty($settings['paged']) ? (int) $settings['paged'] : max(1, (int) get_query_var('paged', 1));

            $args = [
                'post_type' => 'product',
                'posts_per_page' => $per_page,
                'paged' => $paged,
                'post_status' => 'publish',
                'meta_query' => [],
                'tax_query' => [],
            ];

            $archive = $this->get_current_archive_term();
            if ($archive) {
                $args['tax_query'][] = [
                    'taxonomy' => $archive['taxonomy'],
                    'field' => 'term_id',
                    'terms' => [$archive['term_id']],
                ];
            }

            if (function_exists('wc')) {
                $ordering = wc()->query->get_catalog_ordering_args();
                if (!empty($ordering['orderby'])) {
                    $args['orderby'] = $ordering['orderby'];
                }
                if (!empty($ordering['order'])) {
                    $args['order'] = $ordering['order'];
                }
                if (!empty($ordering['meta_key'])) {
                    $args['meta_key'] = $ordering['meta_key'];
                }
            }

            return $args;
        }

        return [];
    }

    /**
     * Map orderby option for product queries.
     *
     * @param string $orderby Orderby.
     *
     * @return string
     */
    private function map_product_orderby(string $orderby): string
    {
        $map = [
            'price' => 'meta_value_num',
            'popularity' => 'meta_value_num',
            'rating' => 'meta_value_num',
            'rand' => 'rand',
            'title' => 'title',
            'date' => 'date',
        ];

        return $map[$orderby] ?? 'date';
    }

    /**
     * Prepare meta info about query for pagination/total.
     *
     * @param array<string, mixed> $query_args Args.
     * @param string               $query_id   Filter query id.
     *
     * @return array<string, mixed>
     */
    private function prepare_query_meta(array $query_args, string $query_id = ''): array
    {
        $query = new \WP_Query($query_args);
        $total = (int) $query->found_posts;
        $pages = (int) $query->max_num_pages;

        wp_reset_postdata();

        return [
            'total' => $total,
            'total_pages' => $pages,
            'pagination' => $this->build_pagination_html((int) ($query_args['paged'] ?? 1), $pages, $query_id),
        ];
    }

    /**
     * Build simple pagination HTML for response.
     *
     * @param int    $current  Current page.
     * @param int    $total    Total pages.
     * @param string $query_id Filter query id.
     *
     * @return string
     */
    private function build_pagination_html(int $current, int $total, string $query_id = ''): string
    {
        if ($total <= 1) {
            return '';
        }

        $html = '<div class="ka-filters-pagination" data-ka-pagination="1" data-ka-filters-query-id="' . esc_attr($query_id) . '">';
        if ($current > 1) {
            $prev = $current - 1;
            $html .= '<button type="button" data-ka-filter-type="pagination" data-ka-filters-query-id="' . esc_attr($query_id) . '" data-ka-page="' . esc_attr((string) $prev) . '">&laquo;</button>';
        }

        for ($i = 1; $i <= $total; $i++) {
            $html .= '<button type="button" data-ka-filter-type="pagination" data-ka-filters-query-id="' . esc_attr($query_id) . '" data-ka-page="' . esc_attr((string) $i) . '"';
            if ($i === $current) {
                $html .= ' class="is-active"';
            }
            $html .= '>' . esc_html((string) $i) . '</button>';
        }

        if ($current < $total) {
            $next = $current + 1;
            $html .= '<button type="button" data-ka-filter-type="pagination" data-ka-filters-query-id="' . esc_attr($query_id) . '" data-ka-page="' . esc_attr((string) $next) . '">&raquo;</button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build facet counts map.
     *
     * @param Faceted_Query_Builder $builder       Builder instance.
     * @param array<string, mixed>  $grid_settings Widget settings.
     * @param array<string, mixed>  $query_args    Query arguments to evaluate.
     *
     * @return array<string, array<string, int>>
     */
    private function build_counts(Faceted_Query_Builder $builder, array $grid_settings, array $query_args): array
    {
        $taxonomies = [];
        $widget_type = $grid_settings['widgetType'] ?? '';

        if ('king-addons-quick-product-grid' === $widget_type || 'woo_products_grid' === $widget_type) {
            $taxonomies['product_cat'] = 'product_cat';
            $taxonomies['product_tag'] = 'product_tag';
            if (function_exists('wc_get_attribute_taxonomies') && function_exists('wc_attribute_taxonomy_name')) {
                foreach (wc_get_attribute_taxonomies() as $attribute) {
                    if (empty($attribute->attribute_name)) {
                        continue;
                    }
                    $tax = wc_attribute_taxonomy_name($attribute->attribute_name);
                    $taxonomies[$tax] = $tax;
                }
            }
        }

        $counts = [];
        if (!empty($taxonomies)) {
            $counts['taxonomy'] = $builder->build_taxonomy_counts($taxonomies, $query_args);
        }

        $price_buckets = apply_filters(
            'king_addons/faceted_filters/price_buckets',
            [
                ['min' => 0, 'max' => 25],
                ['min' => 25, 'max' => 50],
                ['min' => 50, 'max' => 100],
                ['min' => 100, 'max' => 250],
                ['min' => 250, 'max' => 999999],
            ],
            $widget_type
        );
        $counts['price'] = $builder->build_price_counts($price_buckets, $query_args);

        $meta_keys = [];
        if (function_exists('king_addons_can_use_pro') && king_addons_can_use_pro()) {
            $meta_keys = apply_filters('king_addons/faceted_filters/meta_keys', [], $widget_type);
            if ($this->ajax_post_id > 0) {
                $meta_keys = array_merge($meta_keys, $this->collect_meta_keys_from_document($this->ajax_post_id));
            }
        }
        $meta_keys = array_values(array_unique(array_filter(array_map('sanitize_key', (array) $meta_keys))));
        if (!empty($meta_keys)) {
            $counts['meta'] = $builder->build_meta_counts($meta_keys, $query_args);
        }

        return $counts;
    }

    /**
     * Get widget data by Elementor widget id.
     *
     * @param int    $post_id   Post or document id.
     * @param string $widget_id Elementor widget id.
     *
     * @return array<string, mixed>|null
     */
    private function get_widget_data(int $post_id, string $widget_id): ?array
    {
        $document = Plugin::$instance->documents->get($post_id);
        if (!$document) {
            return null;
        }

        $elements = $document->get_elements_data();

        return $this->find_widget_recursive($elements, $widget_id);
    }

    /**
     * Recursive search for widget data.
     *
     * @param array<int, array<string, mixed>> $elements Elements tree.
     * @param string                           $widget_id Widget id.
     *
     * @return array<string, mixed>|null
     */
    private function find_widget_recursive(array $elements, string $widget_id): ?array
    {
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $widget_id) {
                return $element;
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $found = $this->find_widget_recursive($element['elements'], $widget_id);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Collect Facet Meta keys from the Elementor document.
     *
     * @param int $post_id Document id.
     *
     * @return array<int, string>
     */
    private function collect_meta_keys_from_document(int $post_id): array
    {
        $document = Plugin::$instance->documents->get($post_id);
        if (!$document) {
            return [];
        }

        $keys = [];
        $this->collect_meta_keys_recursive($document->get_elements_data(), $keys);

        return array_values(array_unique($keys));
    }

    /**
     * Sanitize variable-price matching mode.
     *
     * @param mixed $value Raw value.
     *
     * @return string any, displayed, or empty if unknown.
     */
    private function sanitize_variable_price_mode($value): string
    {
        $value = is_string($value) ? $value : '';

        return in_array($value, ['any', 'displayed'], true) ? $value : '';
    }

    /**
     * Variable-price mode from a Facet Price widget on the document.
     *
     * @param int    $post_id  Document id.
     * @param string $query_id Filter query id.
     *
     * @return string
     */
    private function collect_variable_price_mode(int $post_id, string $query_id): string
    {
        $document = Plugin::$instance->documents->get($post_id);
        if (!$document || '' === $query_id) {
            return 'any';
        }

        $mode = $this->collect_variable_price_mode_recursive($document->get_elements_data(), $query_id);

        return $mode !== '' ? $mode : 'any';
    }

    /**
     * Walk Elementor elements for Facet Price variable-price setting.
     *
     * @param array<int, array<string, mixed>> $elements Elements tree.
     * @param string                           $query_id Filter query id.
     *
     * @return string
     */
    private function collect_variable_price_mode_recursive(array $elements, string $query_id): string
    {
        foreach ($elements as $element) {
            if (($element['widgetType'] ?? '') === 'king-addons-facet-price') {
                $widget_query_id = sanitize_title((string) ($element['settings']['kng_filters_query_id'] ?? ''));
                if ($widget_query_id === $query_id) {
                    $mode = $this->sanitize_variable_price_mode($element['settings']['kng_variable_price'] ?? 'any');

                    return $mode !== '' ? $mode : 'any';
                }
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $found = $this->collect_variable_price_mode_recursive($element['elements'], $query_id);
                if ('' !== $found) {
                    return $found;
                }
            }
        }

        return '';
    }

    /**
     * Walk Elementor elements for facet meta widgets.
     *
     * @param array<int, array<string, mixed>> $elements Elements tree.
     * @param array<int, string>               $keys     Collected keys.
     *
     * @return void
     */
    private function collect_meta_keys_recursive(array $elements, array &$keys): void
    {
        foreach ($elements as $element) {
            $type = $element['widgetType'] ?? '';
            if (in_array($type, ['facet_meta', 'king-addons-facet-meta'], true)) {
                $key = sanitize_key((string) ($element['settings']['meta_key'] ?? ''));
                if ('' !== $key) {
                    $keys[] = $key;
                }
            }

            if (!empty($element['elements']) && is_array($element['elements'])) {
                $this->collect_meta_keys_recursive($element['elements'], $keys);
            }
        }
    }

    /**
     * Render widget HTML with external query arguments.
     *
     * @param array<string, mixed> $widget_data Widget data array from Elementor document.
     * @param array<string, mixed> $external_query_args Query override.
     *
     * @return string
     */
    private function render_widget(array $widget_data, array $external_query_args): string
    {
        /** @var object $widget */
        $widget = Plugin::$instance->elements_manager->create_element_instance($widget_data);

        if (!$widget) {
            return '';
        }

        if (method_exists($widget, 'set_external_query_args')) {
            $widget->{'set_external_query_args'}($external_query_args);
        }

        $widget->set_settings($widget_data['settings'] ?? []);
        $this->inject_grid_attributes($widget);

        ob_start();
        if (method_exists($widget, 'render_content')) {
            $widget->{'render_content'}();
        } elseif (method_exists($widget, 'render')) {
            $widget->{'render'}();
        }

        return (string) ob_get_clean();
    }
}






