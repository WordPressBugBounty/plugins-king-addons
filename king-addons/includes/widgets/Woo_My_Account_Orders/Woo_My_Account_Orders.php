<?php
/**
 * Woo My Account Orders widget.
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
 * Renders customer orders list.
 */
class Woo_My_Account_Orders extends Abstract_My_Account_Widget
{
    public function get_name(): string
    {
        return 'woo_my_account_orders';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Orders', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-woo-orders';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-orders-style'];
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
            'order_count',
            [
                'label' => esc_html__('Orders Per Page', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'table' => esc_html__('Table (default)', 'king-addons'),
                    'cards' => esc_html__('Cards', 'king-addons'),
                ],
                'default' => 'table',
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
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-my-account-orders',
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!$this->should_render()) {
            $this->render_missing_account_notice();
            return;
        }

        if ($this->maybe_render_login_form()) {
            return;
        }

        $user_id = get_current_user_id();
        $per_page = max(1, (int) ($this->get_settings_for_display()['order_count'] ?? 10));
        $paged = max(1, (int) get_query_var('paged', 1));
        $layout = $this->get_settings_for_display()['layout'] ?? 'table';

        $customer_orders = wc_get_orders(apply_filters('woocommerce_my_account_my_orders_query', [
            'customer' => $user_id,
            'page' => $paged,
            'paginate' => true,
            'posts_per_page' => $per_page,
        ]));

        $has_orders = $customer_orders && $customer_orders->total > 0;

        echo '<div class="ka-woo-my-account-orders">';
        if ('cards' === $layout && $has_orders) {
            echo '<div class="ka-woo-my-account-orders__cards">';
            foreach ($customer_orders->orders as $customer_order) {
                $order = wc_get_order($customer_order);
                if (!$order) {
                    continue;
                }
                $order_number = $order->get_order_number();
                $status = wc_get_order_status_name($order->get_status());
                $date = $order->get_date_created() ? $order->get_date_created()->date_i18n(get_option('date_format')) : '';
                $total = $order->get_formatted_order_total();
                $actions = wc_get_account_orders_actions($order);
                echo '<article class="ka-woo-my-account-orders__card">';
                echo '<div class="ka-woo-my-account-orders__card-top">';
                echo '<div class="ka-woo-my-account-orders__order-id">' . esc_html(sprintf(__('Order #%s', 'king-addons'), $order_number)) . '</div>';
                echo '<div class="ka-woo-my-account-orders__status">' . esc_html($status) . '</div>';
                echo '</div>';
                echo '<div class="ka-woo-my-account-orders__meta">';
                if ($date) {
                    echo '<span class="ka-woo-my-account-orders__date">' . esc_html($date) . '</span>';
                }
                if ($total) {
                    echo '<span class="ka-woo-my-account-orders__total">' . wp_kses_post($total) . '</span>';
                }
                echo '</div>';
                if (!empty($actions)) {
                    echo '<div class="ka-woo-my-account-orders__actions">';
                    foreach ($actions as $action) {
                        printf(
                            '<a href="%s" class="button %s">%s</a>',
                            esc_url($action['url']),
                            esc_attr($action['name']),
                            esc_html($action['name'])
                        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    echo '</div>';
                }
                echo '</article>';
            }
            echo '</div>';
        } else {
            wc_get_template(
                'myaccount/orders.php',
                [
                    'current_page' => $paged,
                    'customer_orders' => $customer_orders,
                    'has_orders' => $has_orders,
                ]
            );
        }
        echo '</div>';
    }
}






