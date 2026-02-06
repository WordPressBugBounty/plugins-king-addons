<?php
/**
 * Woo Cart Empty Message widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays empty cart notice with optional button.
 */
class Woo_Cart_Empty extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_cart_empty';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cart Empty Message', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-alert';
    }

    /**
     * Widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
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
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'message',
            [
                'label' => esc_html__('Message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Your cart is currently empty.', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_return_shop',
            [
                'label' => esc_html__('Show “Return to shop” button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
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
        if (!Woo_Context::maybe_render_context_notice('cart')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('cart');
        if (!function_exists('is_cart') || (!is_cart() && !$in_builder)) {
            return;
        }

        $cart_empty = function_exists('WC') && WC()->cart && WC()->cart->is_empty();
        if (!$cart_empty && !$in_builder) {
            return;
        }

        $settings = $this->get_settings_for_display();
        echo '<div class="ka-woo-cart-empty">';
        echo '<p>' . esc_html($settings['message']) . '</p>';

        if (!empty($settings['show_return_shop'])) {
            $shop_url = wc_get_page_permalink('shop');
            if ($shop_url) {
                echo '<a class="button" href="' . esc_url($shop_url) . '">' . esc_html__('Return to shop', 'king-addons') . '</a>';
            }
        }

        echo '</div>';
    }
}






