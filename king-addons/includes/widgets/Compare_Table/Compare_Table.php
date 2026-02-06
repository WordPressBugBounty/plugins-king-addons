<?php
/**
 * Compare Products Table Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WC_Product;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a WooCommerce products compare table.
 */
class Compare_Table extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-compare-table';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Compare Products Table', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-compare-table';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-compare-table-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-compare-table-script',
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
        return ['woocommerce', 'compare', 'table', 'product'];
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
        $this->register_display_controls();
        $this->register_layout_controls();
        $this->register_style_header_controls();
        $this->register_style_cell_controls();
        $this->register_style_button_controls();
        $this->register_style_view_cart_controls();
        $this->register_pro_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register Pro-only controls placeholder.
     *
     * The Pro version overrides this method to add premium controls without
     * overriding the full `register_controls()` flow.
     *
     * @return void
     */
    public function register_pro_controls(): void
    {
        // Intentionally empty in Free.
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
        $products = $this->get_products($settings);

        if (empty($products)) {
            return;
        }

        $rows = $this->get_rows_config($settings);
        $wrapper_attrs = $this->get_wrapper_attributes($settings);

        ?>
        <div class="king-addons-compare-table" <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-compare-table__scroll">
                <table class="king-addons-compare-table__table">
                    <thead class="king-addons-compare-table__head">
                        <tr class="king-addons-compare-table__row king-addons-compare-table__row--head">
                            <th class="king-addons-compare-table__cell king-addons-compare-table__cell--label">
                                <?php echo esc_html__('Feature', 'king-addons'); ?>
                            </th>
                            <?php foreach ($products as $product) : ?>
                                <th class="king-addons-compare-table__cell king-addons-compare-table__cell--product">
                                    <div class="king-addons-compare-table__product-head">
                                        <div class="king-addons-compare-table__product-thumb">
                                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                                                <?php echo $product->get_image('thumbnail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                            </a>
                                        </div>
                                        <div class="king-addons-compare-table__product-title">
                                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="king-addons-compare-table__body">
                        <?php foreach ($rows as $row_id => $row_label) : ?>
                            <tr class="king-addons-compare-table__row" data-row-id="<?php echo esc_attr($row_id); ?>">
                                <th class="king-addons-compare-table__cell king-addons-compare-table__cell--label">
                                    <?php echo esc_html($row_label); ?>
                                </th>
                                <?php foreach ($products as $product) : ?>
                                    <td class="king-addons-compare-table__cell king-addons-compare-table__cell--value">
                                        <?php echo $this->get_cell_content($row_id, $product, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Products', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_product_ids',
            [
                'label' => esc_html__('Product IDs', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('12, 34, 56', 'king-addons'),
                'description' => esc_html__('Comma-separated product IDs to compare.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_products_limit',
            [
                'label' => esc_html__('Max Products', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 2,
                'max' => 4,
                'step' => 1,
                'default' => 3,
                'description' => esc_html__('Free version limited to 4 products.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_categories',
            [
                'label' => sprintf(__('Categories %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated slugs (Pro).', 'king-addons'),
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Display toggles.
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
            'kng_show_image',
            [
                'label' => esc_html__('Show Image', 'king-addons'),
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
            'kng_show_rating',
            [
                'label' => esc_html__('Show Rating', 'king-addons'),
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
            'kng_show_sku',
            [
                'label' => esc_html__('Show SKU', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_stock',
            [
                'label' => esc_html__('Show Stock', 'king-addons'),
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
            'kng_hide_equal_rows',
            [
                'label' => sprintf(__('Hide Identical Rows %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => 'king-addons-pro-control no-distance',
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

        $this->add_control(
            'kng_cell_alignment',
            [
                'label' => esc_html__('Cell Alignment', 'king-addons'),
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
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_cell_padding',
            [
                'label' => esc_html__('Cell Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Header styles.
     *
     * @return void
     */
    protected function register_style_header_controls(): void
    {
        $this->start_controls_section(
            'kng_style_header_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Header', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_header_typography',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__head .king-addons-compare-table__cell',
            ]
        );

        $this->add_control(
            'kng_header_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__head .king-addons-compare-table__cell' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_header_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__head .king-addons-compare-table__row' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_header_border',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__head .king-addons-compare-table__cell',
            ]
        );

        $this->add_control(
            'kng_header_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__row--head .king-addons-compare-table__cell' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_header_shadow',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__head',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Cell styles.
     *
     * @return void
     */
    protected function register_style_cell_controls(): void
    {
        $this->start_controls_section(
            'kng_style_cell_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Body', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_body_typography',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__body .king-addons-compare-table__cell',
            ]
        );

        $this->add_control(
            'kng_body_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__body .king-addons-compare-table__cell' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_body_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__body .king-addons-compare-table__row' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_body_stripe_bg',
            [
                'label' => esc_html__('Stripe Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__row:nth-child(2n) .king-addons-compare-table__cell' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_body_border',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell',
            ]
        );

        $this->add_control(
            'kng_body_radius',
            [
                'label' => esc_html__('Cell Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell' => 'border-radius: {{SIZE}}{{UNIT}};',
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

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .king-addons-compare-table__add-to-cart' => '--king-addons-atc-indicator-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .button',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .button:hover',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .button' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .king-addons-compare-table__add-to-cart + .added_to_cart' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_view_cart_typography',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart',
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart:hover',
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
                    '{{WRAPPER}} .king-addons-compare-table__cell .added_to_cart' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro notice section.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'compare-table', [
                'Unlimited products and category filters',
                'Hide identical rows and sticky header',
                'Extra attributes (dimensions, weight, taxonomy)',
                'Highlight differences and advanced styling',
            ]);
        }
    }

    /**
     * Collect products based on settings.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, WC_Product>
     */
    protected function get_products(array $settings): array
    {
        $limit = isset($settings['kng_products_limit']) ? (int) $settings['kng_products_limit'] : 3;
        $limit = min(max($limit, 2), 4);

        $ids_raw = $settings['kng_product_ids'] ?? '';
        $ids = array_filter(array_map('absint', explode(',', (string) $ids_raw)));
        $ids = array_slice($ids, 0, $limit);

        if (empty($ids)) {
            return [];
        }

        $query = new WP_Query(
            [
                'post_type' => 'product',
                'post__in' => $ids,
                'posts_per_page' => $limit,
                'orderby' => 'post__in',
                'post_status' => 'publish',
            ]
        );

        $products = [];

        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if ($product) {
                $products[] = $product;
            }
        }

        wp_reset_postdata();

        return $products;
    }

    /**
     * Row labels map.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, string>
     */
    protected function get_rows_config(array $settings): array
    {
        return $this->get_rows_config_base($settings);
    }

    /**
     * Base row labels map.
     *
     * This method exists to allow the Pro version to extend row configuration
     * without calling `parent::` methods.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, string>
     */
    protected function get_rows_config_base(array $settings): array
    {
        $rows = [];

        if (($settings['kng_show_image'] ?? 'yes') === 'yes') {
            $rows['image'] = esc_html__('Image', 'king-addons');
        }

        if (($settings['kng_show_price'] ?? 'yes') === 'yes') {
            $rows['price'] = esc_html__('Price', 'king-addons');
        }

        if (($settings['kng_show_rating'] ?? 'yes') === 'yes') {
            $rows['rating'] = esc_html__('Rating', 'king-addons');
        }

        if (($settings['kng_show_sku'] ?? 'yes') === 'yes') {
            $rows['sku'] = esc_html__('SKU', 'king-addons');
        }

        if (($settings['kng_show_stock'] ?? 'yes') === 'yes') {
            $rows['stock'] = esc_html__('Stock', 'king-addons');
        }

        if (($settings['kng_show_excerpt'] ?? 'yes') === 'yes') {
            $rows['excerpt'] = esc_html__('Short Description', 'king-addons');
        }

        if (($settings['kng_show_add_to_cart'] ?? 'yes') === 'yes') {
            $rows['add_to_cart'] = esc_html__('Add to Cart', 'king-addons');
        }

        return $rows;
    }

    /**
     * Build cell content by row.
     *
     * @param string     $row_id   Row id.
     * @param WC_Product $product  Product.
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_cell_content(string $row_id, WC_Product $product, array $settings): string
    {
        return $this->get_cell_content_base($row_id, $product, $settings);
    }

    /**
     * Base cell content renderer.
     *
     * This method exists to allow the Pro version to extend row rendering
     * without calling `parent::` methods.
     *
     * @param string     $row_id   Row id.
     * @param WC_Product $product  Product.
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_cell_content_base(string $row_id, WC_Product $product, array $settings): string
    {
        switch ($row_id) {
            case 'image':
                return $product->get_image('woocommerce_thumbnail');
            case 'price':
                return wp_kses_post($product->get_price_html());
            case 'rating':
                if (wc_review_ratings_enabled()) {
                    return (string) wc_get_rating_html($product->get_average_rating());
                }
                return '';
            case 'sku':
                return $product->get_sku() ? esc_html($product->get_sku()) : esc_html__('N/A', 'king-addons');
            case 'stock':
                return $product->is_in_stock()
                    ? '<span class="king-addons-compare-table__stock is-in">' . esc_html__('In stock', 'king-addons') . '</span>'
                    : '<span class="king-addons-compare-table__stock is-out">' . esc_html__('Out of stock', 'king-addons') . '</span>';
            case 'excerpt':
                return wp_kses_post(wp_trim_words($product->get_short_description(), 20));
            case 'add_to_cart':
                return $this->render_add_to_cart_button($product);
            default:
                return '';
        }
    }

    /**
     * Render a controlled Add to Cart button without Woo shortcode.
     *
     * @param WC_Product $product Product instance.
     *
     * @return string
     */
    protected function render_add_to_cart_button(WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="king-addons-compare-table__add-to-cart is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'king-addons-compare-table__add-to-cart',
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

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-compare-table__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Wrapper data attributes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_wrapper_attributes(array $settings): string
    {
        $attrs = [
            'data-hide-equal' => 'no',
            'data-sticky-header' => 'no',
        ];

        $compiled = [];
        foreach ($attrs as $key => $value) {
            $compiled[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $compiled);
    }
}







