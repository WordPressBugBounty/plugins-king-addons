<?php
/**
 * Woo Builder wrapper for Single Product.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_id = apply_filters('king_addons/woo_builder/current_template_id', 0, 'single_product');

if (!$template_id) {
    wc_get_template('single-product.php');
    return;
}

wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-style');
wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script');

get_header('shop');

do_action('woocommerce_before_main_content');

global $post;
if ($post instanceof WP_Post) {
    setup_postdata($post);
}
if ($post instanceof WP_Post && function_exists('wc_setup_product_data')) {
    wc_setup_product_data($post);
}

do_action('king_addons/woo_builder/before_render', 'single_product', $template_id);
echo '<div class="king-addons-woo-builder king-addons-woo-builder--single-product" data-ka-context="single_product">';
echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
do_action('king_addons/woo_builder/after_render', 'single_product', $template_id);

if ($post instanceof WP_Post) {
    wp_reset_postdata();
}
if (function_exists('wc_reset_product_data')) {
    wc_reset_product_data();
}

do_action('woocommerce_after_main_content');

get_footer('shop');



