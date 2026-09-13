<?php
/**
 * AJAX handler for the Woo Product Tabs widget.
 *
 * Lives outside the widget class on purpose: admin-ajax.php never instantiates
 * Elementor widgets, so a handler registered from the widget constructor is
 * simply not there when the request arrives and every call comes back as "0".
 * This file has no Elementor dependency and can be required at plugin load.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders one product tab on demand.
 */
class Woo_Product_Tabs_Ajax
{
    public const ACTION = 'king_addons_render_product_tab';
    public const NONCE = 'king_addons_woo_tabs';

    /**
     * Hook the endpoint up for logged-in and anonymous visitors alike.
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('wp_ajax_' . self::ACTION, [self::class, 'handle']);
        add_action('wp_ajax_nopriv_' . self::ACTION, [self::class, 'handle']);
    }

    /**
     * Render a single tab and return its markup.
     *
     * @return void
     */
    public static function handle(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::NONCE)) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
            wp_send_json_error(['message' => esc_html__('WooCommerce is not available.', 'king-addons')], 400);
        }

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $tab_key = isset($_POST['tab_key']) ? sanitize_key(wp_unslash($_POST['tab_key'])) : '';

        if ($product_id <= 0 || '' === $tab_key) {
            wp_send_json_error(['message' => esc_html__('Invalid request.', 'king-addons')], 400);
        }

        $product = wc_get_product($product_id);
        if (!$product || 'publish' !== get_post_status($product_id)) {
            wp_send_json_error(['message' => esc_html__('Product not found.', 'king-addons')], 404);
        }

        // Tab callbacks expect the environment of a real product page. The
        // reviews tab in particular calls comments_template(), which bails out
        // unless is_single() is true - on admin-ajax it is not, and the tab
        // came back empty. Standing up a genuine single-product query is what
        // makes all three core tabs render the same as they do inline.
        $previous_product = $GLOBALS['product'] ?? null;
        $previous_post = $GLOBALS['post'] ?? null;
        $previous_query = $GLOBALS['wp_query'] ?? null;

        $query = new \WP_Query(
            [
                'p' => $product_id,
                'post_type' => 'product',
                'posts_per_page' => 1,
                'ignore_sticky_posts' => true,
            ]
        );

        $GLOBALS['wp_query'] = $query;
        if ($query->have_posts()) {
            $query->the_post();
        }
        $GLOBALS['product'] = $product;

        $tabs = apply_filters('woocommerce_product_tabs', []);

        if (!isset($tabs[$tab_key]) || empty($tabs[$tab_key]['callback'])) {
            self::restore($previous_product, $previous_post, $previous_query);
            wp_send_json_error(['message' => esc_html__('Tab not found.', 'king-addons')], 404);
        }

        ob_start();
        call_user_func($tabs[$tab_key]['callback'], $tab_key, $tabs[$tab_key]);
        $html = ob_get_clean();

        self::restore($previous_product, $previous_post, $previous_query);

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Put the globals back the way they were.
     *
     * @param mixed $product Previous global product.
     * @param mixed $post    Previous global post.
     * @param mixed $query   Previous global query.
     * @return void
     */
    private static function restore($product, $post, $query): void
    {
        $GLOBALS['wp_query'] = $query;
        $GLOBALS['product'] = $product;
        $GLOBALS['post'] = $post;
    }
}
