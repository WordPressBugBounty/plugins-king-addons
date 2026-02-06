<?php
/**
 * Base class for Archive builder widgets.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides archive context utilities.
 */
abstract class Abstract_Archive_Widget extends Widget_Base
{
    /**
     * Check if we should render the widget.
     * Returns true if in editor editing archive template or on actual archive page.
     *
     * @return bool
     */
    protected function should_render(): bool
    {
        // In editor mode editing product_archive template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('product_archive')) {
            return true;
        }

        // On actual archive page
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render placeholder when archive context is missing.
     *
     * @return void
     */
    protected function render_missing_archive_notice(): void
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

        // Check if we're editing an Archive template but context is not set
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('product_archive')) {
            // Don't show notice - we're in the right template type
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget works only on WooCommerce archive pages.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}






