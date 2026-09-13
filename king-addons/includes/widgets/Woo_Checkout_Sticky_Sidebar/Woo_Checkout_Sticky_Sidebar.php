<?php
/**
 * Woo Checkout Sticky Sidebar widget.
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
 * Placeholder sticky sidebar (Pro-only rendering).
 */
class Woo_Checkout_Sticky_Sidebar extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_sticky_sidebar';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Sticky Sidebar', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-checkout-sticky-sidebar';
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
            'title_text',
            [
                'label' => esc_html__('Sidebar Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'default' => esc_html__('Order Summary', 'king-addons'),
            ]
        );

        $this->add_control(
            'sticky_offset',
            [
                'label' => sprintf(__('Top Offset (px) (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
            ]
        );

        $this->add_control(
            'disable_below',
            [
                'label' => sprintf(__('Disable sticky below (px) (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 768,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output (Free notice).
     *
     * @return void
     */
    protected function render(): void
    {
        if (!Woo_Context::maybe_render_context_notice('checkout')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('checkout');
        if (!function_exists('is_checkout') || (!is_checkout() && !$in_builder) || (function_exists('is_order_received_page') && is_order_received_page())) {
            return;
        }

        if (!function_exists('woocommerce_order_review')) {
            return;
        }

        // woocommerce_order_review() reaches straight into WC()->cart. There is
        // no cart in the Elementor editor, so the call died with
        // "Call to a member function get_cart() on null" and took the whole
        // editor preview of the checkout template down with it.
        if (!function_exists('WC') || !WC()->cart) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('The sticky sidebar shows the order review from the live cart.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        if (!king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">';
                echo esc_html__('Sticky order sidebar is available in Pro.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        // A cleared field is '' rather than unset, and (int) '' is 0 - the
        // sidebar then pinned to the very top and stayed sticky on phones.
        $offset = max(0, (int) (($settings['sticky_offset'] ?? null) ?: 20));
        $disable_below = max(0, (int) (($settings['disable_below'] ?? null) ?: 768));

        echo '<aside class="ka-woo-checkout-sticky" data-ka-sticky="true" data-ka-sticky-offset="' . esc_attr($offset) . '" data-ka-sticky-breakpoint="' . esc_attr($disable_below) . '">';
        if (!empty($settings['title_text'])) {
            echo '<h4 class="ka-woo-checkout-sticky__title">' . esc_html($settings['title_text']) . '</h4>';
        }
        woocommerce_order_review();
        echo '</aside>';
    }
}






