<?php
/**
 * Single Product Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Single Product widget.
 */
class Single_Product extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-single-product';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Single Product', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-single-product';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        $deps = [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-single-product-script',
        ];

        if (class_exists('\WooCommerce') && function_exists('wp_script_is') && wp_script_is('wc-add-to-cart', 'registered')) {
            $deps[] = 'wc-add-to-cart';
        }

        return $deps;
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-single-product-style',
        ];
    }

    /**
     * Widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['woocommerce', 'product', 'single', 'cart', 'shop'];
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
        $this->register_content_controls();
        $this->register_interaction_controls();
        $this->register_style_layout_controls();
        $this->register_style_text_controls();
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
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
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
        $product_id = $this->resolve_product_id($settings);

        if (!$product_id) {
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $this->render_output($settings, $product);
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Product', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_use_current_product',
            [
                'label' => esc_html__('Use Current Product (Single)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_product_id',
            [
                'label' => esc_html__('Product ID (fallback)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'step' => 1,
                'condition' => [
                    'kng_use_current_product!' => 'yes',
                ],
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
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Short Description', 'king-addons'),
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
            'kng_show_add_to_cart',
            [
                'label' => esc_html__('Show Add to Cart', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout styles.
     *
     * @return void
     */
    protected function register_style_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_style_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-single-product',
            ]
        );

        $this->add_control(
            'kng_disable_card_shadow',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_disable_card_shadow_hover',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Card Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_border',
            [
                'label' => esc_html__('Card Border', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product' => 'border: 1px solid #e5e7eb;',
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
                'selector' => '{{WRAPPER}} .king-addons-single-product__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_price_typography',
                'selector' => '{{WRAPPER}} .king-addons-single-product__price',
            ]
        );

        $this->add_control(
            'kng_price_color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-single-product__excerpt',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__excerpt' => 'color: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .king-addons-single-product__button' => '--king-addons-atc-indicator-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .button',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .button:hover',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .button' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .king-addons-single-product__button + .added_to_cart' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_view_cart_typography',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart',
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart:hover',
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
                    '{{WRAPPER}} .king-addons-single-product__cta .added_to_cart' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Resolve product ID.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return int
     */
    protected function resolve_product_id(array $settings): int
    {
        $use_current = ($settings['kng_use_current_product'] ?? 'yes') === 'yes';
        if ($use_current && is_singular('product')) {
            $product_id = get_the_ID();
            if ($product_id) {
                return (int) $product_id;
            }
        }

        if (!empty($settings['kng_product_id'])) {
            return (int) $settings['kng_product_id'];
        }

        return 0;
    }

    /**
     * Render output.
     *
     * @param array<string, mixed> $settings Settings.
     * @param \WC_Product          $product  Product object.
     *
     * @return void
     */
    protected function render_output(array $settings, \WC_Product $product): void
    {
        $card_classes = ['king-addons-single-product'];
        $animation = $settings['kng_hover_animation'] ?? 'none';
        if ($animation !== 'none') {
            $card_classes[] = 'is-anim-' . sanitize_html_class((string) $animation);
        }

        $show_image = ($settings['kng_show_image'] ?? 'yes') === 'yes';
        $show_price = ($settings['kng_show_price'] ?? 'yes') === 'yes';
        $show_excerpt = ($settings['kng_show_excerpt'] ?? 'yes') === 'yes';
        $show_rating = ($settings['kng_show_rating'] ?? 'yes') === 'yes';
        $show_button = ($settings['kng_show_add_to_cart'] ?? 'yes') === 'yes';

        ?>
        <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
            <?php if ($show_image) : ?>
                <div class="king-addons-single-product__media">
                    <?php echo $product->get_image('medium'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <div class="king-addons-single-product__content">
                <h3 class="king-addons-single-product__title"><?php echo esc_html($product->get_title()); ?></h3>

                <?php if ($show_rating && wc_review_ratings_enabled()) : ?>
                    <div class="king-addons-single-product__rating">
                        <?php echo wc_get_rating_html($product->get_average_rating()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_price) : ?>
                    <div class="king-addons-single-product__price">
                        <?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_excerpt) : ?>
                    <div class="king-addons-single-product__excerpt">
                        <?php echo wp_kses_post(wpautop($product->get_short_description())); ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_button) : ?>
                    <div class="king-addons-single-product__cta">
                        <?php echo $this->render_add_to_cart_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
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
            return '<span class="king-addons-single-product__button is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'king-addons-single-product__button',
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

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-single-product__button-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'single-product', [
                'Image badges and sale labels',
                'Stock bar and countdown',
                'Quantity selector and icons',
                'Gallery thumbs and layouts',
            ]);
        }
    }
}







