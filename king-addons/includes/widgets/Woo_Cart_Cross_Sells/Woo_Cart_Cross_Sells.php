<?php
/**
 * Woo Cart Cross-sells widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders cart cross-sell products.
 */
class Woo_Cart_Cross_Sells extends Abstract_Cart_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_cart_cross_sells';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cart Cross-sells', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-product-categories';
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-cross-sells-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-slick-slick',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-cross-sells-script',
        ];
    }

    /**
     * Register controls.
     *
     * @return void
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
            'limit',
            [
                'label' => esc_html__('Products Limit', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 4,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'default' => 4,
            ]
        );

        $this->add_control(
            'columns_tablet',
            [
                'label' => esc_html__('Columns Tablet', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 4,
                'default' => 2,
            ]
        );

        $this->add_control(
            'columns_mobile',
            [
                'label' => esc_html__('Columns Mobile', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 2,
                'default' => 1,
            ]
        );

        $this->add_control(
            'show_heading',
            [
                'label' => esc_html__('Show heading', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'heading_text',
            [
                'label' => esc_html__('Heading text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('You may also like…', 'king-addons'),
                'condition' => [
                    'show_heading' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label' => esc_html__('Show Arrows', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_dots',
            [
                'label' => esc_html__('Show Dots', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'loop',
            [
                'label' => esc_html__('Loop Slides', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label' => esc_html__('Autoplay Speed (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4000,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .cross-sells',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .cross-sells li.product',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'selector' => '{{WRAPPER}} .cross-sells li.product',
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
        if (!$this->should_render()) {
            $this->render_missing_cart_notice();
            return;
        }

        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            woocommerce_output_all_notices();
            wc_get_template('cart/cart-empty.php');
            return;
        }

        $settings = $this->get_settings_for_display();
        $limit = max(1, (int) ($settings['limit'] ?? 4));
        $columns = max(1, (int) ($settings['columns'] ?? 4));
        $columns_tablet = max(1, (int) ($settings['columns_tablet'] ?? 2));
        $columns_mobile = max(1, (int) ($settings['columns_mobile'] ?? 1));
        $show_arrows = !empty($settings['show_arrows']);
        $show_dots = !empty($settings['show_dots']);
        $loop = !empty($settings['loop']);
        $autoplay = !empty($settings['autoplay']);
        $autoplay_speed = isset($settings['autoplay_speed']) ? (int) $settings['autoplay_speed'] : 4000;
        $heading_text = (!empty($settings['show_heading']) && !empty($settings['heading_text'])) ? $settings['heading_text'] : '';

        echo '<div class="ka-woo-cart-cross-sells" data-ka-cross-sell="1" data-cols="' . esc_attr((string) $columns) . '" data-cols-tablet="' . esc_attr((string) $columns_tablet) . '" data-cols-mobile="' . esc_attr((string) $columns_mobile) . '" data-arrows="' . ($show_arrows ? '1' : '0') . '" data-dots="' . ($show_dots ? '1' : '0') . '" data-loop="' . ($loop ? '1' : '0') . '" data-autoplay="' . ($autoplay ? '1' : '0') . '" data-autoplay-speed="' . esc_attr((string) $autoplay_speed) . '">';
        if ($heading_text) {
            echo '<h3 class="ka-woo-cart-cross-sells__heading">' . esc_html($heading_text) . '</h3>';
        }
        echo '<div class="ka-woo-cart-cross-sells__list">';
        woocommerce_cross_sell_display($limit, $columns);
        echo '</div>';
        echo '</div>';
    }
}






