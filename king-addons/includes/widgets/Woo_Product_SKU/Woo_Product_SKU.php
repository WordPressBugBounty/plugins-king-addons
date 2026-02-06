<?php
/**
 * Woo Product SKU widget.
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
 * Displays product SKU.
 */
class Woo_Product_SKU extends Abstract_Single_Widget
{
    /**
     * Widget name.
     */
    public function get_name(): string
    {
        return 'woo_product_sku';
    }

    /**
     * Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Product SKU', 'king-addons');
    }

    /**
     * Widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-product-meta';
    }

    /**
     * Categories.
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-sku-style'];
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
            'label_text',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('SKU:', 'king-addons'),
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => sprintf(__('Layout %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'inline' => esc_html__('Inline', 'king-addons'),
                    'stacked' => esc_html__('Label above (Pro)', 'king-addons'),
                ],
                'default' => 'inline',
            ]
        );

        $this->add_control(
            'show_if_empty',
            [
                'label' => sprintf(__('Show if empty %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'empty_text',
            [
                'label' => esc_html__('Empty fallback (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('N/A', 'king-addons'),
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
                'name' => 'typography_label',
                'selector' => '{{WRAPPER}} .ka-woo-product-sku__label',
                'label' => esc_html__('Label Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'color_label',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-sku__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography_value',
                'selector' => '{{WRAPPER}} .ka-woo-product-sku__value',
                'label' => esc_html__('Value Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'color_value',
            [
                'label' => esc_html__('Value Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-sku__value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-sku' => 'gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .ka-woo-product-sku' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $sku = $product->get_sku();

        $can_pro = king_addons_can_use_pro();

        if ('' === $sku || null === $sku) {
            if (!$can_pro || empty($settings['show_if_empty'])) {
                return;
            }
            $sku = $settings['empty_text'] ?? '';
        }

        $layout = $settings['layout'] ?? 'inline';
        if ('stacked' === $layout && !$can_pro) {
            $layout = 'inline';
        }

        $this->add_render_attribute('wrapper', 'class', 'ka-woo-product-sku');
        if ('stacked' === $layout) {
            $this->add_render_attribute('wrapper', 'class', 'ka-woo-product-sku--stacked');
        }

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<span class="ka-woo-product-sku__label">' . esc_html($settings['label_text']) . '</span>';
        echo '<span class="ka-woo-product-sku__value">' . esc_html($sku) . '</span>';
        echo '</div>';
    }
}







