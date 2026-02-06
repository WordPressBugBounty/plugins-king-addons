<?php
/**
 * Woo Product Price widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays WooCommerce product price.
 */
class Woo_Product_Price extends Abstract_Single_Widget
{
    /**
     * Get widget name.
     */
    public function get_name(): string
    {
        return 'woo_product_price';
    }

    /**
     * Get widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Product Price', 'king-addons');
    }

    /**
     * Get widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-product-price';
    }

    /**
     * Get categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-price-style'];
    }

    /**
     * Controls.
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_mode',
            [
                'label' => esc_html__('Display', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'standard' => esc_html__('Standard (WooCommerce)', 'king-addons'),
                    'regular_only' => sprintf(__('Regular price only %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'sale_only' => sprintf(__('Sale price only %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'standard',
            ]
        );

        $this->add_control(
            'show_discount',
            [
                'label' => sprintf(__('Show discount percent %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'discount_format',
            [
                'label' => sprintf(__('Discount format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => '-{percent}%',
                'description' => esc_html__('Use {percent} placeholder.', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .ka-woo-product-price',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price .price, {{WRAPPER}} .ka-woo-product-price .price .amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sale_color',
            [
                'label' => esc_html__('Sale Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price .price ins .amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'regular_color',
            [
                'label' => esc_html__('Regular Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price .price del .amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'discount_color',
            [
                'label' => esc_html__('Discount Badge Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price__discount' => 'color: {{VALUE}}; background: rgba(0,0,0,0.04);',
                ],
                'condition' => [
                    'show_discount' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget.
     */
    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $display_mode = $settings['display_mode'] ?? 'standard';
        $can_pro = king_addons_can_use_pro();

        if ($display_mode !== 'standard' && !$can_pro) {
            $display_mode = 'standard';
        }

        $regular_raw = $product->get_regular_price();
        $sale_raw = $product->get_sale_price();
        // For variable products Woo returns min price; ensure float casting.
        $regular = $regular_raw !== '' ? (float) $regular_raw : null;
        $sale = $sale_raw !== '' ? (float) $sale_raw : null;

        $price_html = $product->get_price_html();
        $regular_html = $regular ? wc_price($regular) : '';
        $sale_html = $sale ? wc_price($sale) : '';

        if ('regular_only' === $display_mode) {
            $price_html = $regular_html ?: $price_html;
        } elseif ('sale_only' === $display_mode) {
            $price_html = $sale_html ?: $price_html;
        } elseif ($product->is_on_sale() && $regular_html && $sale_html) {
            $price_html = '<span class="ka-woo-product-price__regular">' . $regular_html . '</span><span class="ka-woo-product-price__sale">' . $sale_html . '</span>';
        }

        $discount_html = '';
        if (!empty($settings['show_discount']) && $can_pro && $product->is_on_sale()) {
            $regular_for_calc = $regular;
            $sale_for_calc = $sale;
            if ($product->is_type('variable')) {
                $regular_for_calc = (float) $product->get_variation_regular_price('min');
                $sale_for_calc = (float) $product->get_variation_sale_price('min');
            }
            if ($regular_for_calc > 0 && $sale_for_calc > 0 && $sale_for_calc < $regular_for_calc) {
                $percent = max(0, round((($regular_for_calc - $sale_for_calc) / $regular_for_calc) * 100));
                $format = $settings['discount_format'] ?: '-{percent}%';
                $discount_html = str_replace('{percent}', (string) $percent, $format);
                $discount_html = '<span class="ka-woo-product-price__discount">' . esc_html($discount_html) . '</span>';
            }
        }

        echo '<div class="ka-woo-product-price">';
        echo wp_kses_post($price_html);
        echo $discount_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}







