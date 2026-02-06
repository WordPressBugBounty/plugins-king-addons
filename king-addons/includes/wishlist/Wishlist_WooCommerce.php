<?php

namespace King_Addons\Wishlist;

use WC_Product;
use WC_Order;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integrates wishlist UI into WooCommerce templates and tracks conversions.
 */
class Wishlist_WooCommerce
{
    private Wishlist_Service $service;
    private Wishlist_Renderer $renderer;

    /**
     * @param Wishlist_Service $service Wishlist service.
     * @param Wishlist_Renderer $renderer Wishlist renderer.
     */
    public function __construct(Wishlist_Service $service, Wishlist_Renderer $renderer)
    {
        $this->service = $service;
        $this->renderer = $renderer;
    }

    /**
     * Register WooCommerce hooks.
     *
     * @return void
     */
    public function hooks(): void
    {
        if (!Wishlist_Settings::is_enabled() || !function_exists('wc_get_product')) {
            return;
        }

        $position = Wishlist_Settings::get('button_position', 'after_add_to_cart');
        $hook = $position === 'before_add_to_cart' ? 'woocommerce_before_add_to_cart_button' : 'woocommerce_after_add_to_cart_button';
        add_action($hook, [$this, 'render_single_button']);

        if (Wishlist_Settings::get('show_in_archives', true)) {
            add_action('woocommerce_after_shop_loop_item', [$this, 'render_loop_button'], 12);
        }

        // Track conversions when order is completed
        add_action('woocommerce_order_status_completed', [$this, 'track_conversion'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'track_conversion'], 10, 1);
    }

    /**
     * Track wishlist to purchase conversions.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function track_conversion(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        // Check if already tracked
        $tracked = $order->get_meta('_ka_wishlist_conversion_tracked');
        if ($tracked) {
            return;
        }

        $user_id = $order->get_customer_id();
        if ($user_id <= 0) {
            return;
        }

        global $wpdb;
        $items_table = Wishlist_DB::get_items_table();
        $conversions_table = Wishlist_DB::get_conversions_table();

        // Get user's wishlist product IDs
        $wishlist_products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT product_id FROM {$items_table} WHERE user_id = %d",
                $user_id
            )
        );

        if (empty($wishlist_products)) {
            return;
        }

        // Check each order item against wishlist
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            if (!in_array($product_id, $wishlist_products, false)) {
                continue;
            }

            // This product was in wishlist - record conversion
            $wpdb->replace(
                $conversions_table,
                [
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id ?: 0,
                    'order_id' => $order_id,
                    'order_item_qty' => $item->get_quantity(),
                    'order_item_total' => $item->get_total(),
                    'converted_at' => current_time('mysql', true),
                ],
                ['%d', '%d', '%d', '%d', '%d', '%f', '%s']
            );
        }

        // Mark order as tracked
        $order->update_meta_data('_ka_wishlist_conversion_tracked', 1);
        $order->save();
    }

    /**
     * Render wishlist button on single product pages.
     *
     * @return void
     */
    public function render_single_button(): void
    {
        if (!Wishlist_Settings::is_enabled()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        echo wp_kses_post(
            $this->renderer->render_button([
                'product_id' => $product->get_id(),
                'variation_id' => 0,
                'class' => 'king-addons-wishlist-button--single',
            ])
        );
    }

    /**
     * Render wishlist button inside product loop cards.
     *
     * @return void
     */
    public function render_loop_button(): void
    {
        if (!Wishlist_Settings::is_enabled()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        echo wp_kses_post(
            $this->renderer->render_button([
                'product_id' => $product->get_id(),
                'class' => 'king-addons-wishlist-button--loop',
            ])
        );
    }
}



