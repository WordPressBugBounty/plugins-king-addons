<?php
/**
 * Theme Builder front-end template loader.
 *
 * @package King_Addons
 */

use Elementor\Plugin as Elementor_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

$template_id = (int) apply_filters('king_addons/theme_builder/current_template_id', 0);

if (!$template_id) {
    return;
}

get_header();

do_action('king_addons/theme_builder/before_content', $template_id);

if (class_exists(Elementor_Plugin::class)) {
    echo Elementor_Plugin::instance()->frontend->get_builder_content_for_display($template_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

do_action('king_addons/theme_builder/after_content', $template_id);

get_footer();




