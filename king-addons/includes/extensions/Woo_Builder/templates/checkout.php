<?php
/**
 * Woo Builder wrapper for Checkout.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_id = apply_filters('king_addons/woo_builder/current_template_id', 0, 'checkout');

if (!$template_id) {
    wc_get_template('checkout/form-checkout.php');
    return;
}

wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-style');
wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script');

get_header();

do_action('woocommerce_before_main_content');

do_action('king_addons/woo_builder/before_render', 'checkout', $template_id);
echo '<div class="king-addons-woo-builder king-addons-woo-builder--checkout" data-ka-context="checkout">';
echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
do_action('king_addons/woo_builder/after_render', 'checkout', $template_id);

do_action('woocommerce_after_main_content');

get_footer();





