<?php
/**
 * Woo Builder wrapper for My Account.
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

$template_id = apply_filters('king_addons/woo_builder/current_template_id', 0, 'my_account');

if (!$template_id) {
    wc_get_template('myaccount/my-account.php');
    return;
}

wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-style');
wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-builder-script');

get_header();

do_action('woocommerce_before_main_content');

do_action('king_addons/woo_builder/before_render', 'my_account', $template_id);
echo '<div class="king-addons-woo-builder king-addons-woo-builder--my-account" data-ka-context="my_account">';
echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '</div>';
do_action('king_addons/woo_builder/after_render', 'my_account', $template_id);

do_action('woocommerce_after_main_content');

get_footer();





