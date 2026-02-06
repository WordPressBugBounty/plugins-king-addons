<?php
/**
 * Woo Products Grid widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays products grid for archives/builder.
 */
class Woo_Products_Grid extends Abstract_Archive_Widget
{
    /**
     * External query arguments injected from faceted filters.
     *
     * @var array<string,mixed>
     */
    protected array $external_query_args = [];

    /**
     * Wrapper render attribute handle shared with faceted filters feature.
     */
    private const FILTER_WRAPPER_HANDLE = 'ka-filters-wrapper';

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_products_grid';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Products Grid', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-products';
    }

    /**
     * Widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style handles.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-grid-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        $deps = [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-products-grid-script'];

        if (class_exists('\WooCommerce') && function_exists('wp_script_is') && wp_script_is('wc-add-to-cart', 'registered')) {
            $deps[] = 'wc-add-to-cart';
        }

        return $deps;
    }

    /**
     * Render a controlled Add to Cart button without relying on Woo shortcode.
     *
     * @param \WC_Product $product Product instance.
     *
     * @return string
     */
    private function render_add_to_cart_button(\WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="ka-woo-products-grid__add-to-cart button is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'ka-woo-products-grid__add-to-cart',
            'button',
            'product_type_' . $product->get_type(),
        ];

        if ($product->supports('ajax_add_to_cart')) {
            $classes[] = 'ajax_add_to_cart';
            $classes[] = 'add_to_cart_button';
        }

        $attributes = [
            'href' => $product->add_to_cart_url(),
            'data-quantity' => 1,
            'data-product_id' => $product->get_id(),
            'data-product_sku' => $product->get_sku(),
            'rel' => 'nofollow',
            'class' => implode(' ', array_filter($classes)),
            'aria-label' => wp_strip_all_tags($product->add_to_cart_description()),
        ];

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="ka-woo-products-grid__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Inject external query arguments (faceted filters).
     *
     * @param array<string,mixed> $args Query arguments.
     *
     * @return void
     */
    public function set_external_query_args(array $args): void
    {
        $this->external_query_args = $this->sanitize_external_query_args($args);
    }

    /**
     * Register widget controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_query',
            [
                'label' => esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'query_id',
            [
                'label' => esc_html__('Query ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Use to link filters/sorting/load more (Pro).', 'king-addons'),
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => esc_html__('Products Per Page', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'min' => 1,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'price' => esc_html__('Price', 'king-addons'),
                    'popularity' => esc_html__('Popularity', 'king-addons'),
                    'rating' => esc_html__('Rating', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
                'default' => 'date',
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'DESC' => esc_html__('DESC', 'king-addons'),
                    'ASC' => esc_html__('ASC', 'king-addons'),
                ],
                'default' => 'DESC',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'desktop_default' => 4,
                'tablet_default' => 3,
                'mobile_default' => 2,
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-products-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'layout_type',
            [
                'label' => esc_html__('Layout Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'grid' => esc_html__('Grid', 'king-addons'),
                    'masonry' => sprintf(__('Masonry %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'slider' => sprintf(__('Slider %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'grid',
            ]
        );

        $this->add_control(
            'card_layout',
            [
                'label' => sprintf(__('Card layout %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'classic' => esc_html__('Classic', 'king-addons'),
                    'list' => esc_html__('List (Pro)', 'king-addons'),
                ],
                'default' => 'classic',
            ]
        );

        $this->add_control(
            'slider_loop',
            [
                'label' => sprintf(__('Slider Loop %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'layout_type' => 'slider',
                ],
            ]
        );

        $this->add_control(
            'slider_autoplay',
            [
                'label' => sprintf(__('Slider Autoplay %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'layout_type' => 'slider',
                ],
            ]
        );

        $this->add_control(
            'slider_autoplay_speed',
            [
                'label' => sprintf(__('Autoplay Speed (ms) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5000,
                'condition' => [
                    'layout_type' => 'slider',
                    'slider_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'slider_skin',
            [
                'label' => sprintf(__('Slider Skin %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'arrows' => esc_html__('Arrows', 'king-addons'),
                    'dots' => esc_html__('Dots', 'king-addons'),
                    'both' => esc_html__('Arrows & Dots', 'king-addons'),
                ],
                'default' => 'arrows',
                'condition' => [
                    'layout_type' => 'slider',
                ],
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label' => esc_html__('Pagination', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'numbers' => esc_html__('Numbers', 'king-addons'),
                    'load_more' => sprintf(__('Load more (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'infinite' => sprintf(__('Infinite scroll (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'none',
            ]
        );

        $this->add_control(
            'pagination_skin',
            [
                'label' => esc_html__('Pagination Skin', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'default' => esc_html__('Default', 'king-addons'),
                    'outline' => esc_html__('Outline', 'king-addons'),
                    'ghost' => esc_html__('Ghost', 'king-addons'),
                ],
                'default' => 'default',
                'condition' => [
                    'pagination_type' => ['numbers', 'load_more', 'infinite'],
                ],
            ]
        );

        $this->add_control(
            'pagination_align',
            [
                'label' => esc_html__('Pagination Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'condition' => [
                    'pagination_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'load_more_text',
            [
                'label' => sprintf(__('Load More Text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Load more', 'king-addons'),
                'condition' => [
                    'pagination_type' => 'load_more',
                ],
            ]
        );

        $this->add_control(
            'infinite_loading_text',
            [
                'label' => sprintf(__('Infinite Loading Text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Loading…', 'king-addons'),
                'condition' => [
                    'pagination_type' => 'infinite',
                ],
            ]
        );

        $this->add_control(
            'show_rating',
            [
                'label' => sprintf(__('Show rating %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label' => sprintf(__('Show excerpt %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'excerpt_length',
            [
                'label' => sprintf(__('Excerpt words %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 15,
                'condition' => [
                    'show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_badge',
            [
                'label' => sprintf(__('Show sale badge %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_best_seller_badge',
            [
                'label' => sprintf(__('Show best-seller badge %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_custom_badge',
            [
                'label' => sprintf(__('Show custom badge %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'custom_badge_text',
            [
                'label' => esc_html__('Custom badge text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Featured', 'king-addons'),
                'condition' => [
                    'show_custom_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_brand',
            [
                'label' => sprintf(__('Show brand %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_sku',
            [
                'label' => sprintf(__('Show SKU %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_card',
            [
                'label' => esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-products-grid__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .ka-woo-products-grid__item',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .ka-woo-products-grid__item',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     *
     * @return void
     */
    protected function render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product') || !function_exists('is_shop') || !function_exists('is_product_taxonomy')) {
            return;
        }

        if (!$this->should_render()) {
            $this->render_missing_archive_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $per_page = max(1, (int) ($settings['per_page'] ?? 8));
        $orderby = $this->sanitize_orderby($settings['orderby'] ?? 'date');
        $order = $this->sanitize_order($settings['order'] ?? 'DESC');
        $layout_type = $settings['layout_type'] ?? 'grid';
        $card_layout = $settings['card_layout'] ?? 'classic';
        $pagination_type = $settings['pagination_type'] ?? 'none';
        $can_pro = king_addons_can_use_pro();
        $wrapper_handle = self::FILTER_WRAPPER_HANDLE;

        if (in_array($layout_type, ['masonry', 'slider'], true) && !$can_pro) {
            $layout_type = 'grid';
        }
        if ('list' === $card_layout && !$can_pro) {
            $card_layout = 'classic';
        }
        if (in_array($pagination_type, ['load_more', 'infinite'], true) && !$can_pro) {
            $pagination_type = 'none';
        }

        $paged = max(1, (int) get_query_var('paged', 1));
        if (!empty($this->external_query_args['paged'])) {
            $paged = max(1, (int) $this->external_query_args['paged']);
        }
        if (!empty($this->external_query_args['posts_per_page'])) {
            $per_page = max(1, (int) $this->external_query_args['posts_per_page']);
        }
        if (!empty($this->external_query_args['orderby'])) {
            $orderby = $this->sanitize_orderby((string) $this->external_query_args['orderby']);
        }
        if (!empty($this->external_query_args['order'])) {
            $order = $this->sanitize_order((string) $this->external_query_args['order']);
        }
        $query_id = $settings['query_id'] ?? '';
        $filters = [];
        if ($can_pro && !empty($query_id)) {
            $filters = $this->get_filters_from_request($query_id);
        }
        $filters = self::sanitize_filters_array($filters);
        if (!empty($this->external_query_args['tax_query'])) {
            $filters['tax_query'] = $this->external_query_args['tax_query'];
        }
        $q_args = $this->build_query_args($settings, $paged, $filters, $can_pro, $orderby, $order);
        if (!empty($this->external_query_args['meta_query'])) {
            $q_args['meta_query'] = $this->external_query_args['meta_query'];
        }
        if (!empty($this->external_query_args['s'])) {
            $q_args['s'] = $this->external_query_args['s'];
        }
        if (isset($this->external_query_args['posts_per_page'])) {
            $q_args['posts_per_page'] = max(1, (int) $this->external_query_args['posts_per_page']);
        }
        if (isset($this->external_query_args['paged'])) {
            $q_args['paged'] = max(1, (int) $this->external_query_args['paged']);
        }
        if (isset($this->external_query_args['orderby'])) {
            $q_args['orderby'] = $orderby;
        }
        if (isset($this->external_query_args['order'])) {
            $q_args['order'] = $order;
        }

        $query = new \WP_Query($q_args);
        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $columns = [
            'desktop' => $settings['columns'] ?? 4,
            'tablet' => $settings['columns_tablet'] ?? 3,
            'mobile' => $settings['columns_mobile'] ?? 2,
        ];

        $this->add_render_attribute('grid', 'class', 'ka-woo-products-grid');
        $this->add_render_attribute('grid', 'data-cols-desktop', (int) $columns['desktop']);
        $this->add_render_attribute('grid', 'data-cols-tablet', (int) $columns['tablet']);
        $this->add_render_attribute('grid', 'data-cols-mobile', (int) $columns['mobile']);
        $this->add_render_attribute('grid', 'data-layout-type', esc_attr($layout_type));
        $this->add_render_attribute('grid', 'data-card-layout', esc_attr($card_layout));
        $this->add_render_attribute('grid', 'data-pagination-type', esc_attr($pagination_type));
        if ('slider' === $layout_type) {
            $this->add_render_attribute('grid', 'data-slider-loop', (!empty($settings['slider_loop']) && $can_pro) ? 'true' : 'false');
            $this->add_render_attribute('grid', 'data-slider-autoplay', (!empty($settings['slider_autoplay']) && $can_pro) ? 'true' : 'false');
            $this->add_render_attribute('grid', 'data-slider-autoplay-speed', (int) ($settings['slider_autoplay_speed'] ?? 5000));
            $this->add_render_attribute('grid', 'data-slider-skin', esc_attr($settings['slider_skin'] ?? 'arrows'));
        }

        $show_rating = !empty($settings['show_rating']) && king_addons_can_use_pro();
        $show_excerpt = !empty($settings['show_excerpt']) && king_addons_can_use_pro();
        $excerpt_len = max(5, (int) ($settings['excerpt_length'] ?? 15));
        $show_badge = !empty($settings['show_badge']) && king_addons_can_use_pro();
        $show_best_badge = !empty($settings['show_best_seller_badge']) && $can_pro;
        $show_custom_badge = !empty($settings['show_custom_badge']) && $can_pro;
        $custom_badge_text = !empty($settings['custom_badge_text']) ? $settings['custom_badge_text'] : '';
        $show_brand = !empty($settings['show_brand']) && $can_pro;
        $show_sku = !empty($settings['show_sku']) && $can_pro;

        $nonce = wp_create_nonce('ka_products_grid');
        $this->add_render_attribute('grid', 'data-query-id', esc_attr($query_id));
        $this->add_render_attribute('grid', 'data-page', (int) $paged);
        $this->add_render_attribute('grid', 'data-max-pages', (int) $query->max_num_pages);
        $this->add_render_attribute('grid', 'data-ajax-url', esc_url(admin_url('admin-ajax.php')));
        $this->add_render_attribute('grid', 'data-nonce', esc_attr($nonce));
        $this->add_render_attribute('grid', 'data-order', esc_attr($order));
        $this->add_render_attribute('grid', 'data-orderby', esc_attr($orderby));
        $this->add_render_attribute('grid', 'data-per-page', (int) $per_page);
        $this->add_render_attribute('grid', 'data-show-rating', $show_rating ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-show-excerpt', $show_excerpt ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-excerpt-length', (int) $excerpt_len);
        $this->add_render_attribute('grid', 'data-show-badge', $show_badge ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-show-best-badge', $show_best_badge ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-show-custom-badge', $show_custom_badge ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-custom-badge-text', esc_attr($custom_badge_text));
        $this->add_render_attribute('grid', 'data-show-brand', $show_brand ? 'true' : 'false');
        $this->add_render_attribute('grid', 'data-show-sku', $show_sku ? 'true' : 'false');
        if (!empty($filters)) {
            $this->add_render_attribute('grid', 'data-filters', esc_attr(wp_json_encode($filters)));
        }

        $this->add_render_attribute($wrapper_handle, 'class', 'ka-woo-products-grid__wrap');

        echo '<div ' . $this->get_render_attribute_string($wrapper_handle) . '>';
        echo '<div ' . $this->get_render_attribute_string('grid') . '>';
        $is_slider_layout = ('slider' === $layout_type);
        if ($is_slider_layout) {
            echo '<div class="ka-woo-products-grid__track">';
        }
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) {
                continue;
            }
            echo $this->render_product_card($product, [
                'show_rating' => $show_rating,
                'show_excerpt' => $show_excerpt,
                'excerpt_len' => $excerpt_len,
                'show_badge' => $show_badge,
                'show_best_badge' => $show_best_badge,
                'show_custom_badge' => $show_custom_badge,
                'custom_badge_text' => $custom_badge_text,
                'show_brand' => $show_brand,
                'show_sku' => $show_sku,
                'card_layout' => $card_layout,
            ]);
        }
        if ($is_slider_layout) {
            echo '</div>';
        }
        echo '</div>';

        $pagination_classes = [];
        if (!empty($settings['pagination_skin'])) {
            $pagination_classes[] = 'ka-woo-products-grid__pagination--skin-' . esc_attr($settings['pagination_skin']);
        }
        if (!empty($settings['pagination_align'])) {
            $pagination_classes[] = 'ka-woo-products-grid__pagination--align-' . esc_attr($settings['pagination_align']);
        }

        if (in_array($pagination_type, ['numbers', 'load_more', 'infinite'], true) && $query->max_num_pages > 1) {
            if ('numbers' === $pagination_type) {
                $class = 'ka-woo-products-grid__pagination ka-woo-products-grid__pagination--numbers';
                if (!empty($pagination_classes)) {
                    $class .= ' ' . implode(' ', $pagination_classes);
                }
                echo '<div class="' . esc_attr($class) . '">';
                echo paginate_links([
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                echo '</div>';
            } elseif ('load_more' === $pagination_type || 'infinite' === $pagination_type) {
                $btn_text = 'load_more' === $pagination_type ? ($settings['load_more_text'] ?? esc_html__('Load more', 'king-addons')) : ($settings['infinite_loading_text'] ?? esc_html__('Loading…', 'king-addons'));
                $class = 'ka-woo-products-grid__pagination ka-woo-products-grid__pagination--ajax';
                if (!empty($pagination_classes)) {
                    $class .= ' ' . implode(' ', $pagination_classes);
                }
                $btn_classes = ['ka-woo-products-grid__load-more'];
                if (!empty($settings['pagination_skin'])) {
                    $btn_classes[] = 'ka-woo-products-grid__load-more--skin-' . esc_attr($settings['pagination_skin']);
                }
                echo '<div class="' . esc_attr($class) . '">';
                echo '<button type="button" class="' . esc_attr(implode(' ', $btn_classes)) . '" data-pagination="' . esc_attr($pagination_type) . '" data-loading-text="' . esc_attr($settings['infinite_loading_text'] ?? esc_html__('Loading…', 'king-addons')) . '" data-default-text="' . esc_attr($btn_text) . '"><span class="ka-woo-products-grid__load-more-label">' . esc_html($btn_text) . '</span><span class="ka-woo-products-grid__spinner" aria-hidden="true"></span></button>';
                echo '</div>';
            }
        }
        echo '</div>';

        wp_reset_postdata();
    }

    /**
     * Build query args with filters (Pro gating handled via caller).
     *
     * @param array<string,mixed> $settings Settings.
     * @param int                 $paged    Page.
     * @param array<string,mixed> $filters  Filters.
     * @param bool                $can_pro  Whether Pro is available.
     * @param string              $orderby  Orderby.
     * @param string              $order    Order.
     *
     * @return array<string,mixed>
     */
    protected function build_query_args(array $settings, int $paged, array $filters, bool $can_pro, string $orderby, string $order): array
    {
        $per_page = max(1, (int) ($settings['per_page'] ?? 8));

        $q_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'paged' => $paged,
        ];

        $q_args = array_merge($q_args, $this->external_query_args);

        if ('popularity' === $orderby) {
            $q_args['meta_key'] = 'total_sales';
            $q_args['orderby'] = 'meta_value_num';
        } elseif ('rating' === $orderby) {
            $q_args['meta_key'] = '_wc_average_rating';
            $q_args['orderby'] = 'meta_value_num';
            $q_args['meta_query'][] = [
                'key' => '_wc_average_rating',
                'compare' => 'EXISTS',
            ];
        } elseif ('price' === $orderby) {
            $q_args['meta_key'] = '_price';
            $q_args['orderby'] = 'meta_value_num';
        }

        // Current archive context tax query.
        if (is_product_taxonomy()) {
            $term = get_queried_object();
            if ($term && !empty($term->taxonomy) && !empty($term->term_id)) {
                $q_args['tax_query'][] = [
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => [$term->term_id],
                ];
            }
        }

        if ($can_pro && !empty($filters)) {
            $q_args = $this->apply_filters_to_query_args($q_args, $filters);
        }

        return $q_args;
    }

    /**
     * AJAX renderer for load more / infinite.
     *
     * Expects POST: nonce, page, per_page, order, orderby, flags for rating/excerpt/badge, excerpt_length.
     *
     * @return void
     */
    public static function ajax_render(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            wp_send_json_error(['message' => esc_html__('WooCommerce is not available.', 'king-addons')], 400);
        }

        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'ka_products_grid')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $per_page = isset($_POST['per_page']) ? (int) $_POST['per_page'] : 8;
        $order = self::sanitize_order_static($_POST['order'] ?? 'DESC');
        $orderby = self::sanitize_orderby_static($_POST['orderby'] ?? 'date');
        $can_pro = king_addons_can_use_pro();
        $show_rating = !empty($_POST['show_rating']);
        $show_excerpt = !empty($_POST['show_excerpt']);
        $excerpt_len = isset($_POST['excerpt_length']) ? max(5, (int) $_POST['excerpt_length']) : 15;
        $show_badge = !empty($_POST['show_badge']);
        $show_best_badge = !empty($_POST['show_best_badge']);
        $show_custom_badge = !empty($_POST['show_custom_badge']);
        $custom_badge_text = sanitize_text_field($_POST['custom_badge_text'] ?? '');
        $show_brand = !empty($_POST['show_brand']);
        $show_sku = !empty($_POST['show_sku']);
        $card_layout = sanitize_key($_POST['card_layout'] ?? 'classic');
        if ('list' === $card_layout && !$can_pro) {
            $card_layout = 'classic';
        }
        $filters = [];
        $filters_raw = isset($_POST['filters']) ? wp_unslash((string) $_POST['filters']) : '';
        if (!empty($filters_raw)) {
            $decoded = json_decode($filters_raw, true);
            if (is_array($decoded)) {
                $filters = $decoded;
            }
        }
        $filters = self::sanitize_filters_array($filters);

        $settings = [
            'per_page' => $per_page,
            'order' => $order,
            'orderby' => $orderby,
        ];

        $instance = new self();
        $q_args = $instance->build_query_args(
            $settings,
            max(1, $page),
            $filters,
            king_addons_can_use_pro(),
            $orderby,
            $order
        );

        $query = new \WP_Query($q_args);
        if (!$query->have_posts()) {
            wp_send_json_success(['html' => '', 'max_pages' => 0]);
        }

        ob_start();
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) {
                continue;
            }
            echo $instance->render_product_card($product, [
                'show_rating' => $show_rating,
                'show_excerpt' => $show_excerpt,
                'excerpt_len' => $excerpt_len,
                'show_badge' => $show_badge,
                'show_best_badge' => $show_best_badge,
                'show_custom_badge' => $show_custom_badge,
                'custom_badge_text' => $custom_badge_text,
                'show_brand' => $show_brand,
                'show_sku' => $show_sku,
                'card_layout' => $card_layout,
            ]);
        }
        wp_reset_postdata();

        wp_send_json_success([
            'html' => ob_get_clean(),
            'max_pages' => (int) $query->max_num_pages,
        ]);
    }

    /**
     * Sanitize orderby.
     *
     * @param string $orderby Raw orderby.
     *
     * @return string
     */
    private function sanitize_orderby(string $orderby): string
    {
        return self::sanitize_orderby_static($orderby);
    }

    /**
     * Sanitize order.
     *
     * @param string $order Raw order.
     *
     * @return string
     */
    private function sanitize_order(string $order): string
    {
        return self::sanitize_order_static($order);
    }

    /**
     * Static orderby sanitizer.
     *
     * @param string $orderby Raw orderby.
     *
     * @return string
     */
    private static function sanitize_orderby_static(string $orderby): string
    {
        $allowed = ['date', 'title', 'price', 'popularity', 'rating', 'rand'];
        $clean = in_array($orderby, $allowed, true) ? $orderby : 'date';
        return $clean;
    }

    /**
     * Static order sanitizer.
     *
     * @param string $order Raw order.
     *
     * @return string
     */
    private static function sanitize_order_static(string $order): string
    {
        $clean = strtoupper($order);
        return in_array($clean, ['ASC', 'DESC'], true) ? $clean : 'DESC';
    }

    /**
     * Get filters from request by query id.
     *
     * @param string $query_id Query identifier.
     *
     * @return array<string,mixed>
     */
    private function get_filters_from_request(string $query_id): array
    {
        if (empty($query_id)) {
            return [];
        }
        $raw = $_GET['kng_filter'][$query_id] ?? []; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $filters = [
            'tax_query' => [],
            'meta_query' => [],
            'price' => [],
        ];

        if (is_array($raw)) {
            if (!empty($raw['tax']) && is_array($raw['tax'])) {
                foreach ($raw['tax'] as $taxonomy => $terms) {
                    $taxonomy = sanitize_key((string) $taxonomy);
                    $term_ids = array_filter(array_map('absint', (array) $terms));
                    if ($taxonomy && !empty($term_ids)) {
                        $filters['tax_query'][] = [
                            'taxonomy' => $taxonomy,
                            'field' => 'term_id',
                            'terms' => $term_ids,
                        ];
                    }
                }
            }

            if (!empty($raw['attrs']) && is_array($raw['attrs'])) {
                foreach ($raw['attrs'] as $taxonomy => $terms) {
                    $taxonomy = sanitize_key((string) $taxonomy);
                    $term_ids = array_filter(array_map('absint', (array) $terms));
                    if ($taxonomy && !empty($term_ids)) {
                        $filters['tax_query'][] = [
                            'taxonomy' => $taxonomy,
                            'field' => 'term_id',
                            'terms' => $term_ids,
                        ];
                    }
                }
            }

            if (!empty($raw['price']) && is_array($raw['price'])) {
                $min = isset($raw['price']['min']) ? (float) $raw['price']['min'] : null;
                $max = isset($raw['price']['max']) ? (float) $raw['price']['max'] : null;
                if (null !== $min) {
                    $filters['price']['min'] = $min;
                }
                if (null !== $max) {
                    $filters['price']['max'] = $max;
                }
            }
        }

        // Fallback support for standard Woo query vars (Pro only): min_price/max_price, filter_{taxonomy}.
        $min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (null !== $min_price) {
            $filters['price']['min'] = $min_price;
        }
        if (null !== $max_price) {
            $filters['price']['max'] = $max_price;
        }

        foreach ($_GET as $key => $val) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (0 === strpos($key, 'filter_')) {
                $taxonomy = sanitize_key(str_replace('filter_', '', $key));
                $raw_terms = is_array($val) ? $val : explode(',', (string) $val);
                $terms = array_filter(array_map('sanitize_title', $raw_terms));
                if ($taxonomy && $terms) {
                    $filters['tax_query'][] = [
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms,
                    ];
                }
            }
        }

        return $filters;
    }

    /**
     * Sanitize filters array for safe query usage.
     *
     * @param array<string,mixed> $filters Raw filters.
     *
     * @return array<string,mixed>
     */
    private static function sanitize_filters_array(array $filters): array
    {
        $clean = [
            'tax_query' => [],
            'price' => [],
        ];

        if (!empty($filters['tax_query']) && is_array($filters['tax_query'])) {
            foreach ($filters['tax_query'] as $tax_query) {
                $taxonomy = sanitize_key($tax_query['taxonomy'] ?? '');
                $field = in_array($tax_query['field'] ?? '', ['term_id', 'slug'], true) ? $tax_query['field'] : 'term_id';
                $terms_raw = $tax_query['terms'] ?? [];
                $terms = [];
                if (is_array($terms_raw)) {
                    foreach ($terms_raw as $term) {
                        if ('slug' === $field) {
                            $sanitized_term = sanitize_title((string) $term);
                            if (!empty($sanitized_term)) {
                                $terms[] = $sanitized_term;
                            }
                        } else {
                            $id = absint($term);
                            if ($id > 0) {
                                $terms[] = $id;
                            }
                        }
                    }
                }
                if (!empty($taxonomy) && !empty($terms)) {
                    $clean['tax_query'][] = [
                        'taxonomy' => $taxonomy,
                        'field' => $field,
                        'terms' => $terms,
                    ];
                }
            }
        }

        if (!empty($filters['price']) && is_array($filters['price'])) {
            if (isset($filters['price']['min'])) {
                $clean['price']['min'] = (float) $filters['price']['min'];
            }
            if (isset($filters['price']['max'])) {
                $clean['price']['max'] = (float) $filters['price']['max'];
            }
        }

        return $clean;
    }

    /**
     * Sanitize external query args coming from faceted filters.
     *
     * @param array<string,mixed> $args Raw query args.
     *
     * @return array<string,mixed>
     */
    private function sanitize_external_query_args(array $args): array
    {
        $clean = [];

        if (isset($args['paged'])) {
            $clean['paged'] = max(1, (int) $args['paged']);
        }
        if (isset($args['posts_per_page'])) {
            $clean['posts_per_page'] = max(1, (int) $args['posts_per_page']);
        }
        if (!empty($args['tax_query']) && is_array($args['tax_query'])) {
            $clean['tax_query'] = self::sanitize_filters_array(['tax_query' => $args['tax_query']])['tax_query'];
        }
        if (!empty($args['meta_query']) && is_array($args['meta_query'])) {
            $clean['meta_query'] = $args['meta_query'];
        }
        if (!empty($args['orderby'])) {
            $clean['orderby'] = $this->sanitize_orderby((string) $args['orderby']);
        }
        if (!empty($args['order'])) {
            $clean['order'] = $this->sanitize_order((string) $args['order']);
        }
        if (!empty($args['s'])) {
            $clean['s'] = sanitize_text_field((string) $args['s']);
        }

        return $clean;
    }

    /**
     * Apply filters to query args.
     *
     * @param array<string,mixed> $args    Base args.
     * @param array<string,mixed> $filters Filters.
     *
     * @return array<string,mixed>
     */
    private function apply_filters_to_query_args(array $args, array $filters): array
    {
        return self::apply_filters_to_query_args_static($args, $filters);
    }

    /**
     * Apply filters statically (AJAX-safe).
     *
     * @param array<string,mixed> $args    Base args.
     * @param array<string,mixed> $filters Filters.
     *
     * @return array<string,mixed>
     */
    private static function apply_filters_to_query_args_static(array $args, array $filters): array
    {
        if (!empty($filters['tax_query']) && is_array($filters['tax_query'])) {
            $args['tax_query'] = $filters['tax_query'];
        }

        if (!empty($filters['price']) && is_array($filters['price'])) {
            $min = isset($filters['price']['min']) ? (float) $filters['price']['min'] : null;
            $max = isset($filters['price']['max']) ? (float) $filters['price']['max'] : null;
            $meta = [
                'key' => '_price',
                'compare' => 'BETWEEN',
                'type' => 'DECIMAL(10,2)',
                'value' => [
                    null !== $min ? $min : 0,
                    null !== $max ? $max : 999999,
                ],
            ];
            $args['meta_query'][] = $meta;
        }

        if (!empty($args['tax_query']) && is_array($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        if (!empty($args['meta_query']) && is_array($args['meta_query']) && count($args['meta_query']) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }

        return $args;
    }

    /**
     * Render a single product card.
     *
     * @param \WC_Product          $product Product.
     * @param array<string,mixed>  $args    Card settings.
     *
     * @return string
     */
    private function render_product_card(\WC_Product $product, array $args): string
    {
        $image_id = $product->get_image_id();
        $thumb = $image_id ? wp_get_attachment_image($image_id, 'medium') : wc_placeholder_img('medium');
        $title = $product->get_name();
        $price_html = $product->get_price_html();
        $link = get_permalink($product->get_id());
        $rating_html = !empty($args['show_rating']) ? wc_get_rating_html($product->get_average_rating(), $product->get_rating_count()) : '';
        $excerpt = '';
        $card_layout = $args['card_layout'] ?? 'classic';
        if (!empty($args['show_excerpt'])) {
            $raw = $product->get_short_description() ?: $product->get_description();
            $excerpt = wp_trim_words(wp_strip_all_tags($raw), (int) ($args['excerpt_len'] ?? 15));
        }

        $badges = [];
        if (!empty($args['show_badge']) && $product->is_on_sale()) {
            $badges[] = '<span class="ka-woo-products-grid__badge ka-woo-products-grid__badge--sale">' . esc_html__('Sale', 'king-addons') . '</span>';
        }
        if (!empty($args['show_best_badge']) && $product->get_total_sales() > 0) {
            $badges[] = '<span class="ka-woo-products-grid__badge ka-woo-products-grid__badge--best">' . esc_html__('Best seller', 'king-addons') . '</span>';
        }
        if (!empty($args['show_custom_badge']) && !empty($args['custom_badge_text'])) {
            $badges[] = '<span class="ka-woo-products-grid__badge ka-woo-products-grid__badge--custom">' . esc_html($args['custom_badge_text']) . '</span>';
        }

        $meta_parts = [];
        if (!empty($args['show_brand'])) {
            $brand = $this->get_brand_label($product);
            if ($brand) {
                $meta_parts[] = '<span class="ka-woo-products-grid__meta-item ka-woo-products-grid__meta-item--brand">' . esc_html($brand) . '</span>';
            }
        }
        if (!empty($args['show_sku'])) {
            $sku = $product->get_sku();
            if (!empty($sku)) {
                $meta_parts[] = '<span class="ka-woo-products-grid__meta-item ka-woo-products-grid__meta-item--sku">' . esc_html__('SKU:', 'king-addons') . ' ' . esc_html($sku) . '</span>';
            }
        }

        $img_full = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';
        $caption = $image_id ? get_post_field('post_excerpt', $image_id) : '';

        $layout_class = 'ka-woo-products-grid__item--' . $card_layout;
        $html = '<article class="ka-woo-products-grid__item ' . esc_attr($layout_class) . '">';

        if ('list' === $card_layout) {
            $html .= '<div class="ka-woo-products-grid__list-thumb">';
            if (!empty($badges)) {
                $html .= '<div class="ka-woo-products-grid__badges">' . implode('', $badges) . '</div>';
            }
            $html .= '<a class="ka-woo-products-grid__thumb" href="' . esc_url($link) . '" data-full="' . esc_url($img_full) . '" data-caption="' . esc_attr($caption) . '">' . wp_kses_post($thumb) . '</a>';
            $html .= '</div>';
            $html .= '<div class="ka-woo-products-grid__list-body">';
            $html .= '<h3 class="ka-woo-products-grid__title"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h3>';
            if (!empty($args['show_rating']) && $rating_html) {
                $html .= '<div class="ka-woo-products-grid__rating">' . $rating_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            if (!empty($args['show_excerpt']) && $excerpt) {
                $html .= '<div class="ka-woo-products-grid__excerpt">' . esc_html($excerpt) . '</div>';
            }
            if (!empty($meta_parts)) {
                $html .= '<div class="ka-woo-products-grid__meta">' . implode('', $meta_parts) . '</div>';
            }
            if ($price_html) {
                $html .= '<div class="ka-woo-products-grid__price">' . wp_kses_post($price_html) . '</div>';
            }
            $html .= $this->render_add_to_cart_button($product);
            $html .= '</div>';
        } else {
            $html .= '<div class="ka-woo-products-grid__thumb-wrap">';
            if (!empty($badges)) {
                $html .= '<div class="ka-woo-products-grid__badges">' . implode('', $badges) . '</div>';
            }
            $html .= '<a class="ka-woo-products-grid__thumb" href="' . esc_url($link) . '" data-full="' . esc_url($img_full) . '" data-caption="' . esc_attr($caption) . '">' . wp_kses_post($thumb) . '</a>';
            $html .= '</div>';
            $html .= '<h3 class="ka-woo-products-grid__title"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h3>';
            if (!empty($args['show_rating']) && $rating_html) {
                $html .= '<div class="ka-woo-products-grid__rating">' . $rating_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            if (!empty($args['show_excerpt']) && $excerpt) {
                $html .= '<div class="ka-woo-products-grid__excerpt">' . esc_html($excerpt) . '</div>';
            }
            if ($price_html) {
                $html .= '<div class="ka-woo-products-grid__price">' . wp_kses_post($price_html) . '</div>';
            }
            if (!empty($meta_parts)) {
                $html .= '<div class="ka-woo-products-grid__meta">' . implode('', $meta_parts) . '</div>';
            }
            $html .= $this->render_add_to_cart_button($product);
        }
        $html .= '</article>';

        return $html;
    }

    /**
     * Get product brand label.
     *
     * @param \WC_Product $product Product.
     *
     * @return string
     */
    private function get_brand_label(\WC_Product $product): string
    {
        $brand_terms = wc_get_product_terms($product->get_id(), 'product_brand', ['fields' => 'names']);
        if (!empty($brand_terms) && is_array($brand_terms)) {
            return $brand_terms[0];
        }

        $brand_attribute = wc_get_product_terms($product->get_id(), 'pa_brand', ['fields' => 'names']);
        if (!empty($brand_attribute) && is_array($brand_attribute)) {
            return $brand_attribute[0];
        }

        $meta_brand = get_post_meta($product->get_id(), '_brand', true);
        if (!empty($meta_brand)) {
            return (string) $meta_brand;
        }

        return '';
    }
}

add_action('wp_ajax_ka_products_grid', [Woo_Products_Grid::class, 'ajax_render']);
add_action('wp_ajax_nopriv_ka_products_grid', [Woo_Products_Grid::class, 'ajax_render']);






