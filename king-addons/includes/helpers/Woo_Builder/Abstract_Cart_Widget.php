<?php
/**
 * Base class for Cart builder widgets.
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
 * Provides cart context utilities.
 */
abstract class Abstract_Cart_Widget extends Widget_Base
{
    /**
     * Check if we should render the widget.
     * Returns true if in editor editing cart template or on actual cart page.
     *
     * @return bool
     */
    protected function should_render(): bool
    {
        // In editor mode editing cart template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('cart')) {
            return true;
        }

        // On actual cart page
        if (function_exists('is_cart') && is_cart()) {
            return true;
        }

        return false;
    }

    /**
     * Render placeholder when cart context is missing.
     *
     * @return void
     */
    protected function render_missing_cart_notice(): void
    {
        if (!class_exists('\Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return;
        }

        // Check if we're editing a Cart template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('cart')) {
            // Don't show notice - we're in the right template type
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget works only on the WooCommerce cart page.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}







