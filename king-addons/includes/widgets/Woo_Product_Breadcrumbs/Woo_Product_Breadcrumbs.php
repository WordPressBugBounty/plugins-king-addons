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
        return 'king-addons-icon king-addons-woo-product-breadcrumbs';
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
                'dynamic' => ['active' => true],
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
            // Always a plain label here: WC_Breadcrumb::add_crumb() runs the
            // name through wp_strip_all_tags(), so an SVG passed as "home"
            // stripped down to an empty string and the Home link came out
            // blank. The icon is injected into the finished markup below.
            'home' => esc_html__('Home', 'king-addons'),
        ];

        // A filter that returns its argument unchanged did nothing except pile
        // up another closure on the hook with every render.
        ob_start();
        woocommerce_breadcrumb($args);
        $html = ob_get_clean();

        if ($trim > 0) {
            $html = preg_replace_callback(
                '/>([^<]+)</',
                static function ($m) use ($trim) {
                    // The captured text is already escaped markup. Running
                    // esc_html() over it again turned "&amp;" into "&amp;amp;",
                    // so a shop with "Home & Garden" in the trail showed the
                    // entity. Decode, cut, escape once.
                    $text = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                    if (mb_strlen($text) > $trim) {
                        $text = mb_substr($text, 0, $trim) . '…';
                    }
                    return '>' . esc_html($text) . '<';
                },
                $html
            );
        }

        if (!empty($settings['show_home_icon']) && $can_pro) {
            $icon = '<span class="ka-woo-breadcrumbs__home" aria-hidden="true">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" role="presentation" focusable="false">'
                . '<path fill="currentColor" d="M12 4.6 4.5 11H7v7h3.5v-4h3v4H17v-7h2.5z"/></svg></span>'
                . '<span class="screen-reader-text">' . esc_html__('Home', 'king-addons') . '</span>';

            // Replace the first crumb's text only - after trimming, so the
            // trimmer never runs over the SVG.
            $html = preg_replace(
                '/(<a\b[^>]*>)([^<]*)(<\/a>)/',
                '$1' . str_replace('$', '\$', $icon) . '$3',
                $html,
                1
            );
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}







