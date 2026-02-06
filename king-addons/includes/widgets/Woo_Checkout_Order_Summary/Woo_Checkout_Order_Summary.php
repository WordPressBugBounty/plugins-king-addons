<?php
/**
 * Woo Checkout Order Summary widget.
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
 * Displays checkout order review section.
 */
class Woo_Checkout_Order_Summary extends Abstract_Checkout_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_order_summary';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Order Summary', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-order-review';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-order-summary-style'];
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
            'show_thumbnails',
            [
                'label' => sprintf(__('Show product thumbnails %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_sku',
            [
                'label' => sprintf(__('Show SKU %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_meta',
            [
                'label' => sprintf(__('Show item meta %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'sticky',
            [
                'label' => sprintf(__('Make Sticky (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'sticky_offset',
            [
                'label' => sprintf(__('Sticky Offset (px) (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'condition' => [
                    'sticky' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'sticky_breakpoint',
            [
                'label' => sprintf(__('Sticky Breakpoint (px) (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 768,
                'condition' => [
                    'sticky' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-summary',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-summary' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'box_border',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-summary',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'box_shadow',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-summary',
            ]
        );

        $this->add_control(
            'box_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-summary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        if (!function_exists('WC')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $this->add_render_attribute('summary', 'class', 'ka-woo-checkout-summary');

        $show_thumbs = !empty($settings['show_thumbnails']) && $can_pro;
        $show_sku = !empty($settings['show_sku']) && $can_pro;
        $show_meta = !empty($settings['show_meta']) && $can_pro;

        if (!empty($settings['sticky']) && $can_pro) {
            $offset = isset($settings['sticky_offset']) ? (int) $settings['sticky_offset'] : 20;
            $this->add_render_attribute('summary', 'class', 'ka-woo-checkout-summary--sticky');
            $this->add_render_attribute('summary', 'data-ka-sticky', 'true');
            $this->add_render_attribute('summary', 'data-ka-sticky-offset', (int) $offset);
            $breakpoint = isset($settings['sticky_breakpoint']) ? (int) $settings['sticky_breakpoint'] : 768;
            $this->add_render_attribute('summary', 'data-ka-sticky-breakpoint', (int) $breakpoint);
            $this->add_render_attribute('summary', 'style', 'position: sticky; top: ' . $offset . 'px;');
        }

        echo '<div ' . $this->get_render_attribute_string('summary') . '>';
        $cart = WC()->cart;
        if ($cart) {
            echo '<div class="ka-woo-checkout-summary__items">';
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                if (!$product || !$product->exists()) {
                    continue;
                }
                $product_id = $product->get_id();
                $name = $product->get_name();
                $qty = $cart_item['quantity'];
                $line_total = $cart->get_product_subtotal($product, $qty);
                $thumbnail = $show_thumbs ? $product->get_image('thumbnail') : '';
                $sku = $show_sku ? $product->get_sku() : '';
                $meta = $show_meta ? wc_get_formatted_cart_item_data($cart_item, false) : '';

                echo '<div class="ka-woo-checkout-summary__item">';
                if ($thumbnail) {
                    echo '<div class="ka-woo-checkout-summary__thumb">' . $thumbnail . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                echo '<div class="ka-woo-checkout-summary__body">';
                echo '<div class="ka-woo-checkout-summary__title">' . esc_html($name) . '</div>';
                if ($sku) {
                    echo '<div class="ka-woo-checkout-summary__meta ka-woo-checkout-summary__meta--sku">' . esc_html__('SKU:', 'king-addons') . ' ' . esc_html($sku) . '</div>';
                }
                if ($meta) {
                    echo '<div class="ka-woo-checkout-summary__meta">' . wp_kses_post($meta) . '</div>';
                }
                echo '<div class="ka-woo-checkout-summary__qty">' . esc_html__('Qty:', 'king-addons') . ' ' . esc_html($qty) . '</div>';
                echo '</div>';
                echo '<div class="ka-woo-checkout-summary__price">' . wp_kses_post($line_total) . '</div>';
                echo '</div>';
            }
            echo '</div>';

            echo '<div class="ka-woo-checkout-summary__totals">';
            echo '<div class="ka-woo-checkout-summary__totals-row"><span>' . esc_html__('Subtotal', 'king-addons') . '</span><span>' . wp_kses_post($cart->get_cart_subtotal()) . '</span></div>';

            foreach ($cart->get_coupons() as $code => $coupon) {
                $amount_html = '-' . wc_price($coupon->get_amount());
                echo '<div class="ka-woo-checkout-summary__totals-row"><span>' . esc_html__('Coupon', 'king-addons') . ' (' . esc_html($code) . ')</span><span>' . wp_kses_post($amount_html) . '</span></div>';
            }

            if ($cart->needs_shipping() && $cart->show_shipping()) {
                $packages = WC()->shipping()->get_packages();
                WC()->shipping()->calculate_shipping($packages);
                $rates = $packages[0]['rates'] ?? [];
                $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
                $chosen_id = $chosen[0] ?? '';
                foreach ($rates as $rate) {
                    if ($chosen_id && $rate->id !== $chosen_id) {
                        continue;
                    }
                    echo '<div class="ka-woo-checkout-summary__totals-row"><span>' . esc_html__('Shipping', 'king-addons') . '</span><span>' . wp_kses_post(wc_price($rate->get_cost())) . '</span></div>';
                }
            }

            foreach ($cart->get_fees() as $fee) {
                echo '<div class="ka-woo-checkout-summary__totals-row"><span>' . esc_html($fee->name) . '</span><span>' . wp_kses_post(wc_price($fee->amount)) . '</span></div>';
            }

            if ('excl' === $cart->get_tax_price_display_mode()) {
                $tax_totals = $cart->get_tax_totals();
                if (!empty($tax_totals)) {
                    foreach ($tax_totals as $code => $tax) {
                        echo '<div class="ka-woo-checkout-summary__totals-row"><span>' . esc_html($tax->label) . '</span><span>' . wp_kses_post($tax->formatted_amount) . '</span></div>';
                    }
                }
            }

            echo '<div class="ka-woo-checkout-summary__totals-row ka-woo-checkout-summary__totals-row--total"><span>' . esc_html__('Total', 'king-addons') . '</span><span>' . wp_kses_post($cart->get_total()) . '</span></div>';
            echo '</div>';
        }
        echo '</div>';
    }
}






