<?php
/**
 * Woo Cart Totals widget.
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
 * Renders the WooCommerce cart totals.
 */
class Woo_Cart_Totals extends Abstract_Cart_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_cart_totals';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cart Totals', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-woocommerce';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-totals-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-totals-script',
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
            'section_style',
            [
                'label' => esc_html__('Totals', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'enable_sticky',
            [
                'label' => esc_html__('Enable Sticky', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'sticky_offset',
            [
                'label' => esc_html__('Sticky Top Offset (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 16,
                'condition' => [
                    'enable_sticky' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .cart_totals',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart_totals' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'totals_border',
                'selector' => '{{WRAPPER}} .cart_totals',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'totals_shadow',
                'selector' => '{{WRAPPER}} .cart_totals',
            ]
        );

        $this->add_control(
            'totals_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .cart_totals' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            $this->render_missing_cart_notice();
            return;
        }

        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            woocommerce_output_all_notices();
            echo '<div class="ka-cart-empty">';
            echo '<p class="ka-cart-empty__text">' . esc_html__('Your cart is empty.', 'king-addons') . '</p>';
            echo '</div>';
            return;
        }

        $ajax_nonce = wp_create_nonce('ka_cart');
        $settings = $this->get_settings_for_display();
        $sticky_class = !empty($settings['enable_sticky']) ? ' ka-woo-cart-totals--sticky' : '';
        $sticky_style = '';
        if (!empty($settings['enable_sticky']) && isset($settings['sticky_offset'])) {
            $sticky_style = 'style="--ka-cart-sticky-offset:' . esc_attr((string) (int) $settings['sticky_offset']) . 'px"';
        }

        echo '<div class="ka-woo-cart-totals' . esc_attr($sticky_class) . '" data-ka-cart="1" data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr($ajax_nonce) . '" ' . $sticky_style . '>';
        echo self::render_totals_block_html();
        echo '</div>';
    }

    /**
     * Render collapsible coupon UI.
     *
     * @return void
     */
    private static function render_coupon_toggle(): string
    {
        ob_start();
        echo '<div class="ka-cart-coupon" data-ka-coupon="1">';
        echo '<button type="button" class="ka-cart-coupon__toggle" data-ka-coupon-toggle="1">' . esc_html__('Have a coupon?', 'king-addons') . '</button>';
        echo '<div class="ka-cart-coupon__body" hidden>';
        echo '<label class="ka-cart-coupon__label">' . esc_html__('Coupon code', 'king-addons') . '</label>';
        echo '<div class="ka-cart-coupon__row">';
        echo '<input type="text" class="ka-cart-coupon__input" name="ka_coupon_code" placeholder="' . esc_attr__('Enter code', 'king-addons') . '"/>';
        echo '<button type="button" class="ka-cart-coupon__apply" data-mode="apply">' . esc_html__('Apply', 'king-addons') . '</button>';
        echo '<button type="button" class="ka-cart-coupon__remove" data-mode="remove">' . esc_html__('Remove', 'king-addons') . '</button>';
        echo '</div>';
        echo '<div class="ka-cart-coupon__notice" aria-live="polite"></div>';
        echo '</div>';
        echo '</div>';
        return (string) ob_get_clean();
    }

    /**
     * Render totals block markup for reuse (frontend + AJAX).
     *
     * @return string
     */
    public static function render_totals_block_html(): string
    {
        if (!WC()->cart || WC()->cart->is_empty()) {
            return '';
        }

        ob_start();
        echo self::render_coupon_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        woocommerce_cart_totals();
        return (string) ob_get_clean();
    }
}






