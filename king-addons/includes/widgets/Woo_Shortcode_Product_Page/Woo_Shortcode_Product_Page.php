<?php
/**
 * Woo Shortcode Product Page widget.
 * Renders a full single product page using [product_page] shortcode.
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
 * Displays a single product page using the standard shortcode.
 */
class Woo_Shortcode_Product_Page extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_shortcode_product_page';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('WC Product Page', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-single-product';
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
        return ['woocommerce', 'product', 'page', 'single', 'shortcode', 'sku'];
    }

    /**
     * Get script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        // Ensure WooCommerce gallery scripts are loaded
        return ['wc-single-product', 'flexslider', 'zoom', 'photoswipe-ui-default'];
    }

    /**
     * Get style dependencies.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        // Ensure WooCommerce gallery styles are loaded
        return ['photoswipe-default-skin', 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen'];
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
                    esc_html__('Uses the current product from context. Works on single product pages, product archives, and Woo Builder templates.', 'king-addons') .
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
                    esc_html__('WooCommerce Product Page Shortcode', 'king-addons'),
                    esc_html__('This widget renders a full single product page using the [product_page] shortcode. Use "Current Product" for dynamic templates or select a specific product by ID/SKU.', 'king-addons')
                ),
                'content_classes' => 'elementor-panel-alert',
            ]
        );

        $this->end_controls_section();

        // =============================================
        // STYLE TAB
        // =============================================

        // Product Title Style
        $this->start_controls_section(
            'section_style_title',
            [
                'label' => esc_html__('Product Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product_title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .product_title',
            ]
        );

        $this->add_responsive_control(
            'title_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product_title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .product .price ins' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sale_price_color',
            [
                'label' => esc_html__('Sale Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .price ins .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'regular_price_color',
            [
                'label' => esc_html__('Regular Price Color (Strikethrough)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .price del' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .product .price',
            ]
        );

        $this->end_controls_section();

        // Short Description Style
        $this->start_controls_section(
            'section_style_short_desc',
            [
                'label' => esc_html__('Short Description', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'short_desc_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .woocommerce-product-details__short-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'short_desc_typography',
                'selector' => '{{WRAPPER}} .product .woocommerce-product-details__short-description',
            ]
        );

        $this->end_controls_section();

        // Gallery Style
        $this->start_controls_section(
            'section_style_gallery',
            [
                'label' => esc_html__('Product Gallery', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'gallery_border',
                'selector' => '{{WRAPPER}} .woocommerce-product-gallery__image img',
            ]
        );

        $this->add_responsive_control(
            'gallery_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-product-gallery__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'gallery_box_shadow',
                'selector' => '{{WRAPPER}} .woocommerce-product-gallery__image img',
            ]
        );

        $this->end_controls_section();

        // Add to Cart Button Style
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
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .product .single_add_to_cart_button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .single_add_to_cart_button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .product .single_add_to_cart_button',
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
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .product .single_add_to_cart_button',
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_full_width',
            [
                'label' => esc_html__('Full Width', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'selectors' => [
                    '{{WRAPPER}} .product .single_add_to_cart_button' => 'width: 100%;',
                ],
            ]
        );

        $this->end_controls_section();

        // Quantity Input Style
        $this->start_controls_section(
            'section_style_quantity',
            [
                'label' => esc_html__('Quantity Input', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'quantity_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .quantity .qty' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'quantity_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .quantity .qty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'quantity_border',
                'selector' => '{{WRAPPER}} .product .quantity .qty',
            ]
        );

        $this->add_responsive_control(
            'quantity_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product .quantity .qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'quantity_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product .quantity .qty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'quantity_width',
            [
                'label' => esc_html__('Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 150,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .product .quantity .qty' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'quantity_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product .quantity' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Tabs Style
        $this->start_controls_section(
            'section_style_tabs',
            [
                'label' => esc_html__('Product Tabs', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'tabs_heading_color',
            [
                'label' => esc_html__('Tab Heading Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_active_heading_color',
            [
                'label' => esc_html__('Active Tab Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li.active a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_bg_color',
            [
                'label' => esc_html__('Tab Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_active_bg_color',
            [
                'label' => esc_html__('Active Tab Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'tabs_typography',
                'selector' => '{{WRAPPER}} .woocommerce-tabs ul.tabs li a',
            ]
        );

        $this->add_control(
            'tabs_content_color',
            [
                'label' => esc_html__('Tab Content Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs .panel' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'tabs_padding',
            [
                'label' => esc_html__('Tab Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'tabs_content_padding',
            [
                'label' => esc_html__('Content Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs .panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'tabs_border',
                'selector' => '{{WRAPPER}} .woocommerce-tabs ul.tabs, {{WRAPPER}} .woocommerce-tabs .panel',
            ]
        );

        $this->add_responsive_control(
            'tabs_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-tabs ul.tabs li' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Meta Info Style
        $this->start_controls_section(
            'section_style_meta',
            [
                'label' => esc_html__('Product Meta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'meta_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product_meta > span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'meta_value_color',
            [
                'label' => esc_html__('Value Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product_meta > span a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .product_meta > span span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'meta_typography',
                'selector' => '{{WRAPPER}} .product_meta',
            ]
        );

        $this->add_responsive_control(
            'meta_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product_meta > span' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Sale Badge Style
        $this->start_controls_section(
            'section_style_sale_badge',
            [
                'label' => esc_html__('Sale Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'sale_badge_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .onsale' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sale_badge_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product .onsale' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'sale_badge_typography',
                'selector' => '{{WRAPPER}} .product .onsale',
            ]
        );

        $this->add_responsive_control(
            'sale_badge_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product .onsale' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'sale_badge_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product .onsale' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $shortcode = '[product_page';
        $product_id = null;

        if ($product_source === 'current') {
            $current_product = $this->get_current_product();
            if ($current_product) {
                $product_id = $current_product->get_id();
                $shortcode .= ' id="' . $product_id . '"';
            } else {
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<div class="king-addons-woo-builder-notice">' . esc_html__('No product found in current context. Please create at least one WooCommerce product for preview.', 'king-addons') . '</div>';
                }
                return;
            }
        } elseif ($product_source === 'id' && !empty($settings['product_id'])) {
            $shortcode .= ' id="' . absint($settings['product_id']) . '"';
        } elseif ($product_source === 'sku' && !empty($settings['product_sku'])) {
            $shortcode .= ' sku="' . esc_attr($settings['product_sku']) . '"';
        } else {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Please select a product source or enter a product ID/SKU.', 'king-addons') . '</div>';
            }
            return;
        }

        $shortcode .= ']';

        // Ensure WooCommerce product gallery scripts and styles are enqueued
        if (function_exists('wc_enqueue_js')) {
            // Enqueue gallery scripts
            wp_enqueue_script('wc-single-product');
            wp_enqueue_script('flexslider');
            wp_enqueue_script('zoom');
            wp_enqueue_script('photoswipe');
            wp_enqueue_script('photoswipe-ui-default');
            
            // Enqueue gallery styles  
            wp_enqueue_style('photoswipe');
            wp_enqueue_style('photoswipe-default-skin');
            wp_enqueue_style('woocommerce-general');
            wp_enqueue_style('woocommerce-layout');
        }

        // Add theme support for gallery features if not already set
        if (!current_theme_supports('wc-product-gallery-zoom')) {
            add_theme_support('wc-product-gallery-zoom');
        }
        if (!current_theme_supports('wc-product-gallery-lightbox')) {
            add_theme_support('wc-product-gallery-lightbox');
        }
        if (!current_theme_supports('wc-product-gallery-slider')) {
            add_theme_support('wc-product-gallery-slider');
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo do_shortcode($shortcode);

        // In editor mode, add inline script to initialize gallery
        if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Initialize WooCommerce product gallery
                if (typeof wc_single_product_params !== 'undefined') {
                    $('.woocommerce-product-gallery').each(function() {
                        $(this).trigger('wc-product-gallery-before-init', [this, wc_single_product_params]);
                        $(this).wc_product_gallery(wc_single_product_params);
                        $(this).trigger('wc-product-gallery-after-init', [this, wc_single_product_params]);
                    });
                }
                // Fallback initialization
                if (typeof $.fn.wc_product_gallery !== 'undefined') {
                    $('.woocommerce-product-gallery:not(.woocommerce-product-gallery--initialized)').wc_product_gallery();
                }
                // Initialize flexslider if available
                if (typeof $.fn.flexslider !== 'undefined') {
                    $('.woocommerce-product-gallery .flex-viewport').length || $('.woocommerce-product-gallery__wrapper').flexslider({
                        animation: 'slide',
                        animationLoop: false,
                        controlNav: 'thumbnails',
                        selector: '.woocommerce-product-gallery__image'
                    });
                }
            });
            </script>
            <?php
        }
    }
}
