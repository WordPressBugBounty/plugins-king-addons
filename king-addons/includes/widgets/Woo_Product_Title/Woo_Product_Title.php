<?php
/**
 * Woo Product Title widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

require_once KING_ADDONS_PATH . 'includes/helpers/Woo_Builder/Abstract_Single_Widget.php';

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays WooCommerce product title.
 */
class Woo_Product_Title extends Abstract_Single_Widget
{
    /**
     * Get widget name.
     */
    public function get_name(): string
    {
        return 'woo_product_title';
    }

    /**
     * Get widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Product Title', 'king-addons');
    }

    /**
     * Get widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-t-letter';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-title-style'];
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
            'html_tag',
            [
                'label' => esc_html__('HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'div',
                    'span' => 'span',
                ],
                'default' => 'h2',
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
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
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-title' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'typo_preset',
            [
                'label' => sprintf(__('Typography preset %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('None', 'king-addons'),
                    'hero' => esc_html__('Hero', 'king-addons'),
                    'eyebrow' => esc_html__('Eyebrow', 'king-addons'),
                    'compact' => esc_html__('Compact', 'king-addons'),
                ],
                'default' => '',
                'description' => esc_html__('Applies preset font size/weight/spacing (Pro).', 'king-addons'),
            ]
        );

        $this->add_control(
            'trim_length',
            [
                'label' => sprintf(__('Trim Length %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'description' => esc_html__('Number of characters. Pro only.', 'king-addons'),
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
                'selector' => '{{WRAPPER}} .ka-woo-product-title',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_hover',
            [
                'label' => esc_html__('Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-title:hover' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .ka-woo-product-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ka-woo-product-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render.
     */
    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $tag = $settings['html_tag'] ?? 'h2';
        $title = $product->get_name();

        if (!empty($settings['trim_length']) && king_addons_can_use_pro()) {
            $limit = (int) $settings['trim_length'];
            if ($limit > 0) {
                $title = wp_html_excerpt($title, $limit, '…');
            }
        }

        $this->add_render_attribute('title', 'class', 'ka-woo-product-title');
        if (!empty($settings['typo_preset']) && king_addons_can_use_pro()) {
            $this->add_render_attribute('title', 'class', 'ka-woo-product-title--preset-' . sanitize_html_class($settings['typo_preset']));
        }

        printf('<%1$s %2$s>%3$s</%1$s>', esc_html($tag), $this->get_render_attribute_string('title'), esc_html($title));
    }
}







