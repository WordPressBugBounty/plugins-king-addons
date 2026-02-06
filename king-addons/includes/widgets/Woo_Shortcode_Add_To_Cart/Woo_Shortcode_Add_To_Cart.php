<?php
/**
 * Woo Shortcode Add To Cart widget.
 * Renders an add-to-cart button using [add_to_cart] shortcode.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays an add-to-cart button using the standard shortcode.
 */
class Woo_Shortcode_Add_To_Cart extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_shortcode_add_to_cart';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('WC Add To Cart Button', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-cart-medium';
    }

    /**
     * Get widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<int,string>
     */
    public function get_keywords(): array
    {
        return ['woocommerce', 'add to cart', 'button', 'shortcode', 'buy', 'purchase'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Product Selection', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'product_source',
            [
                'label' => esc_html__('Product Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'current',
                'options' => [
                    'current' => esc_html__('Current Product (Dynamic)', 'king-addons'),
                    'id' => esc_html__('Specific Product ID', 'king-addons'),
                    'sku' => esc_html__('Specific Product SKU', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'current_info',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div style="padding: 10px; background: #e7f3e7; border-left: 4px solid #46b450; border-radius: 4px; color: #1e4620;">' .
                    esc_html__('Uses the current product from context. Works on single product pages, product loops, and Woo Builder templates.', 'king-addons') .
                    '</div>',
                'condition' => [
                    'product_source' => 'current',
                ],
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label' => esc_html__('Product ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'description' => esc_html__('Enter the numeric ID of the product.', 'king-addons'),
                'condition' => [
                    'product_source' => 'id',
                ],
            ]
        );

        $this->add_control(
            'product_sku',
            [
                'label' => esc_html__('Product SKU', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('e.g., ABC123', 'king-addons'),
                'description' => esc_html__('Enter the SKU of the product.', 'king-addons'),
                'condition' => [
                    'product_source' => 'sku',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_display',
            [
                'label' => esc_html__('Display Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label' => esc_html__('Show Price', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'true',
                'default' => 'true',
            ]
        );

        $this->add_control(
            'quantity',
            [
                'label' => esc_html__('Default Quantity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 1,
            ]
        );

        $this->add_control(
            'class',
            [
                'label' => esc_html__('Additional CSS Class', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('e.g., my-custom-class', 'king-addons'),
                'description' => esc_html__('Add custom CSS class to the button wrapper.', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_info',
            [
                'label' => esc_html__('Info', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'info_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    '<div style="padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px; color: #1d4ed8;">
                        <strong>%s</strong><br><br>%s
                    </div>',
                    esc_html__('WooCommerce Add To Cart Shortcode', 'king-addons'),
                    esc_html__('This widget renders an add-to-cart button for a product using the [add_to_cart] shortcode. Use "Current Product" for dynamic templates or specify a product by ID/SKU.', 'king-addons')
                ),
                'content_classes' => 'elementor-panel-alert',
            ]
        );

        $this->end_controls_section();

        // =============================================
        // STYLE TAB
        // =============================================

        // Container Style
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .product',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Price Style
        $this->start_controls_section(
            'section_style_price',
            [
                'label' => esc_html__('Price', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .product .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .product .price, {{WRAPPER}} .product .woocommerce-Price-amount',
            ]
        );

        $this->add_responsive_control(
            'price_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product .price' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'section_style_button',
            [
                'label' => esc_html__('Add to Cart Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_tabs');

        $this->start_controls_tab(
            'button_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .product a.button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .product a.button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .product a.button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .product a.button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .product a.add_to_cart_button, {{WRAPPER}} .product a.button',
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .product a.button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .product a.button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .product a.add_to_cart_button, {{WRAPPER}} .product a.button',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .product a.add_to_cart_button, {{WRAPPER}} .product a.button',
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .product a.button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_full_width',
            [
                'label' => esc_html__('Full Width', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'selectors' => [
                    '{{WRAPPER}} .product a.add_to_cart_button' => 'width: 100%; display: block; text-align: center;',
                    '{{WRAPPER}} .product a.button' => 'width: 100%; display: block; text-align: center;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get current product from context.
     *
     * @return \WC_Product|null
     */
    protected function get_current_product(): ?\WC_Product
    {
        global $product, $post;

        // Try global $product first
        if ($product instanceof \WC_Product) {
            return $product;
        }

        // Try from post
        if ($post && 'product' === get_post_type($post->ID)) {
            $prod = wc_get_product($post->ID);
            if ($prod instanceof \WC_Product) {
                return $prod;
            }
        }

        // In editor, try preview product
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editor()) {
            $preview = Woo_Context::setup_preview_product();
            if ($preview instanceof \WC_Product) {
                return $preview;
            }
        }

        return null;
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        if (!class_exists('WooCommerce')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('WooCommerce is required for this widget.', 'king-addons') . '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $product_source = !empty($settings['product_source']) ? $settings['product_source'] : 'current';

        $attrs = [];

        // Product ID or SKU
        if ($product_source === 'current') {
            $current_product = $this->get_current_product();
            if ($current_product) {
                $attrs[] = 'id="' . $current_product->get_id() . '"';
            } else {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<div class="king-addons-woo-builder-notice">' . esc_html__('No product found in current context. Please create at least one WooCommerce product for preview.', 'king-addons') . '</div>';
                }
                return;
            }
        } elseif ($product_source === 'id' && !empty($settings['product_id'])) {
            $attrs[] = 'id="' . absint($settings['product_id']) . '"';
        } elseif ($product_source === 'sku' && !empty($settings['product_sku'])) {
            $attrs[] = 'sku="' . esc_attr($settings['product_sku']) . '"';
        } else {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Please select a product source or enter a product ID/SKU.', 'king-addons') . '</div>';
            }
            return;
        }

        // Show price
        if (empty($settings['show_price'])) {
            $attrs[] = 'show_price="false"';
        }

        // Quantity
        if (!empty($settings['quantity']) && absint($settings['quantity']) > 1) {
            $attrs[] = 'quantity="' . absint($settings['quantity']) . '"';
        }

        // Custom class
        if (!empty($settings['class'])) {
            $attrs[] = 'class="' . esc_attr($settings['class']) . '"';
        }

        $shortcode = '[add_to_cart ' . implode(' ', $attrs) . ']';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo do_shortcode($shortcode);
    }
}
