<?php
/**
 * Woo Shortcode Checkout widget.
 * Renders the standard WooCommerce [woocommerce_checkout] shortcode.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays WooCommerce checkout using the standard shortcode.
 */
class Woo_Shortcode_Checkout extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_shortcode_checkout';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('WC Checkout', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-checkout';
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
        return ['woocommerce', 'checkout', 'shortcode', 'payment', 'order'];
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
                    esc_html__('WooCommerce Checkout Shortcode', 'king-addons'),
                    esc_html__('This widget renders the standard WooCommerce checkout page using the [woocommerce_checkout] shortcode. It displays the full checkout form with billing, shipping, payment methods, and order review.', 'king-addons')
                ),
                'content_classes' => 'elementor-panel-alert',
            ]
        );

        $this->end_controls_section();

        // =============================================
        // STYLE TAB
        // =============================================

        // Section Titles Style
        $this->start_controls_section(
            'section_style_headings',
            [
                'label' => esc_html__('Section Headings', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout h3' => 'color: {{VALUE}};',
                    '{{WRAPPER}} #order_review_heading' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'heading_typography',
                'selector' => '{{WRAPPER}} .woocommerce-checkout h3, {{WRAPPER}} #order_review_heading',
            ]
        );

        $this->add_responsive_control(
            'heading_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout h3' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} #order_review_heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Form Labels Style
        $this->start_controls_section(
            'section_style_labels',
            [
                'label' => esc_html__('Form Labels', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout form label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .woocommerce-checkout form label',
            ]
        );

        $this->add_control(
            'required_color',
            [
                'label' => esc_html__('Required Asterisk Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout form label .required' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Form Fields Style
        $this->start_controls_section(
            'section_style_fields',
            [
                'label' => esc_html__('Form Fields', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'field_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout input.input-text' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .woocommerce-checkout textarea' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .woocommerce-checkout select' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'field_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout input.input-text' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woocommerce-checkout textarea' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woocommerce-checkout select' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'field_placeholder_color',
            [
                'label' => esc_html__('Placeholder Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout input.input-text::placeholder' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .woocommerce-checkout textarea::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'field_border',
                'selector' => '{{WRAPPER}} .woocommerce-checkout input.input-text, {{WRAPPER}} .woocommerce-checkout textarea, {{WRAPPER}} .woocommerce-checkout select',
            ]
        );

        $this->add_responsive_control(
            'field_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout input.input-text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'field_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout input.input-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Order Review Table Style
        $this->start_controls_section(
            'section_style_order_review',
            [
                'label' => esc_html__('Order Review Table', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'order_review_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #order_review' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'order_review_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} #order_review' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'order_review_border',
                'selector' => '{{WRAPPER}} #order_review',
            ]
        );

        $this->add_responsive_control(
            'order_review_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} #order_review' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Payment Methods Style
        $this->start_controls_section(
            'section_style_payment',
            [
                'label' => esc_html__('Payment Methods', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'payment_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #payment' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'payment_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #payment .wc_payment_method label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'payment_label_typography',
                'selector' => '{{WRAPPER}} #payment .wc_payment_method label',
            ]
        );

        $this->add_responsive_control(
            'payment_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} #payment' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'payment_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} #payment' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'payment_border',
                'selector' => '{{WRAPPER}} #payment',
            ]
        );

        $this->end_controls_section();

        // Order Table Style
        $this->start_controls_section(
            'section_style_order_table',
            [
                'label' => esc_html__('Order Table', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'order_table_header_color',
            [
                'label' => esc_html__('Header Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout-review-order-table th' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'order_table_header_bg',
            [
                'label' => esc_html__('Header Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout-review-order-table th' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'order_table_cell_color',
            [
                'label' => esc_html__('Cell Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout-review-order-table td' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'order_table_border',
                'selector' => '{{WRAPPER}} .woocommerce-checkout-review-order-table, {{WRAPPER}} .woocommerce-checkout-review-order-table th, {{WRAPPER}} .woocommerce-checkout-review-order-table td',
            ]
        );

        $this->add_responsive_control(
            'order_table_padding',
            [
                'label' => esc_html__('Cell Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout-review-order-table th' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout-review-order-table td' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Form Rows Style
        $this->start_controls_section(
            'section_style_form_rows',
            [
                'label' => esc_html__('Form Rows', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'form_row_spacing',
            [
                'label' => esc_html__('Row Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout .form-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_row_gap',
            [
                'label' => esc_html__('Column Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-checkout .form-row-first' => 'padding-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .woocommerce-checkout .form-row-last' => 'padding-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Place Order Button Style
        $this->start_controls_section(
            'section_style_place_order',
            [
                'label' => esc_html__('Place Order Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('place_order_tabs');

        $this->start_controls_tab(
            'place_order_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'place_order_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'place_order_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'place_order_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'place_order_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #place_order:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'place_order_hover_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} #place_order:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'place_order_typography',
                'selector' => '{{WRAPPER}} #place_order',
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'place_order_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'place_order_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'place_order_border',
                'selector' => '{{WRAPPER}} #place_order',
            ]
        );

        $this->add_responsive_control(
            'place_order_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'place_order_full_width',
            [
                'label' => esc_html__('Full Width', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'selectors' => [
                    '{{WRAPPER}} #place_order' => 'width: 100%;',
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
    protected function render(): void
    {
        if (!class_exists('WooCommerce')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">' . esc_html__('WooCommerce is required for this widget.', 'king-addons') . '</div>';
            }
            return;
        }

        // In editor mode, ensure WC session and cart are available
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            if (is_null(WC()->cart)) {
                wc_load_cart();
            }
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo do_shortcode('[woocommerce_checkout]');
    }
}
