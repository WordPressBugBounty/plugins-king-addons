<?php
/**
 * Woo My Account Order Details widget.
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
 * Displays the order details view for My Account.
 */
class Woo_My_Account_Order_Details extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_my_account_order_details';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('My Account Order Details', 'king-addons');
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
     * Widget categories.
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
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-order-details-style'];
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
            'show_totals',
            [
                'label' => esc_html__('Show Totals', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_addresses',
            [
                'label' => esc_html__('Show Billing/Shipping', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_back_link',
            [
                'label' => esc_html__('Show Back to Orders', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
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
        if (!Woo_Context::maybe_render_context_notice('my_account')) {
            return;
        }
        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('my_account');
        if (!function_exists('is_account_page') || (!is_account_page() && !$in_builder)) {
            return;
        }

        $order_id = absint(get_query_var('view-order'));
        if ($order_id) {
            $settings = $this->get_settings_for_display();
            $order = wc_get_order($order_id);

            echo '<div class="ka-woo-my-account-order-details">';

            if (('yes' === ($settings['show_back_link'] ?? 'yes')) && wc_get_endpoint_url('orders')) {
                echo '<a class="ka-woo-order-details__back" href="' . esc_url(wc_get_endpoint_url('orders')) . '">' . esc_html__('Back to orders', 'king-addons') . '</a>';
            }

            if ($order) {
                echo '<div class="ka-woo-order-details__summary">';
                echo '<div class="ka-woo-order-details__row"><span>' . esc_html__('Order', 'king-addons') . ':</span><span>#' . esc_html($order->get_order_number()) . '</span></div>';
                echo '<div class="ka-woo-order-details__row"><span>' . esc_html__('Status', 'king-addons') . ':</span><span class="ka-woo-order-details__badge">' . esc_html(wc_get_order_status_name($order->get_status())) . '</span></div>';
                echo '<div class="ka-woo-order-details__row"><span>' . esc_html__('Date', 'king-addons') . ':</span><span>' . esc_html(wc_format_datetime($order->get_date_created())) . '</span></div>';
                echo '<div class="ka-woo-order-details__row"><span>' . esc_html__('Total', 'king-addons') . ':</span><span>' . wp_kses_post($order->get_formatted_order_total()) . '</span></div>';
                echo '</div>';
            }

            wc_get_template('myaccount/view-order.php', ['order_id' => $order_id]);

            if ('yes' === ($settings['show_totals'] ?? 'yes')) {
                wc_get_template('order/order-details.php', ['order_id' => $order_id]);
            }

            if ('yes' === ($settings['show_addresses'] ?? 'yes')) {
                wc_get_template('order/order-details-customer.php', ['order_id' => $order_id]);
            }

            /**
             * Hook for extra order details (ACF/meta).
             *
             * @param int $order_id Order ID.
             */
            do_action('king_addons/my_account/order_details/after', $order_id);
            echo '</div>';
            return;
        }

        $orders_url = wc_get_endpoint_url('orders');
        if (Woo_Context::is_editor()) {
            echo '<div class="king-addons-woo-builder-notice">';
            echo esc_html__('Select an order to view details.', 'king-addons');
            if ($orders_url) {
                echo ' <a href="' . esc_url($orders_url) . '">' . esc_html__('View orders', 'king-addons') . '</a>';
            }
            echo '</div>';
        }
    }
}






