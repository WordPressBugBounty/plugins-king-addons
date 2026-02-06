<?php
/**
 * Woo Product Breadcrumbs widget.
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
 * Displays WooCommerce breadcrumbs.
 */
class Woo_Product_Breadcrumbs extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_breadcrumbs';
    }

    public function get_title(): string
    {
        return esc_html__('Product Breadcrumbs', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-breadcrumbs';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-breadcrumbs-style'];
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
            'separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '/',
            ]
        );

        $this->add_control(
            'show_home_icon',
            [
                'label' => sprintf(__('Show home icon %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'trim_length',
            [
                'label' => sprintf(__('Trim items length %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
            ]
        );

        $this->add_control(
            'max_width',
            [
                'label' => sprintf(__('Max width per item (px) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 50,
                'max' => 600,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-breadcrumbs a, {{WRAPPER}} .ka-woo-breadcrumbs .breadcrumb_last' => 'max-width: {{VALUE}}px;',
                ],
                'description' => esc_html__('Applies ellipsis per crumb.', 'king-addons'),
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
                'selector' => '{{WRAPPER}} .ka-woo-breadcrumbs',
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-breadcrumbs, {{WRAPPER}} .ka-woo-breadcrumbs a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_separator',
            [
                'label' => esc_html__('Separator Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-breadcrumbs__sep' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .ka-woo-breadcrumbs' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $separator = $settings['separator'] ?: '/';
        $trim = !empty($settings['trim_length']) && $can_pro ? (int) $settings['trim_length'] : 0;

        $args = [
            'delimiter' => '<span class="ka-woo-breadcrumbs__sep">' . esc_html($separator) . '</span>',
            'wrap_before' => '<nav class="ka-woo-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'king-addons') . '">',
            'wrap_after' => '</nav>',
            'before' => '',
            'after' => '',
            'home' => !empty($settings['show_home_icon']) && $can_pro ? '<span class="ka-woo-breadcrumbs__home" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 4.6 4.5 11H7v7h3.5v-4h3v4H17v-7h2.5z"/></svg></span>' : esc_html__('Home', 'king-addons'),
        ];

        add_filter(
            'woocommerce_breadcrumb_home_url',
            static function ($url) {
                return $url;
            }
        );

        ob_start();
        woocommerce_breadcrumb($args);
        $html = ob_get_clean();

        if ($trim > 0) {
            $html = preg_replace_callback(
                '/>([^<]+)</',
                static function ($m) use ($trim) {
                    $text = $m[1];
                    if (mb_strlen($text) > $trim) {
                        $text = mb_substr($text, 0, $trim) . '…';
                    }
                    return '>' . esc_html($text) . '<';
                },
                $html
            );
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}







