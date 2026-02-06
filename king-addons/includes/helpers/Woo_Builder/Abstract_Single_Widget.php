<?php
/**
 * Base class for Single Product builder widgets.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;
use WC_Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides product resolution and editor notices.
 */
abstract class Abstract_Single_Widget extends Widget_Base
{
    /**
     * Get current product object.
     * In editor mode, will return a preview product if no product is set.
     *
     * @return WC_Product|null
     */
    protected function get_product(): ?WC_Product
    {
        global $product, $post;

        if (!function_exists('wc_get_product') || !class_exists('WC_Product')) {
            return null;
        }

        // If global product is already set, use it
        if ($product instanceof WC_Product) {
            return $product;
        }

        // Try to get from post
        if (!empty($post->ID) && 'product' === get_post_type($post->ID)) {
            $prod = wc_get_product($post->ID);
            if ($prod instanceof WC_Product) {
                return $prod;
            }
        }

        // In editor mode (including AJAX render), try to set up a preview product
        if (class_exists('King_Addons\\Woo_Builder\\Context')) {
            $preview = Woo_Context::setup_preview_product();
            if ($preview instanceof WC_Product) {
                return $preview;
            }
        }

        return null;
    }

    /**
     * Render placeholder when product context is missing.
     *
     * @return void
     */
    protected function render_missing_product_notice(): void
    {
        $is_editor = false;
        if (class_exists('King_Addons\\Woo_Builder\\Context')) {
            $is_editor = Woo_Context::is_editor();
        }

        $is_elementor_edit = false;
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance && isset(\Elementor\Plugin::$instance->editor)) {
            $is_elementor_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
        }

        if (!$is_editor && !$is_elementor_edit) {
            return;
        }

        // Check if we're editing a Single Product template but just don't have any products
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('single_product')) {
            echo '<div class="king-addons-woo-builder-notice">' . esc_html__('No products found. Please create at least one WooCommerce product to preview this widget.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget works only in a Single Product context.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}





