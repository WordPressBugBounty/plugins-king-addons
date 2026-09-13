<?php
/**
 * Woo Checkout Coupon widget.
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
 * Displays checkout coupon form.
 */
class Woo_Checkout_Coupon extends Abstract_Checkout_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_coupon';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Coupon', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-checkout-coupon';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-coupon-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
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
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-coupon',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-coupon, {{WRAPPER}} .ka-woo-checkout-coupon a, {{WRAPPER}} .ka-woo-checkout-coupon .woocommerce-form-coupon-toggle' => 'color: {{VALUE}};',
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
        if (!$this->should_render()) {
            $this->render_missing_checkout_notice();
            return;
        }

        // woocommerce_checkout_coupon_form() prints nothing when coupons are
            // disabled in WooCommerce.
        // An empty wrapper on the page - and a blank box in the editor -
        // reads as a broken widget rather than a context that has nothing
        // to show.
        ob_start();
        woocommerce_checkout_coupon_form();
        $output = trim((string) ob_get_clean());

        if ('' === $output) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('The coupon form appears when coupons are enabled in WooCommerce.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        // WooCommerce's own markup, not user input.
        echo '<div class="ka-woo-checkout-coupon">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}






