<?php
/**
 * Woo Product Stock widget.
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
 * Displays product stock status.
 */
class Woo_Product_Stock extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_stock';
    }

    public function get_title(): string
    {
        return esc_html__('Product Stock', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-check-circle';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-stock-style'];
    }

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
            'text_in_stock',
            [
                'label' => esc_html__('In stock text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('In stock', 'king-addons'),
            ]
        );

        $this->add_control(
            'text_out_stock',
            [
                'label' => esc_html__('Out of stock text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Out of stock', 'king-addons'),
            ]
        );

        $this->add_control(
            'text_on_backorder',
            [
                'label' => sprintf(__('On backorder text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Available on backorder', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_quantity',
            [
                'label' => sprintf(__('Show quantity %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_status_icon',
            [
                'label' => sprintf(__('Show status icon %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'low_stock_threshold',
            [
                'label' => sprintf(__('Low stock threshold %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'description' => esc_html__('Show low stock message when below threshold.', 'king-addons'),
            ]
        );

        $this->add_control(
            'text_low_stock',
            [
                'label' => esc_html__('Low stock text (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Only {qty} left', 'king-addons'),
            ]
        );

        $this->add_control(
            'hide_if_in_stock',
            [
                'label' => sprintf(__('Hide if in stock %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
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
                'selector' => '{{WRAPPER}} .ka-woo-product-stock',
            ]
        );

        $this->add_control(
            'color_in',
            [
                'label' => esc_html__('Color: In stock', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-stock--in' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_low',
            [
                'label' => esc_html__('Color: Low stock (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-stock--low' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_out',
            [
                'label' => esc_html__('Color: Out of stock', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-stock--out' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_backorder',
            [
                'label' => sprintf(__('Color: Backorder %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-stock--backorder' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .ka-woo-product-stock' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $availability = $product->get_availability();
        $stock_status = $availability['class'] ?? '';

        $is_in_stock = $product->is_in_stock();
        $qty = $product->get_stock_quantity();
        $backorders = $product->get_backorders();

        if (!empty($settings['hide_if_in_stock']) && $can_pro && $is_in_stock && empty($settings['low_stock_threshold'])) {
            return;
        }

        $text = $settings['text_in_stock'] ?? esc_html__('In stock', 'king-addons');
        $class = 'ka-woo-product-stock ka-woo-product-stock--in';
        $icon = '';
        if (!empty($settings['show_status_icon']) && $can_pro) {
            $icon = '<span class="ka-woo-product-stock__icon" aria-hidden="true"></span>';
        }

        if (!$is_in_stock) {
            $text = $settings['text_out_stock'] ?? esc_html__('Out of stock', 'king-addons');
            $class = 'ka-woo-product-stock ka-woo-product-stock--out';
        } elseif ($product->backorders_allowed() && !empty($settings['text_on_backorder']) && $can_pro) {
            $text = $settings['text_on_backorder'];
            $class = 'ka-woo-product-stock ka-woo-product-stock--backorder';
        }

        if ($is_in_stock && $can_pro && !empty($settings['low_stock_threshold']) && is_numeric($qty)) {
            $threshold = (int) $settings['low_stock_threshold'];
            if ($threshold > 0 && $qty <= $threshold) {
                $low_text = $settings['text_low_stock'] ?: 'Only {qty} left';
                $low_text = str_replace('{qty}', (string) $qty, $low_text);
                $text = $low_text;
                $class = 'ka-woo-product-stock ka-woo-product-stock--low';
            }
        }

        if (!empty($settings['show_quantity']) && $can_pro && $is_in_stock && is_numeric($qty) && empty($settings['low_stock_threshold'])) {
            $text .= ' (' . (int) $qty . ')';
        }

        echo '<div class="' . esc_attr($class) . '">' . $icon . esc_html($text) . '</div>';
    }
}







