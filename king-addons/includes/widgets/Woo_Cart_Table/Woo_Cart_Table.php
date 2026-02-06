<?php
/**
 * Woo Cart Table widget.
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
 * Renders the WooCommerce cart table.
 */
class Woo_Cart_Table extends Abstract_Cart_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_cart_table';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cart Table', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-cart';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-table-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-table-script',
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
            'show_collaterals',
            [
                'label' => sprintf(__('Include Totals & Cross-sells (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'heading_empty',
            [
                'label' => esc_html__('Empty title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Your cart is empty', 'king-addons'),
            ]
        );

        $this->add_control(
            'message_empty',
            [
                'label' => esc_html__('Empty message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Looks like you have not added anything to your cart yet.', 'king-addons'),
            ]
        );

        $this->add_control(
            'primary_btn_text',
            [
                'label' => esc_html__('Primary button text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Return to shop', 'king-addons'),
            ]
        );

        $this->add_control(
            'primary_btn_url',
            [
                'label' => esc_html__('Primary button URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
            ]
        );

        $this->add_control(
            'secondary_btn_text',
            [
                'label' => sprintf(__('Secondary button text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('View offers', 'king-addons'),
            ]
        );

        $this->add_control(
            'secondary_btn_url',
            [
                'label' => sprintf(__('Secondary button URL %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_table',
            [
                'label' => esc_html__('Table', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'table_typography',
                'selector' => '{{WRAPPER}} .woocommerce-cart-form',
            ]
        );

        $this->add_control(
            'table_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-cart-form' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'table_border',
                'selector' => '{{WRAPPER}} .woocommerce-cart-form table',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'table_shadow',
                'selector' => '{{WRAPPER}} .woocommerce-cart-form table',
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
        $settings = $this->get_settings_for_display();

        if (!$this->should_render()) {
            $this->render_missing_cart_notice();
            return;
        }

        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        if (WC()->cart->is_empty()) {
            $this->render_empty_cart($settings);
            return;
        }
        $can_pro = king_addons_can_use_pro();
        $include_collaterals = (!empty($settings['show_collaterals']) && $can_pro);

        $had_totals = has_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals');
        $had_cross = has_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');

        if (!$include_collaterals) {
            if ($had_totals) {
                remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
            }
            if ($had_cross) {
                remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10);
            }
        }

        $ajax_nonce = wp_create_nonce('ka_cart');

        echo '<div class="ka-cart-table" data-ka-cart="1" data-ajax-url="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr($ajax_nonce) . '">';
        woocommerce_output_all_notices();
        wc_get_template('cart/cart.php');
        echo '</div>';

        if (!$include_collaterals) {
            if ($had_totals) {
                add_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
            }
            if ($had_cross) {
                add_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10);
            }
        }
    }

    /**
     * Render simple empty cart notice.
     *
     * @return void
     */
    /**
     * Render empty cart block.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return void
     */
    protected function render_empty_cart(array $settings = []): void
    {
        $shop_id = wc_get_page_id('shop');
        $shop_url = $shop_id > 0 ? get_permalink($shop_id) : home_url('/');
        $can_pro = king_addons_can_use_pro();

        $heading = $settings['heading_empty'] ?? esc_html__('Your cart is empty', 'king-addons');
        $message = $settings['message_empty'] ?? esc_html__('Looks like you have not added anything to your cart yet.', 'king-addons');
        $primary_text = $settings['primary_btn_text'] ?? esc_html__('Return to shop', 'king-addons');
        $primary_url = !empty($settings['primary_btn_url']['url']) ? $settings['primary_btn_url']['url'] : $shop_url;
        $secondary_text = (!empty($settings['secondary_btn_text']) && $can_pro) ? $settings['secondary_btn_text'] : '';
        $secondary_url = (!empty($settings['secondary_btn_url']['url']) && $can_pro) ? $settings['secondary_btn_url']['url'] : '';

        echo '<div class="ka-cart-empty">';
        echo '<div class="ka-cart-empty__icon" aria-hidden="true">🛒</div>';
        if ($heading) {
            echo '<h3 class="ka-cart-empty__title">' . esc_html($heading) . '</h3>';
        }
        if ($message) {
            echo '<p class="ka-cart-empty__text">' . esc_html($message) . '</p>';
        }
        echo '<div class="ka-cart-empty__actions">';
        if ($primary_text && $primary_url) {
            echo '<a class="ka-cart-empty__btn ka-cart-empty__btn--primary" href="' . esc_url($primary_url) . '">' . esc_html($primary_text) . '</a>';
        }
        if ($secondary_text && $secondary_url) {
            echo '<a class="ka-cart-empty__btn ka-cart-empty__btn--ghost" href="' . esc_url($secondary_url) . '">' . esc_html($secondary_text) . '</a>';
        }
        echo '</div>';
        /**
         * Hook to inject additional empty cart content (e.g., Elementor template).
         */
        do_action('king_addons/cart/empty_after');
        echo '</div>';
    }

    /**
     * AJAX: Update quantity or remove item.
     *
     * @return void
     */
    public static function ajax_update(): void
    {
        check_ajax_referer('ka_cart', 'nonce');

        if (!WC()->cart) {
            wp_send_json_error(['message' => esc_html__('Cart unavailable.', 'king-addons')]);
        }

        $op = sanitize_text_field($_POST['op'] ?? '');
        $key = sanitize_text_field($_POST['cart_item_key'] ?? '');

        if (!$key) {
            wp_send_json_error(['message' => esc_html__('Missing cart item.', 'king-addons')]);
        }

        if ('remove' === $op) {
            WC()->cart->remove_cart_item($key);
        } elseif ('qty' === $op) {
            $qty = isset($_POST['qty']) ? (float) wc_clean(wp_unslash($_POST['qty'])) : 0;
            WC()->cart->set_quantity($key, $qty, true);
        } else {
            wp_send_json_error(['message' => esc_html__('Unknown operation.', 'king-addons')]);
        }

        WC()->cart->calculate_totals();

        wp_send_json_success(self::build_fragments());
    }

    /**
     * AJAX: Apply/remove coupon.
     *
     * @return void
     */
    public static function ajax_coupon(): void
    {
        check_ajax_referer('ka_cart', 'nonce');

        if (!WC()->cart) {
            wp_send_json_error(['message' => esc_html__('Cart unavailable.', 'king-addons')]);
        }

        $code = isset($_POST['coupon']) ? wc_format_coupon_code(wp_unslash($_POST['coupon'])) : '';
        $mode = sanitize_text_field($_POST['mode'] ?? 'apply');

        if (!$code) {
            wp_send_json_error(['message' => esc_html__('Coupon is empty.', 'king-addons')]);
        }

        if ('remove' === $mode) {
            WC()->cart->remove_coupon($code);
        } else {
            WC()->cart->apply_coupon($code);
        }

        WC()->cart->calculate_totals();

        wp_send_json_success(self::build_fragments());
    }

    /**
     * Build fragments for AJAX responses.
     *
     * @return array<string, mixed>
     */
    private static function build_fragments(): array
    {
        $empty = WC()->cart->is_empty();

        $cart_html = $empty ? self::render_empty_cart_html() : self::render_cart_html();
        $totals_html = self::render_totals_html($empty);
        $cross_html = self::render_cross_sells_html($empty);
        $notices = function_exists('wc_print_notices') ? wc_print_notices(true) : '';

        return [
            'cart_html' => $cart_html,
            'totals_html' => $totals_html,
            'cross_sells_html' => $cross_html,
            'notices' => $notices,
            'empty' => $empty,
            'total' => WC()->cart->get_cart_contents_count(),
            'cart_hash' => WC()->cart->get_cart_hash(),
        ];
    }

    /**
     * Render cart HTML.
     *
     * @return string
     */
    private static function render_cart_html(): string
    {
        ob_start();
        woocommerce_output_all_notices();
        wc_get_template('cart/cart.php');
        return (string) ob_get_clean();
    }

    /**
     * Render totals HTML.
     *
     * @param bool $empty Is cart empty.
     *
     * @return string
     */
    private static function render_totals_html(bool $empty): string
    {
        if ($empty) {
            return '';
        }

        return Woo_Cart_Totals::render_totals_block_html();
    }

    /**
     * Render cross-sells HTML.
     *
     * @param bool $empty Is cart empty.
     *
     * @return string
     */
    private static function render_cross_sells_html(bool $empty): string
    {
        if ($empty) {
            return '';
        }

        ob_start();
        woocommerce_cross_sell_display();
        return (string) ob_get_clean();
    }

    /**
     * Render empty cart markup for AJAX.
     *
     * @return string
     */
    private static function render_empty_cart_html(): string
    {
        $shop_id = wc_get_page_id('shop');
        $shop_url = $shop_id > 0 ? get_permalink($shop_id) : home_url('/');

        ob_start();
        echo '<div class="ka-cart-empty">';
        echo '<p class="ka-cart-empty__text">' . esc_html__('Your cart is empty.', 'king-addons') . '</p>';
        echo '<a class="ka-cart-empty__link" href="' . esc_url($shop_url) . '">' . esc_html__('Return to shop', 'king-addons') . '</a>';
        echo '</div>';
        return (string) ob_get_clean();
    }
}

// Register AJAX handlers only if WooCommerce is active
if (class_exists('WooCommerce')) {
    add_action('wp_ajax_ka_cart_update', [Woo_Cart_Table::class, 'ajax_update']);
    add_action('wp_ajax_nopriv_ka_cart_update', [Woo_Cart_Table::class, 'ajax_update']);
    add_action('wp_ajax_ka_cart_coupon', [Woo_Cart_Table::class, 'ajax_coupon']);
    add_action('wp_ajax_nopriv_ka_cart_coupon', [Woo_Cart_Table::class, 'ajax_coupon']);
}






