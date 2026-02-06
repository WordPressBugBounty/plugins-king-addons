<?php
/**
 * Quick Product Grid Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Quick Product Grid widget.
 */
class Quick_Product_Grid extends Widget_Base
{
    /**
     * External query arguments injected from faceted filters.
     *
     * @var array<string, mixed>
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
        return 'king-addons-quick-product-grid';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Quick Product Grid', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-quick-product-grid';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-product-grid-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        $deps = [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-product-grid-script',
        ];

        if (class_exists('\WooCommerce') && function_exists('wp_script_is') && wp_script_is('wc-add-to-cart', 'registered')) {
            $deps[] = 'wc-add-to-cart';
        }

        return $deps;
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['woocommerce', 'product', 'quick', 'grid', 'shop'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_query_controls();
        $this->register_layout_controls();
        $this->register_display_controls();
        $this->register_interaction_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_rating_controls();
        $this->register_style_badge_controls();
        $this->register_style_button_controls();
        $this->register_style_view_cart_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Interaction controls.
     *
     * @return void
     */
    protected function register_interaction_controls(): void
    {
        $this->start_controls_section(
            'kng_interaction_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Animations', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hover-small-lift',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'hover-small-lift' => esc_html__('Small Lift', 'king-addons'),
                    'hover-lift' => esc_html__('Lift', 'king-addons'),
                    'hover-zoom' => esc_html__('Image Zoom', 'king-addons'),
                    'hover-tilt' => esc_html__('Tilt', 'king-addons'),
                    'hover-float' => esc_html__('Float', 'king-addons'),
                    'hover-scale-fade' => esc_html__('Scale & Fade', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        if (!class_exists('\WooCommerce')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $query = $this->build_query($settings);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_style = $this->get_wrapper_style($settings);
        $wrapper_handle = self::FILTER_WRAPPER_HANDLE;

        $this->add_render_attribute($wrapper_handle, 'class', $wrapper_classes);

        if ('' !== $wrapper_style) {
            $this->add_render_attribute($wrapper_handle, 'style', $wrapper_style);
        }

        ?>
        <div <?php echo $this->get_render_attribute_string($wrapper_handle); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-quick-product-grid__grid">
                <?php
                while ($query->have_posts()) :
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());
                    if ($product) {
                        $this->render_card($settings, $product);
                    }
                endwhile;
                ?>
            </div>
        </div>
        <?php

        wp_reset_postdata();
    }

    /**
     * Query controls.
     *
     * @return void
     */
    protected function register_query_controls(): void
    {
        $this->start_controls_section(
            'kng_query_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_products_per_page',
            [
                'label' => esc_html__('Products Number', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'default' => 6,
                'description' => esc_html__('Free version limited to 12 products.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'price' => esc_html__('Price', 'king-addons'),
                    'popularity' => esc_html__('Popularity', 'king-addons'),
                    'rating' => esc_html__('Rating', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('DESC', 'king-addons'),
                    'ASC' => esc_html__('ASC', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_categories',
            [
                'label' => sprintf(__('Categories %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated slugs.', 'king-addons'),
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_include_products',
            [
                'label' => esc_html__('Include Products', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated product IDs.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_exclude_products',
            [
                'label' => esc_html__('Exclude Products', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated product IDs.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_on_sale',
            [
                'label' => esc_html__('Show Only On Sale', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_featured_only',
            [
                'label' => esc_html__('Show Only Featured', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_in_stock',
            [
                'label' => esc_html__('Only In Stock', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 3,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_grid_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Display controls.
     *
     * @return void
     */
    protected function register_display_controls(): void
    {
        $this->start_controls_section(
            'kng_display_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Display', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_rating',
            [
                'label' => esc_html__('Show Rating', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_price',
            [
                'label' => esc_html__('Show Price', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Short Description', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_add_to_cart',
            [
                'label' => esc_html__('Show Add to Cart', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_badge',
            [
                'label' => esc_html__('Show Sale Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_badge_text',
            [
                'label' => esc_html__('Sale Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Sale', 'king-addons'),
                'condition' => [
                    'kng_show_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_excerpt_length',
            [
                'label' => esc_html__('Excerpt Length (words)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 60,
                'step' => 1,
                'default' => 16,
                'condition' => [
                    'kng_show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'medium',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Card style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_card_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__card',
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__card',
            ]
        );

        $this->add_control(
            'kng_card_shadow_disable',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__card' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_shadow_hover_disable',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__card:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Text styles.
     *
     * @return void
     */
    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_style_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_price_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__price',
            ]
        );

        $this->add_control(
            'kng_price_color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__excerpt',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Rating styles.
     *
     * @return void
     */
    protected function register_style_rating_controls(): void
    {
        $this->start_controls_section(
            'kng_style_rating_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Rating', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_rating' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_color_empty',
            [
                'label' => esc_html__('Empty Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__rating .star-rating' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_color_filled',
            [
                'label' => esc_html__('Filled Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__rating .star-rating span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 10, 'max' => 32],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__rating .star-rating' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Sale badge styles.
     *
     * @return void
     */
    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_style_badge_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sale Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_badge' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__badge',
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_badge_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__badge',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_badge_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__badge',
            ]
        );

        $this->add_control(
            'kng_badge_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_offset_top',
            [
                'label' => esc_html__('Offset Top', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_offset_left',
            [
                'label' => esc_html__('Offset Left', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__badge' => 'left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button styles.
     *
     * @return void
     */
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Add to Cart', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_cta_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Start', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('End', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_full_width',
            [
                'label' => esc_html__('Full Width Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta' => 'width: 100%;',
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button' => 'width: 100%; justify-content: center;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_indicator_size',
            [
                'label' => esc_html__('Indicator Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 8, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .king-addons-quick-product-grid__add-to-cart' => '--king-addons-atc-indicator-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_button_style_tabs');

        $this->start_controls_tab(
            'kng_button_style_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_button_style_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * View cart link styles (WooCommerce injects `.added_to_cart` link after AJAX add to cart).
     *
     * @return void
     */
    protected function register_style_view_cart_controls(): void
    {
        $this->start_controls_section(
            'kng_style_view_cart_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('View Cart Link', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_gap',
            [
                'label' => esc_html__('Spacing From Button', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .king-addons-quick-product-grid__add-to-cart + .added_to_cart' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_view_cart_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart',
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_view_cart_style_tabs');

        $this->start_controls_tab(
            'kng_view_cart_style_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_view_cart_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_view_cart_style_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_view_cart_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_view_cart_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-grid__cta .added_to_cart' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Set external query arguments from filters.
     *
     * @param array<string, mixed> $args Query arguments.
     *
     * @return void
     */
    public function set_external_query_args(array $args): void
    {
        $this->external_query_args = $args;
    }

    /**
     * Build query.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return WP_Query
     */
    protected function build_query(array $settings): WP_Query
    {
        $per_page = !empty($settings['kng_products_per_page']) ? (int) $settings['kng_products_per_page'] : 6;
        $per_page = min($per_page, 12);

        $orderby_setting = $settings['kng_orderby'] ?? 'date';
        $meta_key = $this->get_orderby_meta_key($orderby_setting);

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'order' => $settings['kng_order'] ?? 'DESC',
            'orderby' => $this->map_orderby($orderby_setting),
            'post_status' => 'publish',
        ];

        if ($meta_key) {
            $args['meta_key'] = $meta_key;
        }

        $args = $this->apply_product_filters($args, $settings);

        if (!empty($this->external_query_args)) {
            $args = $this->merge_query_parts($args, $this->external_query_args);
        }

        // Categories: gated for pro; ignored in free.

        return new WP_Query($args);
    }

    /**
     * Map orderby.
     *
     * @param string $orderby Orderby.
     *
     * @return string
     */
    protected function map_orderby(string $orderby): string
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
     * Return meta key for meta-based orderby.
     *
     * @param string $orderby Orderby value.
     *
     * @return string|null
     */
    protected function get_orderby_meta_key(string $orderby): ?string
    {
        $map = [
            'price' => '_price',
            'popularity' => 'total_sales',
            'rating' => '_wc_average_rating',
        ];

        return $map[$orderby] ?? null;
    }

    /**
     * Apply product query filters (include/exclude, on-sale, featured, in-stock).
     *
     * @param array<string, mixed> $args     Query args.
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<string, mixed>
     */
    protected function apply_product_filters(array $args, array $settings): array
    {
        $filter_args = [];

        $include_ids = $this->parse_id_list($settings['kng_include_products'] ?? '');
        $exclude_ids = $this->parse_id_list($settings['kng_exclude_products'] ?? '');

        $post_in = $include_ids;
        $post_in_filter_applied = !empty($include_ids);

        if (($settings['kng_on_sale'] ?? '') === 'yes') {
            $sale_ids = function_exists('wc_get_product_ids_on_sale') ? wc_get_product_ids_on_sale() : [];
            $post_in_filter_applied = true;
            $post_in = $post_in ? array_values(array_intersect($post_in, $sale_ids)) : $sale_ids;
        }

        if ($post_in_filter_applied) {
            $filter_args['post__in'] = !empty($post_in) ? $post_in : [0];
        }

        if (!empty($exclude_ids)) {
            $filter_args['post__not_in'] = $exclude_ids;
        }

        $tax_query = [];
        if (($settings['kng_featured_only'] ?? '') === 'yes') {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field' => 'name',
                'terms' => ['featured'],
                'operator' => 'IN',
            ];
        }

        if (!empty($tax_query)) {
            $filter_args['tax_query'] = $tax_query;
        }

        $meta_query = [];
        if (($settings['kng_in_stock'] ?? '') === 'yes') {
            $meta_query[] = [
                'key' => '_stock_status',
                'value' => 'instock',
            ];
        }

        if (!empty($meta_query)) {
            $filter_args['meta_query'] = $meta_query;
        }

        return $this->merge_query_parts($args, $filter_args);
    }

    /**
     * Parse a comma-separated list of IDs.
     *
     * @param string $raw Raw input.
     *
     * @return array<int, int>
     */
    protected function parse_id_list(string $raw): array
    {
        if ('' === trim($raw)) {
            return [];
        }

        $ids = array_filter(array_map('absint', array_map('trim', explode(',', $raw))));

        return array_values(array_unique($ids));
    }

    /**
     * Merge query arguments, preserving tax/meta queries and combining post__in/not_in.
     *
     * @param array<string, mixed> $args  Base query args.
     * @param array<string, mixed> $extra Extra query args.
     *
     * @return array<string, mixed>
     */
    protected function merge_query_parts(array $args, array $extra): array
    {
        $tax_query = $args['tax_query'] ?? [];
        $meta_query = $args['meta_query'] ?? [];
        $post_in = $args['post__in'] ?? [];
        $post_in_filter_applied = array_key_exists('post__in', $args);
        $post_not_in = $args['post__not_in'] ?? [];

        $extra_tax_query = $extra['tax_query'] ?? [];
        $extra_meta_query = $extra['meta_query'] ?? [];
        $extra_has_post_in = array_key_exists('post__in', $extra);
        $extra_post_in = $extra_has_post_in ? (array) $extra['post__in'] : [];
        $extra_post_not_in = $extra['post__not_in'] ?? [];

        unset($extra['tax_query'], $extra['meta_query'], $extra['post__in'], $extra['post__not_in']);

        $args = array_merge($args, $extra);

        if ($extra_has_post_in) {
            $post_in_filter_applied = true;
            $post_in = !empty($post_in)
                ? array_values(array_intersect($post_in, $extra_post_in))
                : $extra_post_in;
        }

        if ($post_in_filter_applied) {
            $post_in = array_values(array_unique(array_map('absint', (array) $post_in)));
            if (count($post_in) > 1 && in_array(0, $post_in, true)) {
                $post_in = array_values(array_diff($post_in, [0]));
            }
            if (empty($post_in)) {
                $post_in = [0];
            }
            $args['post__in'] = $post_in;
        }

        $post_not_in = array_merge((array) $post_not_in, (array) $extra_post_not_in);
        if (!empty($post_not_in)) {
            $post_not_in = array_values(array_unique(array_map('absint', $post_not_in)));
            $args['post__not_in'] = $post_not_in;
        }

        $tax_query = array_merge((array) $tax_query, (array) $extra_tax_query);
        if (!empty($tax_query)) {
            $args['tax_query'] = $this->normalize_query_relation($tax_query);
        }

        $meta_query = array_merge((array) $meta_query, (array) $extra_meta_query);
        if (!empty($meta_query)) {
            $args['meta_query'] = $this->normalize_query_relation($meta_query);
        }

        return $args;
    }

    /**
     * Ensure relation for tax/meta query arrays.
     *
     * @param array<int|string, mixed> $query Query array.
     *
     * @return array<int|string, mixed>
     */
    protected function normalize_query_relation(array $query): array
    {
        $conditions = array_filter($query, 'is_array');
        if (count($conditions) > 1 && empty($query['relation'])) {
            $query['relation'] = 'AND';
        }

        return $query;
    }

    /**
     * Render card.
     *
     * @param array<string, mixed> $settings Settings.
     * @param \WC_Product          $product  Product.
     *
     * @return void
     */
    protected function render_card(array $settings, \WC_Product $product): void
    {
        $card_classes = ['king-addons-quick-product-grid__card'];
        $animation = $settings['kng_hover_animation'] ?? 'none';
        if ($animation !== 'none') {
            $card_classes[] = 'is-anim-' . sanitize_html_class((string) $animation);
        }

        $show_rating = ($settings['kng_show_rating'] ?? 'yes') === 'yes';
        $show_price = ($settings['kng_show_price'] ?? 'yes') === 'yes';
        $show_excerpt = ($settings['kng_show_excerpt'] ?? 'yes') === 'yes';
        $show_button = ($settings['kng_show_add_to_cart'] ?? 'yes') === 'yes';
        $show_badge = ($settings['kng_show_badge'] ?? 'yes') === 'yes';
        $badge_text = !empty($settings['kng_badge_text']) ? (string) $settings['kng_badge_text'] : esc_html__('Sale', 'king-addons');
        $excerpt_length = !empty($settings['kng_excerpt_length']) ? max(1, (int) $settings['kng_excerpt_length']) : 16;

        ?>
        <div class="king-addons-quick-product-grid__item">
            <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                <div class="king-addons-quick-product-grid__media">
                    <a href="<?php the_permalink(); ?>" class="king-addons-quick-product-grid__link">
                        <?php echo $this->get_product_image_html($product, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                    <?php if ($show_badge && $product->is_on_sale()) : ?>
                        <span class="king-addons-quick-product-grid__badge"><?php echo esc_html($badge_text); ?></span>
                    <?php endif; ?>
                </div>

                <div class="king-addons-quick-product-grid__body">
                    <h3 class="king-addons-quick-product-grid__title">
                        <a href="<?php the_permalink(); ?>"><?php echo esc_html($product->get_name()); ?></a>
                    </h3>

                    <?php if ($show_rating && wc_review_ratings_enabled()) : ?>
                        <div class="king-addons-quick-product-grid__rating">
                            <?php echo wc_get_rating_html($product->get_average_rating()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_price) : ?>
                        <div class="king-addons-quick-product-grid__price">
                            <?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_excerpt) : ?>
                        <div class="king-addons-quick-product-grid__excerpt">
                            <?php echo wp_kses_post(wp_trim_words($product->get_short_description(), $excerpt_length)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_button) : ?>
                        <div class="king-addons-quick-product-grid__cta">
                            <?php echo $this->render_add_to_cart_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Get product image HTML with Elementor image size control.
     *
     * @param \WC_Product          $product  Product instance.
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function get_product_image_html(\WC_Product $product, array $settings): string
    {
        $image_id = $product->get_image_id();

        if ($image_id) {
            $image_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'kng_image_size', $image_id);
            if (!empty($image_html)) {
                return $image_html;
            }
        }

        $fallback_size = $settings['kng_image_size_size'] ?? 'medium';

        return $product->get_image($fallback_size);
    }

    /**
     * Render a controlled Add to Cart button without relying on Woo shortcode.
     *
     * @param \WC_Product $product Product instance.
     *
     * @return string
     */
    protected function render_add_to_cart_button(\WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="king-addons-quick-product-grid__add-to-cart is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'king-addons-quick-product-grid__add-to-cart',
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

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-quick-product-grid__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Wrapper classes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['king-addons-quick-product-grid'];
        $columns = $settings['kng_columns'] ?? 3;
        $classes[] = 'king-addons-quick-product-grid--cols-' . sanitize_html_class((string) $columns);
        return $classes;
    }

    /**
     * Wrapper inline style.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_wrapper_style(array $settings): string
    {
        $parts = [];
        if (isset($settings['kng_grid_gap']['size'])) {
            $parts[] = '--kng-quick-product-grid-gap:' . (float) $settings['kng_grid_gap']['size'] . ($settings['kng_grid_gap']['unit'] ?? 'px') . ';';
        }

        return implode(' ', $parts);
    }

    /**
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-product-grid', [
                'Category filter and higher limits',
                'Advanced hover animations and skins',
                'Advanced badges and ribbons',
                'Advanced CTA layouts',
            ]);
        }
    }
}
